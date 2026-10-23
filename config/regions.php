<?php

return [
    'base_url' => env('INDONESIA_REGIONS_BASE_URL', 'https://wilayah.web.id/api'),
    'timeout' => (int) env('INDONESIA_REGIONS_TIMEOUT', 30),
    'retry_times' => (int) env('INDONESIA_REGIONS_RETRY_TIMES', 3),
    'retry_sleep_ms' => (int) env('INDONESIA_REGIONS_RETRY_SLEEP_MS', 500),
];
