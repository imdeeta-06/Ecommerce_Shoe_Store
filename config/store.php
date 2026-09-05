<?php

return [
    // Dữ liệu mô phỏng phục vụ đồ án. Không dùng để phát hành chứng từ thuế thật.
    'is_demo' => true,
    'brand_name' => 'Liên Hoa',
    'legal_name' => 'Liên Hoa (dữ liệu mô phỏng)',
    'tax_code' => '0312345678',
    'business_registration' => 'TMĐT-2026',
    'address' => '123 Đường Bạch Đằng, Phường Bến Thành, Thành phố Hồ Chí Minh',
    'phone' => '1800 2235',
    'email' => 'lienhoashop.pg@gmail.com',
    'privacy_email' => 'lienhoashop.pg@gmail.com',
    'support_hours' => '07:00–21:00 hằng ngày',
    'terms_version' => 'v2.0-2026-08-27',
    'privacy_version' => 'v2.0-2026-08-27',
    'invoice' => [
        'type' => 'vat_invoice',
        'seller_tax_method' => 'credit',
        'prices_are_gross' => true,
        'series_suffix' => 'LI',
    ],
    'bank' => [
        'code' => 'MB',
        'name' => 'MB Bank (Ngân hàng Quân Đội)',
        'account_number' => '19036789999',
        'account_name' => 'CUA HANG LIEN HOA',
    ],
];
