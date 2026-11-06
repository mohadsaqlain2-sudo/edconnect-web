/**
 * EduConnect live class room: our own WebRTC room (FR-18, FR-20, FR-22).
 *
 * The page contains no iframe and loads nothing from an external video server.
 * This module grabs the camera/microphone, joins through the application's own
 * signaling API (the "live." routes) and connects the peers in a small mesh:
 *
 *   1. getUserMedia -> local tile straight away,
 *   2. POST join    -> ICE servers + the current roster,
 *   3. poll signals with a self-scheduling loop and POST a heartbeat every
 *      five seconds,
 *   4. one RTCPeerConnection per remote participant, negotiated with the
 *      standard "perfect negotiation" pattern (onnegotiationneeded, polite =
 *      the larger id, rollback on collision),
 *   5. chat, mute, camera toggle, screen sharing and a clean teardown.
 *
 * Both the teacher and the students use the same code: the teacher simply
 * starts with the microphone on (data-moderator="1").
 */

/** How often the signaling mailbox is polled (plus a little jitter). */
const POLL_INTERVAL_MS = 1000;

/** Random jitter added to the poll interval so several tabs do not sync up. */
const POLL_JITTER_MS = 250;

/** A single poll may take this long before it is abandoned and retried. */
const POLL_TIMEOUT_MS = 15000;

/** How often the presence is refreshed. */
const HEARTBEAT_INTERVAL_MS = 5000;

/** How many times a failed peer connection is renegotiated before we give up. */
const MAX_RECOVERY_ATTEMPTS = 4;

/** How long a "disconnected" peer is given to recover on its own. */
const DISCONNECT_GRACE_MS = 5000;

/** Read the CSRF token that Laravel puts in the document head. */
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

/** Headers shared by every JSON POST (CSRF + accept JSON). */
function jsonHeaders() {
    return {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
    };
}

/** POST a JSON body and return the raw Response (callers inspect the status). */
function postJson(url, body, keepalive = false) {
    return fetch(url, {
        method: 'POST',
        headers: jsonHeaders(),
        body: JSON.stringify(body ?? {}),
        credentials: 'same-origin',
        keepalive,
    });
}

/** GET JSON, optionally cancellable with an AbortController. */
function getJson(url, signal) {
    return fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
        },
        credentials: 'same-origin',
        signal,
    });
}

class LiveRoom {
    constructor(wrapper) {
        this.wrapper = wrapper;

        this.grid = wrapper.querySelector('[data-live-grid]');
        this.status = wrapper.querySelector('[data-live-status]');
        this.errorBox = wrapper.querySelector('[data-live-error]');
        this.errorText = wrapper.querySelector('[data-live-error-text]');
        this.countEl = wrapper.querySelector('[data-live-count]');
        this.chatPanel = wrapper.querySelector('[data-live-chat-panel]');
        this.chatList = wrapper.querySelector('[data-live-chat-list]');
        this.chatForm = wrapper.querySelector('[data-live-chat-form]');
        this.chatInput = wrapper.querySelector('[data-live-chat-input]');

        this.micBtn = wrapper.querySelector('[data-live-mic]');
        this.cameraBtn = wrapper.querySelector('[data-live-camera]');
        this.screenBtn = wrapper.querySelector('[data-live-screen]');
        this.chatToggleBtn = wrapper.querySelector('[data-live-chat-toggle]');
        this.leaveBtn = wrapper.querySelector('[data-live-leave]');

        this.urls = {
            join: wrapper.dataset.joinUrl,
            heartbeat: wrapper.dataset.heartbeatUrl,
            signals: wrapper.dataset.signalsUrl,
            signal: wrapper.dataset.signalUrl,
            chat: wrapper.dataset.chatUrl,
            leave: wrapper.dataset.leaveUrl,
            dashboard: wrapper.dataset.dashboardUrl,
        };

        this.selfId = Number(wrapper.dataset.selfId);
        this.isModerator = wrapper.dataset.moderator === '1';
        this.displayName = wrapper.dataset.displayName || '';
        this.selfAvatar = wrapper.dataset.avatarUrl || '';

        // data-ice is a JSON array of RTCIceServer objects.
        let ice = [];
        try {
            ice = JSON.parse(wrapper.dataset.ice || '[]');
        } catch (error) {
            ice = [];
        }
        this.iceServers = Array.isArray(ice) ? ice : [];

        this.localStream = null;
        this.cameraTrack = null;
        this.screenStream = null;

        // The teacher starts unmuted, a student starts muted.
        this.audioEnabled = this.isModerator;
        this.videoEnabled = true;

        this.peers = new Map(); // id -> { pc, pending, stream, makingOffer, ... }
        this.peerNames = new Map(); // id -> name
        this.peerAvatars = new Map(); // id -> profile photo URL
        this.tiles = new Map(); // id -> tile element

        this.lastId = 0;
        this.pollTimer = null;
        this.pollController = null;
        this.pollActive = false;
        this.heartbeatTimer = null;

        // Autoplay / notice helpers.
        this.gestureBound = false;
        this.gestureHandler = null;
        this.autoplayBanner = null;
        this.noticeEl = null;
        this.noticeText = null;
        this.noticeTimer = null;

        this.booted = false;
        this.connecting = false;
        this.tornDown = false;
        this.leaving = false;
        this.requestingMic = false;
    }

    /** Wire the controls once and start the session. */
    boot() {
        if (this.booted) {
            return;
        }

        this.booted = true;
        this.bindControls();

        // Leaving the page (navigation, tab close) must free the camera and
        // tell the other peers that we are gone.
        window.addEventListener('beforeunload', () => this.teardown());
        window.addEventListener('pagehide', () => this.teardown());

        this.connect();
    }

    /**
     * Grab the devices, degrading gracefully. The camera and the microphone
     * are opened one after the other, never at the same time: a second
     * getUserMedia() that starts while the first request is still opening a
     * device is aborted by several browsers (AbortError / NotReadableError),
     * which used to drop the audio track and leave the room with video but no
     * sound. Keeping the requests in sequence still lets a denied camera or
     * microphone leave the other device working.
     */
    async acquireLocalStream() {
        if (!navigator.mediaDevices?.getUserMedia) {
            throw new Error('getUserMedia is not available');
        }

        const stream = new MediaStream();
        let lastError = null;

        const requests = [
            { video: true, audio: false },
            { video: false, audio: true },
        ];

        for (const constraints of requests) {
            try {
                // Awaited on its own: the next device is only requested once
                // this one has finished opening.
                const device = await navigator.mediaDevices.getUserMedia(constraints);
                device.getTracks().forEach((track) => stream.addTrack(track));
            } catch (error) {
                // One denied device must never stop the other one.
                lastError = error;
            }
        }

        if (stream.getTracks().length === 0) {
            throw lastError || new Error('No media device is available');
        }

        return stream;
    }

    /** Grab the devices and join the room. Safe to call again from "retry". */
    async connect() {
        if (this.connecting) {
            return;
        }

        this.connecting = true;
        this.showStatus();

        let stream;
        try {
            stream = await this.acquireLocalStream();
        } catch (error) {
            console.error('[EduConnect] the camera or the microphone is unavailable', error);
            this.showError('Allow the camera and the microphone in your browser, then try again.');
            this.connecting = false;
            return;
        }

        this.localStream = stream;

        const hasVideo = stream.getVideoTracks().length > 0;
        const hasAudio = stream.getAudioTracks().length > 0;

        this.videoEnabled = hasVideo;
        this.cameraTrack = stream.getVideoTracks()[0] || null;

        // A student joins muted: the track is only disabled, never blocked, so
        // it can be turned on again with one click. A moderator joins unmuted.
        this.audioEnabled = this.isModerator && hasAudio;
        stream.getAudioTracks().forEach((track) => {
            track.enabled = this.audioEnabled;
        });

        // An unplugged camera must show the avatar and keep the session alive.
        if (this.cameraTrack) {
            this.cameraTrack.onended = () => {
                this.cameraTrack = null;
                this.videoEnabled = false;
                this.refreshTileState(this.selfId);
                this.syncButtons();
                this.showNotice('Your camera is no longer available. The class is still running.');
            };
        }

        if (!hasVideo) {
            this.showNotice('Your camera is blocked or missing, so you joined without video.');
        }
        if (!hasAudio) {
            this.showNotice('Your microphone is blocked. Press the microphone button to allow it and speak.');
        }

        this.renderLocalTile();
        this.syncButtons();
        this.updateCount();

        let response;
        try {
            response = await postJson(this.urls.join, {});
        } catch (error) {
            console.error('[EduConnect] the join request failed', error);
            this.showError('The room could not be joined. Check your connection and try again.');
            this.stopLocalStream();
            this.connecting = false;
            return;
        }

        if (response.status === 409) {
            const data = await response.json().catch(() => ({}));
            this.showError(data.message || 'The class is not live yet.');
            this.stopLocalStream();
            this.connecting = false;
            return;
        }

        if (!response.ok) {
            this.showError('The room could not be joined. Please try again.');
            this.stopLocalStream();
            this.connecting = false;
            return;
        }

        const data = await response.json().catch(() => ({}));

        this.lastId = Number(data.last_signal_id || 0);
        if (this.status) {
            this.status.classList.add('d-none');
        }
        if (this.errorBox) {
            this.errorBox.classList.add('d-none');
        }

        this.applyParticipants(data.participants || []);

        this.startPolling();
        this.startHeartbeat();
        this.connecting = false;
    }

    /* ---------------------------------------------------------------
     | Controls
     * --------------------------------------------------------------- */

    bindControls() {
        this.micBtn?.addEventListener('click', () => this.toggleMic());
        this.cameraBtn?.addEventListener('click', () => this.toggleCamera());
        this.screenBtn?.addEventListener('click', () => this.toggleScreen());
        this.chatToggleBtn?.addEventListener('click', () => this.toggleChat());
        this.leaveBtn?.addEventListener('click', () => this.leave());

        this.chatForm?.addEventListener('submit', (event) => {
            event.preventDefault();

            const text = this.chatInput?.value.trim();
            if (!text) {
                return;
            }

            this.chatInput.value = '';
            this.sendChat(text);
        });
    }

    /** Microphone: enable/disable the local audio track (or ask for one). */
    async toggleMic() {
        // No track at all (blocked earlier, or the device was unplugged): ask
        // for the microphone now and publish it, instead of doing nothing.
        if (!this.hasAudioTrack()) {
            await this.enableMicrophone();
            return;
        }

        this.audioEnabled = !this.audioEnabled;

        this.localStream?.getAudioTracks().forEach((track) => {
            track.enabled = this.audioEnabled;
        });

        this.reflectMicState();
    }

    /** True when the local stream carries an audio track. */
    hasAudioTrack() {
        return (this.localStream?.getAudioTracks().length ?? 0) > 0;
    }

    /**
     * Ask for the microphone again and publish it to every peer that is
     * already connected (a fresh track renegotiates on its own).
     */
    async enableMicrophone() {
        if (this.requestingMic) {
            return;
        }

        this.requestingMic = true;

        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            const track = stream.getAudioTracks()[0];

            if (!track) {
                this.showNotice('No microphone is available on this device.');
                return;
            }

            if (!this.localStream) {
                this.localStream = new MediaStream();
            }

            this.localStream.addTrack(track);
            track.enabled = true;
            this.audioEnabled = true;

            for (const entry of this.peers.values()) {
                const sender = entry.pc.getSenders().find((s) => s.track?.kind === 'audio');

                if (sender) {
                    sender.replaceTrack(track).catch(() => {});
                } else {
                    entry.pc.addTrack(track, this.localStream);
                }
            }

            this.reflectMicState();
            this.showNotice('Microphone on.');
        } catch (error) {
            this.showNotice('The microphone is still blocked. Allow it in your browser settings.');
        } finally {
            this.requestingMic = false;
        }
    }

    /** Paint the microphone state on the button and on my own tile. */
    reflectMicState() {
        const icon = this.micBtn?.querySelector('i');
        if (icon) {
            icon.className = this.audioEnabled ? 'bi bi-mic' : 'bi bi-mic-mute';
        }
        this.setActive(this.micBtn, this.audioEnabled);

        const muted = this.tiles.get(this.selfId)?.querySelector('.edu-live-muted');
        muted?.classList.toggle('d-none', this.audioEnabled);
    }

    /** Camera: enable/disable the local video track and show the placeholder. */
    toggleCamera() {
        if (!this.cameraTrack) {
            return;
        }

        this.videoEnabled = !this.videoEnabled;
        this.cameraTrack.enabled = this.videoEnabled;

        const icon = this.cameraBtn?.querySelector('i');
        if (icon) {
            icon.className = this.videoEnabled ? 'bi bi-camera-video' : 'bi bi-camera-video-off';
        }
        this.setActive(this.cameraBtn, this.videoEnabled);

        this.refreshTileState(this.selfId);
    }

    /** Screen share: replace the outgoing video track while it runs. */
    async toggleScreen() {
        if (this.screenStream) {
            this.stopScreen();
            return;
        }

        let stream;
        try {
            stream = await navigator.mediaDevices.getDisplayMedia({ video: true });
        } catch (error) {
            // The user simply cancelled the picker.
            return;
        }

        this.screenStream = stream;
        const screenTrack = stream.getVideoTracks()[0];

        for (const entry of this.peers.values()) {
            const sender = entry.pc.getSenders().find((s) => s.track?.kind === 'video');
            if (sender) {
                await sender.replaceTrack(screenTrack).catch(() => {});
            }
        }

        // Show our own screen in the local tile while sharing.
        const localTile = this.tiles.get(this.selfId);
        const localVideo = localTile?.querySelector('video');
        if (localVideo) {
            localVideo.srcObject = stream;
            this.attemptPlay(this.selfId, localVideo);
            this.refreshTileState(this.selfId);
        }

        // "Stop sharing" from the browser chrome ends it too.
        screenTrack.onended = () => this.stopScreen();
        this.setActive(this.screenBtn, true);
    }

    /** Back to the camera after the screen share stops. */
    stopScreen() {
        if (this.screenStream) {
            this.screenStream.getTracks().forEach((track) => track.stop());
            this.screenStream = null;
        }

        for (const entry of this.peers.values()) {
            const sender = entry.pc.getSenders().find((s) => s.track?.kind === 'video');
            if (sender && this.cameraTrack) {
                sender.replaceTrack(this.cameraTrack).catch(() => {});
            }
        }

        const localVideo = this.tiles.get(this.selfId)?.querySelector('video');
        if (localVideo && this.localStream) {
            localVideo.srcObject = this.localStream;
            this.attemptPlay(this.selfId, localVideo);
        }
        this.refreshTileState(this.selfId);

        this.setActive(this.screenBtn, false);
    }

    /** Show / hide the chat panel. */
    toggleChat() {
        const hidden = this.chatPanel?.classList.toggle('d-none');
        this.chatToggleBtn?.setAttribute('aria-expanded', hidden ? 'false' : 'true');
        this.setActive(this.chatToggleBtn, !hidden);
    }

    /** Leave: tell the server, then go back to the dashboard. */
    leave() {
        if (this.leaving) {
            return;
        }

        this.leaving = true;
        this.teardown();
        window.location.href = this.urls.dashboard;
    }

    /* ---------------------------------------------------------------
     | Presence, polling and signaling
     * --------------------------------------------------------------- */

    /**
     * Start the self-scheduling poll loop. A new request is only scheduled
     * once the previous one has finished, so a slow host (php artisan serve is
     * single threaded) can never starve the poller.
     */
    startPolling() {
        this.pollActive = true;
        this.schedulePoll(0);
    }

    schedulePoll(delay) {
        if (!this.pollActive) {
            return;
        }

        if (this.pollTimer) {
            window.clearTimeout(this.pollTimer);
        }

        this.pollTimer = window.setTimeout(() => this.poll(), delay);
    }

    stopPolling() {
        this.pollActive = false;

        if (this.pollTimer) {
            window.clearTimeout(this.pollTimer);
            this.pollTimer = null;
        }

        // Only teardown aborts an in-flight poll: a healthy request is never
        // cancelled, it is simply awaited.
        if (this.pollController) {
            this.pollController.abort();
            this.pollController = null;
        }
    }

    /** One polling round. At most one request is ever in flight. */
    async poll() {
        if (!this.pollActive || this.pollController) {
            return;
        }

        const controller = new AbortController();
        this.pollController = controller;
        const timeout = window.setTimeout(() => controller.abort(), POLL_TIMEOUT_MS);

        try {
            const response = await getJson(`${this.urls.signals}?after=${this.lastId}`, controller.signal);

            if (!this.pollActive) {
                return;
            }
            if (!response.ok) {
                return;
            }

            const data = await response.json().catch(() => ({}));

            this.applyParticipants(data.participants || []);

            // Process the messages strictly in order: two setRemoteDescription
            // calls must never overlap on the same connection.
            for (const message of data.messages || []) {
                if (message.type === 'chat') {
                    this.renderChat(message);
                } else if (message.type === 'bye') {
                    this.handleBye(message);
                } else {
                    await this.handleSignal(message);
                }
            }

            if (typeof data.last_id === 'number' && data.last_id > this.lastId) {
                this.lastId = data.last_id;
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('[EduConnect] the signaling poll failed', error);
            }
        } finally {
            window.clearTimeout(timeout);

            if (this.pollController === controller) {
                this.pollController = null;
                const jitter = Math.floor(Math.random() * POLL_JITTER_MS);
                this.schedulePoll(POLL_INTERVAL_MS + jitter);
            }
        }
    }

    startHeartbeat() {
        window.clearInterval(this.heartbeatTimer);
        this.heartbeatTimer = window.setInterval(() => this.heartbeat(), HEARTBEAT_INTERVAL_MS);
    }

    stopHeartbeat() {
        window.clearInterval(this.heartbeatTimer);
        this.heartbeatTimer = null;
    }

    async heartbeat() {
        try {
            await postJson(this.urls.heartbeat, {});
        } catch (error) {
            // A missed heartbeat is recovered on the next tick.
        }
    }

    /* ---------------------------------------------------------------
     | The peer mesh
     * --------------------------------------------------------------- */

    /** Reconcile tiles and peer connections with the roster we just received. */
    applyParticipants(participants) {
        const seen = new Set();

        for (const participant of participants) {
            const id = Number(participant.id);
            seen.add(id);

            if (id === this.selfId) {
                continue;
            }

            this.peerNames.set(id, participant.name);
            this.peerAvatars.set(id, participant.photo_url || '');

            const tile = this.ensureTile(id, participant.name);
            if (tile) {
                tile.querySelector('.edu-live-name').textContent = participant.name;
                this.applyAvatar(tile, participant.name, participant.photo_url);
            }

            // Creating the connection adds our local tracks, which fires
            // onnegotiationneeded: no ad-hoc handshake role is needed.
            this.ensurePeer(id, participant.name, participant.photo_url);
        }

        // Anyone no longer in the roster (and without a tile) has left.
        const known = new Set([...this.peers.keys(), ...this.tiles.keys()]);
        for (const id of known) {
            if (id !== this.selfId && !seen.has(id)) {
                this.closePeer(id);
            }
        }

        this.updateCount();
    }

    /** Create the peer connection for a remote participant (once). */
    ensurePeer(id, name, photoUrl) {
        const existing = this.peers.get(id);
        if (existing) {
            return existing;
        }

        const pc = new RTCPeerConnection({ iceServers: this.iceServers });
        const entry = {
            pc,
            pending: [],
            stream: null,
            makingOffer: false,
            ignoreOffer: false,
            failures: 0,
            recoverTimer: null,
            graceTimer: null,
        };
        this.peers.set(id, entry);
        this.peerNames.set(id, name || this.peerNames.get(id) || '');

        if (photoUrl !== undefined) {
            this.peerAvatars.set(id, photoUrl || '');
        }

        const tile = this.ensureTile(id, name);
        this.applyAvatar(tile, this.peerNames.get(id), this.peerAvatars.get(id));

        pc.onicecandidate = (event) => {
            if (event.candidate) {
                this.sendSignal('ice', id, { candidate: event.candidate.toJSON() });
            }
        };

        pc.ontrack = (event) => {
            if (!entry.stream) {
                entry.stream = new MediaStream();
            }

            const track = event.track;
            if (!entry.stream.getTracks().includes(track)) {
                entry.stream.addTrack(track);
            }

            // A remote track starts "muted" and unmutes once frames flow: use
            // that (not ontrack alone) to decide when the tile is really live.
            const refresh = () => this.refreshTileState(id);
            track.onmute = refresh;
            track.onunmute = refresh;
            track.onended = refresh;

            this.attachStream(id, entry.stream);
        };

        pc.onnegotiationneeded = () => {
            this.offerTo(id);
        };

        pc.onconnectionstatechange = () => {
            const state = pc.connectionState;

            if (state === 'connected') {
                entry.failures = 0;
                if (entry.graceTimer) {
                    window.clearTimeout(entry.graceTimer);
                    entry.graceTimer = null;
                }
                return;
            }

            if (state === 'disconnected') {
                // Give the peer a grace period before trying to recover.
                if (!entry.graceTimer) {
                    entry.graceTimer = window.setTimeout(() => {
                        entry.graceTimer = null;
                        if (this.peers.has(id) && pc.connectionState !== 'connected') {
                            this.recoverPeer(id);
                        }
                    }, DISCONNECT_GRACE_MS);
                }
                return;
            }

            if (state === 'failed') {
                this.recoverPeer(id);
            }
        };

        // Publish our own media so the remote side can render it. This must
        // come after the handlers so onnegotiationneeded is not missed.
        if (this.localStream) {
            this.localStream.getTracks().forEach((track) => pc.addTrack(track, this.localStream));
        }

        return entry;
    }

    /**
     * Perfect negotiation: create and send an offer for one peer. Used both by
     * onnegotiationneeded and by the recovery path (ICE restart).
     */
    async offerTo(id, options) {
        const entry = this.peers.get(id);
        if (!entry) {
            return;
        }

        const pc = entry.pc;
        if (entry.makingOffer || pc.signalingState !== 'stable') {
            return;
        }

        entry.makingOffer = true;

        try {
            const offer = await pc.createOffer(options);
            await pc.setLocalDescription(offer);
            await this.sendSignal('offer', id, { sdp: pc.localDescription });
        } catch (error) {
            console.error('[EduConnect] could not create the offer', error);
        } finally {
            entry.makingOffer = false;
        }
    }

    /** React to one offer / answer / ice message (perfect negotiation). */
    async handleSignal(message) {
        const from = Number(message.from);

        if (!from || from === this.selfId) {
            return;
        }

        const entry = this.ensurePeer(from, this.peerNames.get(from) || '', this.peerAvatars.get(from));
        const pc = entry.pc;
        const payload = message.payload || {};

        if (message.type === 'ice') {
            const candidate = payload.candidate;

            if (!candidate) {
                return;
            }

            // A candidate can arrive before the SDP: queue it until then.
            if (pc.remoteDescription) {
                try {
                    await pc.addIceCandidate(new RTCIceCandidate(candidate));
                } catch (error) {
                    if (!entry.ignoreOffer) {
                        console.error('[EduConnect] could not add the ICE candidate', error);
                    }
                }
            } else {
                entry.pending.push(candidate);
            }
            return;
        }

        if (message.type !== 'offer' && message.type !== 'answer') {
            return;
        }

        const description = payload.sdp;
        if (!description) {
            return;
        }

        // Perfect negotiation: the polite peer is the one with the larger id.
        const polite = this.selfId > from;
        const offerCollision = description.type === 'offer'
            && (entry.makingOffer || pc.signalingState !== 'stable');

        entry.ignoreOffer = !polite && offerCollision;
        if (entry.ignoreOffer) {
            return;
        }

        try {
            // On a collision the polite peer's pending offer is rolled back
            // implicitly when the incoming offer is applied.
            await pc.setRemoteDescription(new RTCSessionDescription(description));
            await this.drainPending(entry);

            if (description.type === 'offer') {
                const answer = await pc.createAnswer();
                await pc.setLocalDescription(answer);
                await this.sendSignal('answer', from, { sdp: pc.localDescription });
            }
        } catch (error) {
            console.error('[EduConnect] could not apply the remote description', error);
        }
    }

    /** Add the ICE candidates that arrived before the remote description. */
    async drainPending(entry) {
        for (const candidate of entry.pending) {
            await entry.pc.addIceCandidate(new RTCIceCandidate(candidate)).catch(() => {});
        }
        entry.pending = [];
    }

    /**
     * A peer connection died. Try an ICE restart / renegotiation a few times
     * with backoff before giving up, so a transient network hiccup recovers.
     */
    recoverPeer(id) {
        const entry = this.peers.get(id);
        if (!entry) {
            return;
        }

        entry.failures += 1;

        if (entry.failures > MAX_RECOVERY_ATTEMPTS) {
            console.warn('[EduConnect] giving up on the peer', id);
            this.closePeer(id);
            return;
        }

        const delay = Math.min(8000, 500 * 2 ** (entry.failures - 1));

        if (entry.recoverTimer) {
            window.clearTimeout(entry.recoverTimer);
        }

        entry.recoverTimer = window.setTimeout(() => {
            entry.recoverTimer = null;

            if (!this.peers.has(id)) {
                return;
            }

            try {
                entry.pc.restartIce?.();
            } catch (error) {
                // Older browsers: the plain renegotiation below still runs.
            }

            this.offerTo(id, { iceRestart: true });
        }, delay);
    }

    /** Send one signaling message to a peer. */
    async sendSignal(type, to, payload) {
        try {
            await postJson(this.urls.signal, { type, to, payload });
        } catch (error) {
            console.error('[EduConnect] could not send the signal', error);
        }
    }

    /** Send one chat line to the whole room (FR-22). */
    async sendChat(text) {
        try {
            await postJson(this.urls.chat, { text });
        } catch (error) {
            console.error('[EduConnect] could not send the chat message', error);
        }
    }

    /** A peer announced it is leaving: drop it right away. */
    handleBye(message) {
        const from = Number(message.from);
        if (!from || from === this.selfId) {
            return;
        }

        this.peerNames.delete(from);
        this.closePeer(from);
    }

    /** Close a peer connection and remove its tile. */
    closePeer(id) {
        const entry = this.peers.get(id);

        if (entry) {
            if (entry.recoverTimer) {
                window.clearTimeout(entry.recoverTimer);
            }
            if (entry.graceTimer) {
                window.clearTimeout(entry.graceTimer);
            }

            try {
                entry.pc.close();
            } catch (error) {
                // Already closed: nothing to do.
            }
            this.peers.delete(id);
        }

        const tile = this.tiles.get(id);
        if (tile && id !== this.selfId) {
            tile.querySelector('.edu-live-play')?.remove();
            tile.remove();
            this.tiles.delete(id);
        }

        this.peerNames.delete(id);
        this.peerAvatars.delete(id);
        this.updateCount();
    }

    /* ---------------------------------------------------------------
     | Rendering
     * --------------------------------------------------------------- */

    /** Build (once) the tile of one participant. */
    ensureTile(id, name) {
        const existing = this.tiles.get(id);
        if (existing) {
            return existing;
        }

        // is-cam-off keeps the avatar placeholder visible until a stream (or
        // the local camera) actually fills the tile.
        const tile = document.createElement('div');
        tile.className = 'edu-live-tile is-cam-off';
        tile.dataset.peerId = String(id);

        const video = document.createElement('video');
        video.autoplay = true;
        video.playsInline = true;
        // The placeholder is cleared by real media, not by the first ontrack.
        video.addEventListener('loadeddata', () => this.refreshTileState(id));
        video.addEventListener('resize', () => this.refreshTileState(id));
        video.addEventListener('playing', () => this.onVideoPlaying(id));
        tile.appendChild(video);

        // The avatar placeholder: the participant's profile photo, with their
        // initials as the fallback whenever there is no photo (or it fails).
        const avatar = document.createElement('div');
        avatar.className = 'edu-live-avatar';

        const photo = document.createElement('img');
        photo.className = 'edu-live-avatar-photo';
        photo.alt = '';
        photo.decoding = 'async';
        photo.referrerPolicy = 'no-referrer';
        photo.addEventListener('load', () => avatar.classList.add('has-photo'));
        photo.addEventListener('error', () => avatar.classList.remove('has-photo'));

        const initials = document.createElement('span');
        initials.className = 'edu-live-avatar-initials';
        initials.textContent = (name || '?').charAt(0).toUpperCase();

        avatar.appendChild(photo);
        avatar.appendChild(initials);
        tile.appendChild(avatar);

        const label = document.createElement('span');
        label.className = 'edu-live-name';
        label.textContent = name || '';
        tile.appendChild(label);

        const muted = document.createElement('span');
        muted.className = 'edu-live-muted d-none';
        muted.setAttribute('aria-label', 'Muted');
        muted.innerHTML = '<i class="bi bi-mic-mute" aria-hidden="true"></i>';
        tile.appendChild(muted);

        this.grid?.appendChild(tile);
        this.tiles.set(id, tile);

        return tile;
    }

    /**
     * Put a participant's name and profile photo on their tile. The initials
     * always stay as the fallback; the photo simply covers them once it loads.
     */
    applyAvatar(tile, name, photoUrl) {
        const avatar = tile?.querySelector('.edu-live-avatar');
        if (!avatar) {
            return;
        }

        const initials = avatar.querySelector('.edu-live-avatar-initials');
        if (initials) {
            initials.textContent = (name || '?').charAt(0).toUpperCase();
        }

        const photo = avatar.querySelector('.edu-live-avatar-photo');
        if (!photo) {
            return;
        }

        const url = photoUrl || '';

        if (!url) {
            avatar.classList.remove('has-photo');
            photo.removeAttribute('src');
            return;
        }

        if (photo.getAttribute('src') !== url) {
            // "has-photo" is set again by the load handler once the image is in.
            avatar.classList.remove('has-photo');
            photo.src = url;
        }
    }

    /** The local tile: muted, mirrored, always first. */
    renderLocalTile() {
        const tile = this.ensureTile(this.selfId, this.displayName);
        if (!tile) {
            return;
        }

        tile.classList.add('is-local');

        const video = tile.querySelector('video');
        video.muted = true;
        if (this.localStream) {
            video.srcObject = this.localStream;
            this.watchTracks(this.selfId, this.localStream);
            this.attemptPlay(this.selfId, video);
        }

        this.applyAvatar(tile, this.displayName, this.selfAvatar);

        this.refreshTileState(this.selfId);
        tile.querySelector('.edu-live-muted')?.classList.toggle('d-none', this.audioEnabled);
    }

    /** Attach a remote stream to its tile. */
    attachStream(id, stream) {
        const tile = this.ensureTile(id, this.peerNames.get(id) || '');
        if (!tile) {
            return;
        }

        this.applyAvatar(tile, this.peerNames.get(id), this.peerAvatars.get(id));

        const video = tile.querySelector('video');
        if (video.srcObject !== stream) {
            video.srcObject = stream;
        }

        this.watchTracks(id, stream);
        this.attemptPlay(id, video);
        this.refreshTileState(id);
    }

    /** Mirror track mute/end events into the tile placeholder. */
    watchTracks(id, stream) {
        if (!stream) {
            return;
        }

        const refresh = () => this.refreshTileState(id);

        stream.getTracks().forEach((track) => {
            track.onmute = refresh;
            track.onunmute = refresh;
            track.onended = refresh;
        });
    }

    /** True only when the stream carries a real, enabled, unmuted video track. */
    videoIsLive(stream) {
        if (!stream || typeof stream.getVideoTracks !== 'function') {
            return false;
        }

        return stream.getVideoTracks().some(
            (track) => track.readyState === 'live' && track.enabled && !track.muted
        );
    }

    /** Show the avatar placeholder until the tile has live video. */
    refreshTileState(id) {
        const tile = this.tiles.get(id);
        if (!tile) {
            return;
        }

        const video = tile.querySelector('video');
        const live = this.videoIsLive(video?.srcObject || null);
        tile.classList.toggle('is-cam-off', !live);
    }

    /* ---------------------------------------------------------------
     | Autoplay handling
     * --------------------------------------------------------------- */

    /** Try to play a video; surface a control when the browser blocks it. */
    attemptPlay(id, video) {
        if (!video) {
            return;
        }

        const promise = video.play();
        if (promise && typeof promise.catch === 'function') {
            promise.catch(() => this.needsGesture(id));
        }
    }

    /** The browser refused to autoplay: offer a click-to-play control. */
    needsGesture(id) {
        const tile = this.tiles.get(id);
        if (tile) {
            this.showPlayOverlay(tile);
        }
        this.showAutoplayBanner();
        this.bindGestureRetry();
    }

    /** A per-tile overlay that starts playback on click / Enter / Space. */
    showPlayOverlay(tile) {
        if (tile.querySelector('.edu-live-play')) {
            return;
        }

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'edu-live-play';
        button.setAttribute('aria-label', 'Click to play the video');
        button.innerHTML = '<i class="bi bi-play-fill" aria-hidden="true"></i>'
            + '<span>Click to play</span>';
        button.addEventListener('click', () => {
            const video = tile.querySelector('video');
            const promise = video?.play();
            if (promise && typeof promise.catch === 'function') {
                promise.catch(() => {});
            }
        });

        tile.appendChild(button);
    }

    /** A room-level banner telling the user to click to start the media. */
    showAutoplayBanner() {
        if (this.autoplayBanner) {
            this.autoplayBanner.classList.remove('d-none');
            return;
        }

        const banner = document.createElement('div');
        banner.className = 'alert alert-info d-flex align-items-center gap-2 py-2 px-3 small mb-2';
        banner.setAttribute('role', 'status');

        const icon = document.createElement('i');
        icon.className = 'bi bi-volume-up';
        icon.setAttribute('aria-hidden', 'true');

        const text = document.createElement('span');
        text.textContent = 'Your browser blocked the sound and video. Click anywhere to start them.';

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-sm btn-primary ms-auto';
        button.textContent = 'Play';
        button.setAttribute('aria-label', 'Play the audio and video');
        button.addEventListener('click', () => this.playAllVideos());

        banner.appendChild(icon);
        banner.appendChild(text);
        banner.appendChild(button);

        this.wrapper.insertBefore(banner, this.wrapper.firstChild);
        this.autoplayBanner = banner;
    }

    hideAutoplayBanner() {
        this.autoplayBanner?.remove();
        this.autoplayBanner = null;
    }

    /** Try to play every tile; used by the banner and the first gesture. */
    playAllVideos() {
        for (const tile of this.tiles.values()) {
            const video = tile.querySelector('video');
            if (!video || !video.paused) {
                continue;
            }

            const promise = video.play();
            if (promise && typeof promise.catch === 'function') {
                promise.catch(() => {});
            }
        }
    }

    /** Retry playback on the first user gesture anywhere in the document. */
    bindGestureRetry() {
        if (this.gestureBound) {
            return;
        }

        this.gestureBound = true;
        this.gestureHandler = () => {
            this.playAllVideos();
            if (!this.wrapper.querySelector('.edu-live-play') && !this.hasPausedRemote()) {
                this.unbindGestureRetry();
            }
        };

        document.addEventListener('click', this.gestureHandler, true);
        document.addEventListener('keydown', this.gestureHandler, true);
        document.addEventListener('pointerdown', this.gestureHandler, true);
    }

    unbindGestureRetry() {
        if (!this.gestureBound) {
            return;
        }

        this.gestureBound = false;
        document.removeEventListener('click', this.gestureHandler, true);
        document.removeEventListener('keydown', this.gestureHandler, true);
        document.removeEventListener('pointerdown', this.gestureHandler, true);
    }

    hasPausedRemote() {
        for (const [id, tile] of this.tiles) {
            if (id === this.selfId) {
                continue;
            }

            const video = tile.querySelector('video');
            if (video && video.paused) {
                return true;
            }
        }

        return false;
    }

    /** Playback started: drop the overlay and maybe the banner. */
    onVideoPlaying(id) {
        this.tiles.get(id)?.querySelector('.edu-live-play')?.remove();

        if (!this.wrapper.querySelector('.edu-live-play')) {
            this.hideAutoplayBanner();
        }

        this.refreshTileState(id);
    }

    /* ---------------------------------------------------------------
     | Notices
     * --------------------------------------------------------------- */

    /** A non-blocking warning: the room stays usable. */
    showNotice(message) {
        if (!this.noticeEl) {
            const el = document.createElement('div');
            el.className = 'alert alert-warning d-flex align-items-center gap-2 py-2 px-3 small mb-2';
            el.setAttribute('role', 'status');

            const icon = document.createElement('i');
            icon.className = 'bi bi-exclamation-triangle';
            icon.setAttribute('aria-hidden', 'true');

            const text = document.createElement('span');

            el.appendChild(icon);
            el.appendChild(text);
            this.wrapper.insertBefore(el, this.wrapper.firstChild);

            this.noticeEl = el;
            this.noticeText = text;
        }

        this.noticeText.textContent = message;
        this.noticeEl.classList.remove('d-none');

        if (this.noticeTimer) {
            window.clearTimeout(this.noticeTimer);
        }
        this.noticeTimer = window.setTimeout(() => this.noticeEl?.classList.add('d-none'), 8000);
    }

    /** Paint a chat line received through the signals poll. */
    renderChat(message) {
        const payload = message.payload || {};

        const row = document.createElement('div');
        row.className = 'edu-live-msg';

        const name = document.createElement('span');
        name.className = 'edu-live-msg-name';
        name.textContent = payload.name || '';

        const text = document.createElement('span');
        text.className = 'edu-live-msg-text';
        text.textContent = payload.text || '';

        row.appendChild(name);
        row.appendChild(text);
        this.chatList?.appendChild(row);

        if (this.chatList) {
            this.chatList.scrollTop = this.chatList.scrollHeight;
        }
    }

    /** Refresh the participants counter. */
    updateCount() {
        if (this.countEl) {
            this.countEl.textContent = String(this.tiles.size);
        }
    }

    /** Reflect the microphone / camera / screen / chat state on the buttons. */
    syncButtons() {
        this.setActive(this.micBtn, this.audioEnabled);

        const micIcon = this.micBtn?.querySelector('i');
        if (micIcon) {
            micIcon.className = this.audioEnabled ? 'bi bi-mic' : 'bi bi-mic-mute';
        }

        this.setActive(this.cameraBtn, this.videoEnabled);

        const camIcon = this.cameraBtn?.querySelector('i');
        if (camIcon) {
            camIcon.className = this.videoEnabled ? 'bi bi-camera-video' : 'bi bi-camera-video-off';
        }
    }

    setActive(button, active) {
        button?.classList.toggle('is-active', active);
    }

    showStatus() {
        this.status?.classList.remove('d-none');
        this.errorBox?.classList.add('d-none');
    }

    showError(message) {
        if (this.status) {
            this.status.classList.add('d-none');
        }
        if (this.errorText) {
            this.errorText.textContent = message;
        }
        this.errorBox?.classList.remove('d-none');
    }

    /* ---------------------------------------------------------------
     | Teardown
     * --------------------------------------------------------------- */

    /** Stop everything: polls, heartbeat, peers, tracks and presence. */
    teardown() {
        if (this.tornDown) {
            return;
        }

        this.tornDown = true;

        this.stopPolling();
        this.stopHeartbeat();
        this.unbindGestureRetry();

        for (const id of [...this.peers.keys()]) {
            this.closePeer(id);
        }

        if (this.screenStream) {
            this.screenStream.getTracks().forEach((track) => track.stop());
            this.screenStream = null;
        }

        this.stopLocalStream();
        this.sendLeave();
    }

    stopLocalStream() {
        if (this.localStream) {
            this.localStream.getTracks().forEach((track) => track.stop());
            this.localStream = null;
        }
        this.cameraTrack = null;
    }

    /** Tell the server we left; sendBeacon survives a page unload. */
    sendLeave() {
        try {
            const body = new FormData();
            body.append('_token', csrfToken());

            if (navigator.sendBeacon?.(this.urls.leave, body)) {
                return;
            }
        } catch (error) {
            // Fall through to fetch().
        }

        postJson(this.urls.leave, {}, true).catch(() => {});
    }
}

/** Boot every room on the page, once each. */
const booted = new WeakSet();

function initLiveRooms() {
    document.querySelectorAll('[data-live-room]').forEach((wrapper) => {
        if (booted.has(wrapper)) {
            return;
        }

        booted.add(wrapper);

        const room = new LiveRoom(wrapper);
        room.boot();

        wrapper.querySelector('[data-live-retry]')?.addEventListener('click', () => {
            // A failed attempt may have stopped the tracks: start clean.
            room.tornDown = false;
            room.connect();
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLiveRooms);
} else {
    initLiveRooms();
}
