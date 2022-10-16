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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'aws_access_key' => env('AWS_ACCESS_KEY_ID'),
    'aws_secret_key' => env('AWS_SECRET_ACCESS_KEY'),
    'aws_bucket' => env('AWS_BUCKET'),
    'folder_livestream_1' => env('AWS_FOLDER_LIVESTREAM_1', 'recordings'),
    'folder_livestream_2' => env('AWS_FOLDER_LIVESTREAM_2', 'raw'),
    'link_service_back' => env('LINK_SERVICE_BE', 'https://localhost/api/'),
    'link_service_front' => env('LINK_SERVICE_FE', 'https://localhost/'),
    'link_service_front_shop' => env('LINK_SERVICE_FE_SHOP', 'https://localhost/'),
    'link_service_front_cms' => env('LINK_SERVICE_CMS', 'https://localhost/'),
    'link_s3' => env('LINK_S3', 'https://localhost/'),
    'visibility' => env('VISIBILITY', 'public'),

    'host_nodejs' => env('HOST_NODEJS', 'https://localhost'),

    'agora_app_id' => env('AGORA_APP_ID'),
    'agora_app_certificate' => env('AGORA_APP_CERTIFICATE'),
    'agora_channel_name_length' => env('AGORA_CHANNEL_NAME_LENGTH', 16),
    'agora_time_expire' => env('AGORA_TIME_EXPIRE', 3600),
    'agora_customer_id' => env('AGORA_CUSTOMER_ID'),
    'agora_customer_secret' => env('AGORA_CUSTOMER_SECRET'),
    'agora_version' => env('AGORA_VERSION', '006'),
];
