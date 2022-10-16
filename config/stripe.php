
<?php
return [
    'secret_key' => env(
        'STRIPE_SECRET_KEY',
        env('STRIPE_SECRET_KEY', null)
    ),
    'public_key' => env(
        'STRIPE_PUBLIC_KEY',
        env('STRIPE_PUBLIC_KEY', null)
    ),
];
