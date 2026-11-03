<?php

return [
    'name' => 'SIMAS',
    'program' => 'Prodi Hukum Bisnis',
    'institution' => 'Universitas Siliwangi',

    // Format nomor surat. Placeholder: {seq} {code} {year} {month} {roman_month}
    // Contoh hasil: 001/UN58.HB/SKA/2026 -> SESUAIKAN dengan tata naskah dinas prodi.
    'number_format' => env('SIMAS_NUMBER_FORMAT', '{seq}/UN58.HB/{code}/{year}'),
    'number_pad' => 3,

    'pdf_max_kb' => (int) env('SIMAS_PDF_MAX_KB', 5120),
    'stats_ttl' => (int) env('SIMAS_STATS_TTL', 60),

    // Dokumen menyebut Admin juga dapat ACC, sedangkan modul verifikasi ditulis untuk Kaprodi.
    // Default: hanya Kaprodi. Ubah ke true jika admin boleh ACC (TTD tetap memakai TTD Kaprodi aktif).
    'admin_can_approve' => (bool) env('SIMAS_ADMIN_CAN_APPROVE', true),

    // Pembubuhan TTD otomatis pada PDF yang di-ACC (butuh setasign/fpdi).
    'signature' => [
        'width_mm' => 45,
        'margin_right_mm' => 20,
        'margin_bottom_mm' => 25,
    ],

    // Saran kategori surat masuk (datalist; tetap bisa diketik bebas).
    'incoming_categories' => [
        'Undangan', 'Edaran', 'Permohonan', 'Pemberitahuan', 'Kerja Sama', 'Lainnya',
    ],

    'seed_password' => env('SIMAS_SEED_PASSWORD', 'password'),
];
