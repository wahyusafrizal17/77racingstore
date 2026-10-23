<?php

return [
    'provider' => env('SHIPPING_PROVIDER', 'rajaongkir'),
    'rajaongkir' => [
        'api_key' => env('RAJAONGKIR_API_KEY'),
        'base_url' => env('RAJAONGKIR_BASE_URL', 'https://api.rajaongkir.com/starter'),
        // Fallback saja; origin utama diambil dari kota Head Office (Warehouse::headOffice()).
        'origin_city_id' => env('RAJAONGKIR_ORIGIN_CITY_ID'),
        'courier' => 'jnt',
    ],
    // Fallback when API not configured (dev)
    'fallback_services' => [
        ['service' => 'EZ', 'description' => 'J&T EZ (estimasi)', 'cost' => 15000, 'etd' => '2-3'],
        ['service' => 'REG', 'description' => 'J&T Regular (estimasi)', 'cost' => 18000, 'etd' => '2-4'],
    ],
];
