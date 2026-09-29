<?php

/*
|--------------------------------------------------------------------------
| Plans & billing — single source of truth for prices
|--------------------------------------------------------------------------
| All amounts are in paise (₹1 = 100 paise). Prices are per user per month
| and exclude GST. Landing, pricing, billing and upgrade pages all read
| from here — never hardcode a price in a view.
|
| Module lists per plan live in App\Services\ModuleService.
*/

return [

    'currency' => 'INR',

    // Set to 0 if the business is not GST-registered.
    'gst_percent' => (float) env('BILLING_GST_PERCENT', 18),

    // A paid plan can be cancelled for a full refund within this many days of payment.
    'refund_window_days' => 7,

    // Max refunds an organization can ever receive (null = no limit).
    'refund_limit_per_org' => env('BILLING_REFUND_LIMIT') !== null ? (int) env('BILLING_REFUND_LIMIT') : null,

    'free' => [
        'label'     => 'Free',
        'max_users' => 10,
    ],

    'pro' => [
        'label'     => 'Pro',
        'min_seats' => 5,
        'periods'   => [
            'monthly' => ['months' => 1,  'price_per_user' => 19900],  // ₹199 /user/month
            'yearly'  => ['months' => 12, 'price_per_user' => 14900],  // ₹149 /user/month, billed yearly
        ],
    ],

    // Contact sales — no self-serve checkout.
    'enterprise' => [
        'label'               => 'Enterprise',
        'from_price_per_user' => 34900,  // ₹349 /user/month
        'min_seats'           => 50,
    ],

    // Printed on payment receipts. Receipts are titled "Tax Invoice" only when a GSTIN is set.
    'seller' => [
        'name'    => env('BILLING_SELLER_NAME', 'OutraqHQ'),
        'address' => env('BILLING_SELLER_ADDRESS'),
        'gstin'   => env('BILLING_SELLER_GSTIN'),
        'sac'     => env('BILLING_SAC_CODE'),  // confirm the correct SAC code with your accountant
        'email'   => env('BILLING_SELLER_EMAIL', env('MAIL_FROM_ADDRESS')),
    ],

];
