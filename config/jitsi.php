<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Jitsi Meet
    |--------------------------------------------------------------------------
    |
    | The domain that hosts the Jitsi Meet installation. The default is the
    | public server meet.jit.si. Point JITSI_DOMAIN at your own self hosted
    | instance (for example meet.my-school.org) to keep the traffic inside
    | your network - no code has to change.
    |
    | The External API script is normally served from that same domain.
    | JITSI_SCRIPT_URL lets you override the URL when a proxy serves it
    | somewhere else.
    |
    */

    'domain' => $domain = env('JITSI_DOMAIN', 'meet.jit.si'),

    'script_url' => env('JITSI_SCRIPT_URL', 'https://'.$domain.'/external_api.js'),

];
