<?php

return [
    'version' => 22,
    'default' => env('APP_DEFAULT_LOCALE', 'az'),
    'supported' => array_filter(explode(',', env('APP_SUPPORTED_LOCALES', 'az,en,ru'))),
    'labels' => [
        'az' => 'Azərbaycan',
        'en' => 'English',
        'ru' => 'Русский',
    ],
];
