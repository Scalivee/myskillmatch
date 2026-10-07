<?php

return [
    'scanner' => env('DOCUMENT_SCANNER', 'disabled'),
    'clamav_binary' => env('CLAMAV_BINARY', 'clamscan'),
    'midtrans_key' => env('MIDTRANS_SERVER_KEY', ''),
    'demo' => env('SKILLMATCH_DEMO', false),
    'categories' => ['Kampus', 'Komunitas', 'Hiburan'],
];
