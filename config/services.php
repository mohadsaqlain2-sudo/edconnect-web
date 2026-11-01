<?php

$jitsiDomain = env('JITSI_DOMAIN', 'meet.jit.si');

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Jitsi Meet
    |--------------------------------------------------------------------------
    |
    | The domain that hosts the Jitsi Meet installation. The default is the
    | public server meet.jit.si. Point JITSI_DOMAIN at a self hosted instance
    | (for example meet.my-school.org) and the whole app follows: the External
    | API script, the iframe origin and the Content-Security-Policy.
    |
    | JITSI_SCRIPT_URL is only needed when a proxy serves external_api.js from
    | somewhere other than https://{JITSI_DOMAIN}/external_api.js.
    |
    */

    'jitsi' => [
        'domain' => $jitsiDomain,
        'script_url' => env('JITSI_SCRIPT_URL', 'https://'.$jitsiDomain.'/external_api.js'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
