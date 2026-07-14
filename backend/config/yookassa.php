<?php

declare(strict_types=1);

return [

    'shop_id' => env('YOOKASSA_SHOP_ID'),
    'secret_key' => env('YOOKASSA_SECRET_KEY'),
    'webhook_secret' => env('YOOKASSA_WEBHOOK_SECRET'),
    'base_url' => env('YOOKASSA_BASE_URL', 'https://api.yookassa.ru/v3'),

    'verify_webhook_ip' => (bool) env('YOOKASSA_VERIFY_WEBHOOK_IP', true),

    // Официальные диапазоны ЮKassa для вебхуков, см. docs/rules/04-payments-escrow.md.
    'webhook_ip_ranges' => explode(',', (string) env(
        'YOOKASSA_WEBHOOK_IP_RANGES',
        '185.71.76.0/27,185.71.77.0/27,77.75.153.0/25,77.75.156.11,77.75.156.35,77.75.154.128/25,2a02:5180::/32',
    )),

    'commission_basis_points' => (int) env('YOOKASSA_COMMISSION_BASIS_POINTS', 500),
    'escrow_hold_days' => (int) env('YOOKASSA_ESCROW_HOLD_DAYS', 7),
    'payouts_enabled' => (bool) env('YOOKASSA_PAYOUTS_ENABLED', false),

    'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),
];
