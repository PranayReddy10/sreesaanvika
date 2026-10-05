<?php

/**
 * Shop-wide defaults. Anything the shop owner should be able to change lives
 * in Settings instead; this is only the fallback when they have not.
 */
return [
    'currency'           => env('SHOP_CURRENCY', 'INR'),
    'free_shipping_from' => (float) env('SHOP_FREE_SHIPPING_FROM', 2999),
    'flat_shipping'      => 99.0,
    'cod_fee'            => 0.0,
    'low_stock_at'       => 3,

    /*
     * The first admin account, created by `php artisan db:seed`. Read through
     * config rather than env() directly so it still works on a host where the
     * config has been cached.
     */
    'admin' => [
        'name'     => env('ADMIN_NAME', 'OJASVI'),
        'email'    => env('ADMIN_EMAIL', 'admin@ojasvidrapes.in'),
        'password' => env('ADMIN_PASSWORD', 'change-this-now'),
    ],
];
