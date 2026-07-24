<?php

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

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
    ],

    'bcv_rate' => [
        // OWF-321: pydolarve.org never resolved (DNS down, confirmed from prod + multiple
        // networks) — switched to ve.dolarapi.com, verified live and returning clean JSON.
        'url' => env('BCV_RATE_URL', 'https://ve.dolarapi.com/v1/dolares/oficial'),
        // OWF-329: kept as a fallback in BcvRateFetcher in case pydolarve.org ever resolves
        // again — never verified live (DNS was down for the whole time it was primary).
        'fallback_url' => env('BCV_RATE_FALLBACK_URL', 'https://pydolarve.org/api/v1/dollar?page=bcv'),
        // OWF-330: same API family, but this one reports VES per EUR directly (not USD per
        // EUR) — BcvRateFetcher derives the USD-relative EUR rate this system needs by
        // dividing the VES/USD rate by this VES/EUR rate.
        'eur_ves_url' => env('BCV_RATE_EUR_VES_URL', 'https://ve.dolarapi.com/v1/euros/oficial'),
    ],

];
