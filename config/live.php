<?php

return [
    // Public STUN server used to discover ICE candidates. No TURN server:
    // peers inside one school network connect directly.
    'stun' => env('LIVE_STUN_URL', 'stun:stun.l.google.com:19302'),

    // Presence is refreshed by a heartbeat; a peer that has not been seen for
    // this many seconds is treated as gone.
    'presence_ttl' => (int) env('LIVE_PRESENCE_TTL', 25),

    // Signaling rows older than this are pruned (seconds).
    'signal_ttl' => (int) env('LIVE_SIGNAL_TTL', 600),
];
