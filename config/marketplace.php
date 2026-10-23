<?php

return [
    'warehouse_id' => env('MARKETPLACE_WAREHOUSE_ID'), // null = first warehouse
    'default_item_weight_gram' => (int) env('MARKETPLACE_ITEM_WEIGHT_GRAM', 1000),
    'free_shipping_minimum' => (int) env('MARKETPLACE_FREE_SHIPPING_MINIMUM', 150000),
    /** Potongan tetap dari ongkir J&T (hasil dibayar = max(0, ongkir − subsidi)). */
    'shipping_subsidy' => (int) env('MARKETPLACE_SHIPPING_SUBSIDY', 20000),
];
