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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    'whatsapp' => [
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
        'template_name' => env('WHATSAPP_TEMPLATE_NAME'),
        'reminder_template_name' => env('WHATSAPP_REMINDER_TEMPLATE_NAME'),
        'thankyou_template_name' => env('WHATSAPP_THANKYOU_TEMPLATE_NAME', 'thank_fifi_kiki'),
        'template_language' => env('WHATSAPP_TEMPLATE_LANGUAGE', 'en'),
        'template_body_param_name' => env('WHATSAPP_TEMPLATE_BODY_PARAM_NAME', 'n'),
        'template_url_button' => env('WHATSAPP_TEMPLATE_URL_BUTTON', true),
        'template_url_button_index' => (int) env('WHATSAPP_TEMPLATE_URL_BUTTON_INDEX', 0),
        /*
         * full = send complete https URL in button {{1}} (recommended).
         * token = send only the 5-letter access token (Meta URL must end with /access-card/{{1}}).
         */
        'template_button_url_mode' => env('WHATSAPP_TEMPLATE_BUTTON_URL_MODE', 'full'),
        'graph_version' => env('WHATSAPP_GRAPH_VERSION', 'v21.0'),
        /*
         * Public HTTPS origin for per-guest access card images in WhatsApp headers.
         * Set on staging/production (e.g. https://staging.fifiandkiki.com). Leave unset on local Herd.
         */
        'public_app_url' => env('WHATSAPP_PUBLIC_APP_URL'),
    ],

];
