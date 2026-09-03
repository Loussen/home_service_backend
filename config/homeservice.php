<?php

return [
    'welcome_bonus' => (float) env('WALLET_WELCOME_BONUS', 10),
    'bump_up_fee' => (float) env('WALLET_BUMP_UP_FEE', 1),
    'urgent_fee' => (float) env('WALLET_URGENT_FEE', 2),
    'urgent_hours' => (int) env('WALLET_URGENT_HOURS', 2),
    'urgent_daily_limit' => (int) env('WALLET_URGENT_DAILY_LIMIT', 3),
    'urgent_radius_km' => (float) env('WALLET_URGENT_RADIUS_KM', 5),
    'vip_fee' => (float) env('WALLET_VIP_FEE', 15),
    'verified_fee' => (float) env('WALLET_VERIFIED_FEE', 5),
    'vip_days' => (int) env('WALLET_VIP_DAYS', 30),
    'connect_free_quota' => (int) env('CONNECT_FREE_QUOTA', 5),
    'connect_free_days' => (int) env('CONNECT_FREE_DAYS', 30),
    'connect_daily_limit' => (int) env('CONNECT_DAILY_LIMIT', 10),
    'connect_fee' => (float) env('CONNECT_FEE', 0.5),
    'bump_hours' => (int) env('WALLET_BUMP_HOURS', 24),
    'bump_daily_limit' => (int) env('WALLET_BUMP_DAILY_LIMIT', 2),
    'bump_boost_km' => (float) env('WALLET_BUMP_BOOST_KM', 8),
    'search_radius_km' => (float) env('SEARCH_RADIUS_KM', 50),
    'max_category_tags' => (int) env('MAX_CATEGORY_TAGS', 3),
    'otp_ttl_minutes' => (int) env('OTP_TTL_MINUTES', 5),
    'otp_max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),
    'otp_send_max' => (int) env('OTP_SEND_MAX', 3),
    'otp_send_window_minutes' => (int) env('OTP_SEND_WINDOW_MINUTES', 5),
    'otp_resend_seconds' => (int) env('OTP_RESEND_SECONDS', 30),
    // Local: 123456. Production always random. Set false to test random codes locally.
    'otp_allow_debug_code' => filter_var(env('OTP_ALLOW_DEBUG_CODE', true), FILTER_VALIDATE_BOOL),
    'wallet_packages' => array_values(array_filter(array_map(
        'floatval',
        explode(',', env('WALLET_PACKAGES', '10,30,50'))
    ))),
    'feature_voice_search' => filter_var(env('FEATURE_VOICE_SEARCH', true), FILTER_VALIDATE_BOOL),
    'feature_maps' => filter_var(env('FEATURE_MAPS', true), FILTER_VALIDATE_BOOL),
    'feature_push' => filter_var(env('FEATURE_PUSH', true), FILTER_VALIDATE_BOOL),
    // Local/dev: log OTP instead of SMS
    'otp_driver' => env('OTP_DRIVER', 'log'),
    // Process voice requests inline (no queue worker needed)
    'audio_sync' => filter_var(env('HOMESERVICE_AUDIO_SYNC', true), FILTER_VALIDATE_BOOL),
    'google_maps_api_key' => env('GOOGLE_MAPS_API_KEY', ''),
    // Web Maps JavaScript API (HTTP referrer). Empty = Google Map iframe (mobile/server key JS-də işləmir).
    'google_maps_browser_key' => env('GOOGLE_MAPS_BROWSER_KEY', ''),
    // Local/dev: send push inline (no queue worker). Production: false + queue:work
    'push_sync' => filter_var(env('HOMESERVICE_PUSH_SYNC', true), FILTER_VALIDATE_BOOL),
    'fcm' => [
        'credentials' => env('FCM_CREDENTIALS', ''),
        'project_id' => env('FCM_PROJECT_ID', ''),
        'client_email' => env('FCM_CLIENT_EMAIL', ''),
        'private_key' => env('FCM_PRIVATE_KEY', ''),
    ],
];
