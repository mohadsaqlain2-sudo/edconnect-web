/**
 * EduConnect live class room (FR-18, FR-20, FR-22).
 *
 * The page does not contain an iframe: it carries the room settings in data-*
 * attributes and this file asks the Jitsi Meet External API to build the room
 * (video, audio, screen sharing and the built in chat all come from Jitsi).
 *
 * Two things have to keep working on a bad connection:
 *   1. the API script may fail to load or time out -> friendly message + retry,
 *   2. the user may end the call from inside Jitsi -> back to the dashboard.
 */

/** Give up on the External API after this long (a school network can hang). */
const SCRIPT_TIMEOUT_MS = 15000;

/** How long we wait after "hangup" before leaving the page. */
const LEAVE_DELAY_MS = 1200;

/** The JitsiMeetExternalAPI instance; needed to close the room on a retry. */
let api = null;

/** Guards against a double redirect when hangup and readyToClose both fire. */
let leaving = false;

/**
 * Load the External API script once and resolve when window.JitsiMeetExternalAPI
 * exists. Rejects on a network error, a missing global or a timeout.
 */
function loadExternalApi(url) {
    return new Promise((resolve, reject) => {
        if (window.JitsiMeetExternalAPI) {
            resolve();
            return;
        }

        const script = document.createElement('script');
        script.src = url;
        script.async = true;
        script.dataset.jitsiApi = '1';

        // Without a timeout a hanging request would leave the spinner forever.
        const timer = window.setTimeout(() => {
            script.remove();
            reject(new Error('timeout'));
        }, SCRIPT_TIMEOUT_MS);

        script.addEventListener('load', () => {
            window.clearTimeout(timer);
            if (window.JitsiMeetExternalAPI) {
                resolve();
            } else {
                reject(new Error('api-missing'));
            }
        });

        script.addEventListener('error', () => {
            window.clearTimeout(timer);
            reject(new Error('network'));
        });

        document.head.appendChild(script);
    });
}

/** Leave the page and go back to the class detail page (FR-20 edge case). */
function leaveRoom(leaveUrl) {
    if (leaving) {
        return;
    }

    leaving = true;
    window.setTimeout(() => {
        window.location.href = leaveUrl;
    }, LEAVE_DELAY_MS);
}

/**
 * Build the Jitsi room for one [data-jitsi] wrapper.
 */
async function startRoom(wrapper) {
    const container = wrapper.querySelector('[data-jitsi-container]');
    const status = wrapper.querySelector('[data-jitsi-status]');
    const errorBox = wrapper.querySelector('[data-jitsi-error]');

    const showError = () => {
        status?.classList.add('d-none');
        errorBox?.classList.remove('d-none');
    };

    // Reset the UI so a retry starts from a clean state.
    errorBox?.classList.add('d-none');
    status?.classList.remove('d-none');

    if (container) {
        container.innerHTML = '';
    }

    // Close a room that is still open from a previous attempt.
    if (api) {
        try {
            api.dispose();
        } catch (error) {
            console.warn('[EduConnect] could not dispose the previous room', error);
        }
        api = null;
    }

    try {
        await loadExternalApi(wrapper.dataset.scriptUrl);
    } catch (error) {
        console.error('[EduConnect] the Jitsi External API did not load', error);
        showError();
        return;
    }

    const options = {
        roomName: wrapper.dataset.room,
        parentNode: container,
        width: '100%',
        height: '100%',
        // Jitsi shows these next to every message and in the participant list.
        userInfo: {
            displayName: wrapper.dataset.displayName,
            email: wrapper.dataset.email,
            avatarUrl: wrapper.dataset.avatar,
        },
        configOverwrite: {
            // Teacher-friendly defaults: the moderator starts unmuted with the
            // camera on. Students start muted so they can look around first.
            startWithVideoMuted: wrapper.dataset.moderator !== '1',
            startWithAudioMuted: wrapper.dataset.moderator !== '1',
            // Show the preview screen so a student can check the microphone first.
            prejoinPageEnabled: true,
            // Keeps mobile browsers on the page instead of opening the app.
            disableDeepLinking: true,
            // The class title is the meeting subject shown in the room.
            subject: wrapper.dataset.subject,
            // Exactly the buttons the brief asks for: microphone, camera,
            // chat, raise hand, tile view, fullscreen and hang up.
            toolbarButtons: [
                'microphone',
                'camera',
                'chat',
                'raisehand',
                'tileview',
                'fullscreen',
                'hangup',
            ],
        },
        interfaceConfigOverwrite: {
            SHOW_JITSI_WATERMARK: false,
            MOBILE_APP_PROMO: false,
            DEFAULT_BACKGROUND: '#f6f7fb',
        },
    };

    try {
        api = new window.JitsiMeetExternalAPI(wrapper.dataset.domain, options);
    } catch (error) {
        console.error('[EduConnect] Jitsi refused to start the room', error);
        showError();
        return;
    }

    // The spinner can go as soon as the user is inside the room.
    api.addEventListener('videoConferenceJoined', () => {
        status?.classList.add('d-none');
    });

    // The red phone inside Jitsi: back to the class page with a flash message.
    api.addEventListener('hangup', () => {
        leaveRoom(wrapper.dataset.leaveUrl);
    });

    // Fired after the room was closed from the Jitsi side.
    api.addEventListener('readyToClose', () => {
        leaveRoom(wrapper.dataset.leaveUrl);
    });

    // A failed conference (wrong room, server down) must not look like a hang.
    api.addEventListener('errorOccurred', (event) => {
        console.error('[EduConnect] Jitsi reported an error', event);
        showError();
    });
}

/** Wire every room on the page (there is only one, but this stays reusable). */
function initJitsiRooms() {
    document.querySelectorAll('[data-jitsi]').forEach((wrapper) => {
        startRoom(wrapper);

        // Retry button shown when the script could not be loaded.
        wrapper.querySelector('[data-jitsi-retry]')?.addEventListener('click', () => {
            startRoom(wrapper);
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initJitsiRooms);
} else {
    initJitsiRooms();
}
