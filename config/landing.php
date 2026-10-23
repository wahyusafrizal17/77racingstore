<?php

return [

    'whatsapp' => env('LANDING_WHATSAPP', '6285353300103'),

    'email' => env('LANDING_EMAIL', 'info@77racing.com'),

    'hours' => env('LANDING_HOURS', 'Setiap hari · 08.00 – 17.00'),

    'instagram' => env('LANDING_INSTAGRAM', 'https://instagram.com/77racing'),

    'tiktok' => env('LANDING_TIKTOK', 'https://www.tiktok.com/@77racing'),

    'facebook' => env('LANDING_FACEBOOK', 'https://www.facebook.com/77racing'),

    'youtube_embed_id' => env('LANDING_YOUTUBE_EMBED_ID', 'Xn3Rz12ZPkM'),

    'hero_autoplay_ms' => (int) env('LANDING_HERO_AUTOPLAY_MS', 6000),

    'partner_marquee_duration' => (int) env('LANDING_PARTNER_MARQUEE_DURATION', 45),

    'hero_slides' => [
        [
            'image' => 'images/landing/slider-1.png',
            'headline_1' => 'Workshop',
            'headline_2' => '77racing.',
            'headline_2_accent' => true,
            'subheadline' => 'Toko rekanan parts performance terpercaya.',
            'description' => 'UMA Racing, TDR, Motul, Daytona, RCB & partner resmi lainnya — lengkap dengan Dyno Room untuk tuning presisi.',
            'image_position' => 'center center',
        ],
        [
            'image' => 'images/landing/hero-team.jpg',
            'headline_1' => 'Built for',
            'headline_2' => 'Speed.',
            'headline_2_accent' => true,
            'subheadline' => 'Trusted for Every Ride.',
            'description' => 'Tim teknisi berpengalaman siap meningkatkan performa motor Anda — hadir di 4 cabang: Bandung, Depok, Bogor & Bekasi.',
            'image_position' => 'center top',
        ],
    ],

    'partner_cards' => [
        [
            'name' => 'UMA Racing',
            'logo' => 'images/landing/partners/uma-logo.png',
            'color' => '#7B2D8E',
            'description' => 'Racing parts & sistem pengapian untuk peningkatan performa mesin 2-tak dan 4-tak.',
        ],
        [
            'name' => 'TDR',
            'logo' => 'images/landing/partners/tdr-logo.jpg',
            'subtitle' => 'High Performance Technology',
            'color' => '#111111',
            'description' => 'Knalpot, piston, dan komponen racing berkualitas untuk rider yang mengejar performa maksimal.',
        ],
        [
            'name' => 'Motul',
            'logo' => 'images/landing/partners/motul-logo.png',
            'color' => '#C8102E',
            'description' => 'Oli & pelumas premium untuk proteksi mesin, performa konsisten, dan daya tahan ekstrem.',
        ],
        [
            'name' => 'Daytona',
            'logo' => 'images/landing/partners/daytona-logo.jpg',
            'color' => '#E87722',
            'description' => 'Parts performance terpercaya untuk modifikasi harian hingga setup track day.',
        ],
        [
            'name' => 'RCB',
            'logo' => 'images/landing/partners/rcb-logo.jpg',
            'color' => '#E8192C',
            'description' => 'Sistem pengereman & aksesoris racing berstandar internasional.',
        ],
        [
            'name' => 'BRT',
            'logo' => 'images/landing/partners/brt-logo.jpg',
            'subtitle' => 'Bintang Racing Team',
            'color' => '#E8192C',
            'description' => 'Komponen racing proven di circuit — dirancang untuk rider kompetitif.',
        ],
        [
            'name' => 'X1 Racing',
            'logo' => 'images/landing/partners/x1-logo.jpg',
            'subtitle' => 'Performance',
            'color' => '#111111',
            'description' => 'Performance parts untuk rider yang mengejar lap time dan respons throttle terbaik.',
        ],
        [
            'name' => 'RX King Racing',
            'logo' => 'images/landing/partners/rx-logo.jpg',
            'color' => '#111111',
            'description' => 'Specialist parts RX King — heritage performance motor legendaris Indonesia.',
        ],
    ],

    'testimonials' => [
        [
            'quote' => 'Setup suspensi dan ECU-nya beda banget. Daily ride jadi lebih stabil, track day juga percaya diri. Prosesnya transparan dari awal.',
            'initials' => 'RP',
            'name' => 'Rizky Pratama',
            'motor' => 'Yamaha R15 V3',
        ],
        [
            'quote' => 'Custom build sesuai brief — agresif tapi tetap usable. Tim-nya detail banget saat konsultasi, nggak asal modif.',
            'initials' => 'DH',
            'name' => 'Dimas Hartono',
            'motor' => 'Honda CBR250RR',
        ],
        [
            'quote' => 'Performance upgrade + tuning hasilnya konsisten. Garansi layanan dan follow-up-nya bikin tenang. Recommended banget.',
            'initials' => 'AW',
            'name' => 'Andi Wijaya',
            'motor' => 'Kawasaki Ninja 250',
        ],
        [
            'quote' => 'Upgrade knalpot dan karburator di Depok, hasilnya sesuai ekspektasi. Estimasi biaya jelas, nggak ada biaya tambahan di tengah jalan.',
            'initials' => 'FN',
            'name' => 'Fajar Nugroho',
            'motor' => 'Honda PCX 160',
        ],
        [
            'quote' => 'Servis berkala dan modif ringan di cabang Bogor. Teknisinya ramah, jelasin tiap part yang dipasang. Motor jadi lebih responsif.',
            'initials' => 'SA',
            'name' => 'Siti Aulia',
            'motor' => 'Yamaha NMAX',
        ],
        [
            'quote' => 'Dyno tuning setelah upgrade piston — power naik, konsumsi tetap wajar. Worth it banget buat yang serius main performa.',
            'initials' => 'BR',
            'name' => 'Budi Raharjo',
            'motor' => 'Honda CB150R',
        ],
    ],

    'branches' => [
        [
            'city' => 'Bandung',
            'code' => 'BTP-01',
            'address' => 'Jl. Pasir Panjang, Cilampeni, Kec. Katapang, Kabupaten Bandung, Jawa Barat 40921',
            'phone' => '0853-5330-0103',
            'whatsapp' => '6285353300103',
            'maps_query' => 'Jl. Pasir Panjang, Cilampeni, Kec. Katapang, Kabupaten Bandung, Jawa Barat 40921',
        ],
        [
            'city' => 'Depok',
            'code' => 'CSK-02',
            'address' => 'Jl. Raya Bogor Jl. Nusa Indah No.27, Cisalak Ps., Kec. Cimanggis, Kota Depok, Jawa Barat 16416',
            'phone' => '0853-5330-0102',
            'whatsapp' => '6285353300102',
            'maps_query' => 'Jl. Raya Bogor Jl. Nusa Indah No.27, Cisalak Ps., Kec. Cimanggis, Kota Depok, Jawa Barat 16416',
        ],
        [
            'city' => 'Bogor',
            'code' => 'CLK-03',
            'address' => 'Jl. Brigjen Saptadji Hadiprawira No.9, RT.01/RW.09, Cilendek Bar., Kec. Bogor Bar., Kota Bogor, Jawa Barat 16112',
            'phone' => '0813-2877-3881',
            'whatsapp' => '6281328773881',
            'maps_query' => 'Jl. Brigjen Saptadji Hadiprawira No.9, RT.01/RW.09, Cilendek Bar., Kec. Bogor Bar., Kota Bogor, Jawa Barat 16112',
        ],
        [
            'city' => 'Bekasi',
            'code' => 'BKS-04',
            'address' => 'Jl. Raya Jatimekar No.30, RT.002/RW.016, Jatimekar, Kec. Jatiasih, Kota Bks, Jawa Barat 17422',
            'phone' => '0853-5330-0103',
            'whatsapp' => '6285353300103',
            'maps_query' => 'Jl. Raya Jatimekar No.30, RT.002/RW.016, Jatimekar, Kec. Jatiasih, Kota Bks, Jawa Barat 17422',
        ],
    ],

];
