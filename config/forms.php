<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Public Forms Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the list of allowed origin hosts that may submit public forms
    | via the API. Values should be lowercase hostnames. Wildcards are
    | supported using a leading '*.' to allow any subdomain, e.g. '*.example.com'.
    |
    */
    'allowed_origins' => [
        'primavip.co',
        'www.primavip.co',
        '*.primavip.co',
        '*.primaapp.com',
        '*.lovable.com',
        '*.lovable.dev',
        '*.lovable.app',

    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Email Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the email addresses that will receive form submissions.
    |
    */
    'notification_emails' => [
        'to' => env('FORMS_NOTIFICATION_TO', 'prima@primavip.co'),
        'cc' => explode(',', (string) env('FORMS_NOTIFICATION_CC', 'kevin@primavip.co,alex@primavip.co,patrick@primavip.co')),
        'bcc' => explode(',', (string) env('FORMS_NOTIFICATION_BCC', 'andru.weir@gmail.com')),
    ],
];
