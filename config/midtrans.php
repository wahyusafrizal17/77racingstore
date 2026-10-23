<?php

return [
    'server_key' => env('MIDTRANS_SERVER_KEY'),
    'client_key' => env('MIDTRANS_CLIENT_KEY'),
    'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
    'is_production' => filter_var(env('MIDTRANS_IS_PRODUCTION', false), FILTER_VALIDATE_BOOL),
    'is_sanitized' => filter_var(env('MIDTRANS_IS_SANITIZED', true), FILTER_VALIDATE_BOOL),
    'is_3ds' => filter_var(env('MIDTRANS_IS_3DS', true), FILTER_VALIDATE_BOOL),

    /**
     * Tampilkan tombol bayar Midtrans di UI reseller.
     * Matikan dulu saat merchant Midtrans masih dalam proses pendaftaran.
     */
    'payments_enabled' => filter_var(env('MIDTRANS_PAYMENTS_ENABLED', false), FILTER_VALIDATE_BOOL),

    /** Rekening penerima otomatis saat pembayaran Midtrans lunas (opsional — fallback rekening utama gudang). */
    'keuangan_rekening_id' => env('MIDTRANS_KEUANGAN_REKENING_ID'),
];
