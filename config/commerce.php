<?php

return [
    'shipping_fees' => [
        'seven_eleven' => 80,
        'family_mart' => 80,
        'postal' => 120,
        'self_pickup' => 0,
    ],
    'recipient_link_ttl_hours' => (int) env('ORDER_RECIPIENT_LINK_TTL_HOURS', 168),
];
