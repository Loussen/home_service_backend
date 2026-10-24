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
    /** Keep widening (same category) until at least this many unique providers. */
    'search_min_results' => (int) env('SEARCH_MIN_RESULTS', 12),
    'max_category_tags' => (int) env('MAX_CATEGORY_TAGS', 3),
    'otp_ttl_minutes' => (int) env('OTP_TTL_MINUTES', 5),
    'otp_max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),
    'otp_send_max' => (int) env('OTP_SEND_MAX', 3),
    'otp_send_window_minutes' => (int) env('OTP_SEND_WINDOW_MINUTES', 5),
    'otp_resend_seconds' => (int) env('OTP_RESEND_SECONDS', 30),
    /** Login phone mask: +994 | 50/55/… | 7 digits */
    'phone_prefix' => env('PHONE_PREFIX', '+994'),
    'phone_operator_codes' => array_values(array_filter(array_map(
        static fn (string $c) => preg_replace('/\D+/', '', $c) ?: null,
        explode(',', (string) env('PHONE_OPERATOR_CODES', '50,55,70,77,99,10,51'))
    ))),
    'phone_subscriber_digits' => (int) env('PHONE_SUBSCRIBER_DIGITS', 7),
    // Local: 123456. Production always random. Set false to test random codes locally.
    'otp_allow_debug_code' => filter_var(env('OTP_ALLOW_DEBUG_CODE', true), FILTER_VALIDATE_BOOL),
    /**
     * App Store / TestFlight review phones — always OTP 123456 (even in production).
     * Comma-separated E.164, e.g. +994501111111
     */
    'otp_review_phones' => array_values(array_filter(array_map(
        static fn (string $p) => preg_replace('/\s+/', '', $p) ?: null,
        explode(',', (string) env('OTP_REVIEW_PHONES', '+994501111111'))
    ))),
    /** Family request TTL (hours). Default + picker options. */
    'request_ttl_default_hours' => (int) env('REQUEST_TTL_DEFAULT_HOURS', 1),
    'request_ttl_options_hours' => array_values(array_filter(array_map(
        'intval',
        explode(',', env('REQUEST_TTL_OPTIONS_HOURS', '1,3,6'))
    ))),
    'wallet_packages' => array_values(array_filter(array_map(
        'floatval',
        explode(',', env('WALLET_PACKAGES', '10,30,50'))
    ))),
    /**
     * StoreKit / Play Billing product map.
     * Create matching Consumable (iOS) / one-time (Android) products with these IDs.
     * credit may exceed pay later (volume bonus).
     */
    'wallet_iap_products' => [
        [
            'product_id' => env('IAP_PRODUCT_10', 'mysancho_wallet_10'),
            'pay' => 10,
            'credit' => (float) env('IAP_CREDIT_10', 10),
        ],
        [
            'product_id' => env('IAP_PRODUCT_30', 'mysancho_wallet_30'),
            'pay' => 30,
            'credit' => (float) env('IAP_CREDIT_30', 36),
        ],
        [
            'product_id' => env('IAP_PRODUCT_50', 'mysancho_wallet_50'),
            'pay' => 50,
            'credit' => (float) env('IAP_CREDIT_50', 65),
        ],
    ],
    'android_package_name' => env('ANDROID_PACKAGE_NAME', 'az.homeservice.home_service_app'),
    'apple_iap_shared_secret' => env('APPLE_IAP_SHARED_SECRET', ''),
    'apple_iap_bundle_id' => env('APPLE_IAP_BUNDLE_ID', 'az.homeservice.homeServiceApp'),
    /** App Store Server API (StoreKit 2 / JWS). Create key in App Store Connect → Users and Access → Keys. */
    'apple_iap_issuer_id' => env('APPLE_IAP_ISSUER_ID', ''),
    'apple_iap_key_id' => env('APPLE_IAP_KEY_ID', ''),
    'apple_iap_private_key' => env('APPLE_IAP_PRIVATE_KEY', ''), // path to .p8 or PEM contents with \n
    /** Play Developer API JSON key path (can reuse FCM SA if Play API enabled on same project). */
    'google_play_credentials' => env('GOOGLE_PLAY_CREDENTIALS', env('FCM_CREDENTIALS', '')),
    /** Local/dev only: credit without store verify when secrets missing. Never enable in production. */
    'iap_allow_unverified' => filter_var(env('IAP_ALLOW_UNVERIFIED', false), FILTER_VALIDATE_BOOL),
    'feature_voice_search' => filter_var(env('FEATURE_VOICE_SEARCH', true), FILTER_VALIDATE_BOOL),
    'feature_maps' => filter_var(env('FEATURE_MAPS', true), FILTER_VALIDATE_BOOL),
    'feature_push' => filter_var(env('FEATURE_PUSH', true), FILTER_VALIDATE_BOOL),
    /**
     * App update gates (semver, e.g. 1.0.0). Empty = disabled.
     * Force wins when current < force_min. Soft prompts when current < soft_min.
     */
    'ios_soft_min_version' => env('IOS_SOFT_MIN_VERSION', ''),
    'ios_force_min_version' => env('IOS_FORCE_MIN_VERSION', ''),
    'android_soft_min_version' => env('ANDROID_SOFT_MIN_VERSION', ''),
    'android_force_min_version' => env('ANDROID_FORCE_MIN_VERSION', ''),
    /** Latest published marketing version (semver). Admin updates on each store release. */
    'ios_current_version' => env('IOS_CURRENT_VERSION', '1.0.0'),
    'android_current_version' => env('ANDROID_CURRENT_VERSION', '1.0.0'),
    /** Relative public disk paths or absolute URLs for voice greeting (az/en/ru). */
    'voice_greeting_az' => env('VOICE_GREETING_AZ', ''),
    'voice_greeting_en' => env('VOICE_GREETING_EN', ''),
    'voice_greeting_ru' => env('VOICE_GREETING_RU', ''),
    'voice_accepted_az' => env('VOICE_ACCEPTED_AZ', ''),
    'voice_accepted_en' => env('VOICE_ACCEPTED_EN', ''),
    'voice_accepted_ru' => env('VOICE_ACCEPTED_RU', ''),
    // Local/dev: log OTP instead of SMS. Production: sms (LSIM QuickSMS).
    'otp_driver' => env('OTP_DRIVER', 'log'),
    'lsim' => [
        'login' => env('LSIM_LOGIN', ''),
        'password' => env('LSIM_PASSWORD', ''),
        'sender' => env('LSIM_SENDER', ''),
        'base_url' => env('LSIM_BASE_URL', 'https://apps.lsim.az/quicksms'),
        'timeout' => (int) env('LSIM_TIMEOUT', 20),
        // {code} {ttl} — unicode SMS (Azerbaijani letters OK)
        'otp_template' => env(
            'LSIM_OTP_TEMPLATE',
            'My Sancho kodunuz: {code}. {ttl} deqiqe etibarlidir.'
        ),
    ],
    // Process voice requests inline (no queue worker needed)
    'audio_sync' => filter_var(env('HOMESERVICE_AUDIO_SYNC', true), FILTER_VALIDATE_BOOL),
    'google_maps_api_key' => env('GOOGLE_MAPS_API_KEY', ''),
    // Web Maps JavaScript API (HTTP referrer). Empty = Google Map iframe (mobile/server key JS-də işləmir).
    'google_maps_browser_key' => env('GOOGLE_MAPS_BROWSER_KEY', ''),
    // Public store links for web “Get the app” (empty = show “Coming soon”).
    'app_store_url' => env('APP_STORE_URL', ''),
    'play_store_url' => env('PLAY_STORE_URL', ''),
    // Local/dev: send push inline (no queue worker). Production: false + queue:work
    'push_sync' => filter_var(env('HOMESERVICE_PUSH_SYNC', true), FILTER_VALIDATE_BOOL),
    'fcm' => [
        'credentials' => env('FCM_CREDENTIALS', ''),
        'project_id' => env('FCM_PROJECT_ID', ''),
        'client_email' => env('FCM_CLIENT_EMAIL', ''),
        'private_key' => env('FCM_PRIVATE_KEY', ''),
    ],
];
