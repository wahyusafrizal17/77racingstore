<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    | WACloud — kirim invoice PDF ke WhatsApp (https://app.wacloud.id/api/v1/messages).
    | WACLOUD_DEVICE_ID: dari dashboard WACLOUD (device/session terhubung).
    | Jangan commit API key; pakai .env saja.
    */
    'wacloud' => [
        'api_key' => env('WACLOUD_API_KEY'),
        'device_id' => env('WACLOUD_DEVICE_ID'),
        'device_id_bandung' => env('WACLOUD_DEVICE_ID_BANDUNG'),
        'device_id_bogor' => env('WACLOUD_DEVICE_ID_BOGOR'),
        'device_id_depok' => env('WACLOUD_DEVICE_ID_DEPOK'),
        'device_id_bekasi' => env('WACLOUD_DEVICE_ID_BEKASI'),
        'base_url' => env('WACLOUD_BASE_URL', 'https://app.wacloud.id/api/v1'),
    ],

    /*
    | Monitor TV — TTS ElevenLabs (MP3 di public/elevenlabs-tts, tanpa storage:link).
    | Jangan commit API key; isi hanya lewat .env.
    | Opsional: ELEVENLABS_SPEECH_SPEED (0.65–1.0, default 0.82) untuk tempo bicara natural di TV.
    | Akun gratis: API biasanya menolak "library voices" (HTTP 402, paid_plan_required) — upgrade
    | paket ElevenLabs atau gunakan voice yang diizinkan untuk tier Anda.
    */
    'elevenlabs' => [
        'api_key' => env('ELEVENLABS_API_KEY'),
        'voice_id' => env('ELEVENLABS_VOICE_ID'),
        /** Voice khusus monitor TV pesanan reseller (fallback ke voice_id jika kosong). */
        'voice_id_reseller' => env('ELEVENLABS_VOICE_ID_RESELLER'),
        'timeout' => (int) env('ELEVENLABS_TIMEOUT', 45),
        /** Kecepatan bicara TTS (0.65–1.05). Di bawah 1 = lebih pelan dan natural untuk monitor TV. */
        'speech_speed' => (float) env('ELEVENLABS_SPEECH_SPEED', 0.82),
    ],

    'landing' => [
        'whatsapp' => env('LANDING_WHATSAPP', '6285353300103'),
    ],

];
