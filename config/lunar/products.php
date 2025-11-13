<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Low Stock Threshold
    |--------------------------------------------------------------------------
    |
    | The default threshold for determining when a product is considered
    | to be low in stock. This value is used by the inventory management
    | system to alert vendors.
    |
    */
    'low_stock_threshold' => env('LUNAR_LOW_STOCK_THRESHOLD', 10),
];
