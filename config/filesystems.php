<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Upload disk (bukti setoran, dll.)
    |--------------------------------------------------------------------------
    |
    | Jangan panggil env('FILES_UPLOAD_DISK') dari controller/view setelah
    | `php artisan config:cache`. Gunakan config('filesystems.upload_disk').
    |
    */

    'upload_disk' => env('FILES_UPLOAD_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

        /*
         * Biznet/NOS (S3-compatible)
         * Endpoint contoh: https://nos.wjv-1.neo.id
         */
        'biznet' => [
            'driver' => 's3',
            'key' => env('BIZNET_ACCESS_KEY_ID'),
            'secret' => env('BIZNET_SECRET_ACCESS_KEY'),
            'region' => env('BIZNET_DEFAULT_REGION', 'wjv-1'),
            'bucket' => env('BIZNET_BUCKET'),
            // Jika kamu punya domain CDN/public base URL, isi BIZNET_URL. Kalau kosong, Storage::url pakai endpoint+bucket.
            'url' => env('BIZNET_URL'),
            'endpoint' => env('BIZNET_ENDPOINT', 'https://nos.wjv-1.neo.id'),
            'use_path_style_endpoint' => env('BIZNET_USE_PATH_STYLE_ENDPOINT', true),
            'visibility' => 'public',
            'throw' => true,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
