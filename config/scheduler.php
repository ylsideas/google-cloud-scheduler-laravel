<?php

return [

    'project_id' => env('GOOGLE_CLOUD_SCHEDULE_PROJECT_ID', ''),

    'location' => env('GOOGLE_CLOUD_SCHEDULE_LOCATION', ''),

    'auth' => 'ocid',

    'options' => [
        'credentials' => 'path/to/your/keyfile',
    ],

    'domain' => env('GOOGLE_CLOUD_SCHEDULE_DOMAIN'),

    'drivers' => [
        'ocid' => [
            'type' => 'ocid',
            'service_account' => env('GOOGLE_CLOUD_SCHEDULE_SERVICE_ACCOUNT', ''),
        ],

        'appengine' => [
            'type' => 'appengine',
        ],
    ],
];
