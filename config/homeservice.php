<?php

return [
    'welcome_bonus' => (float) env('WALLET_WELCOME_BONUS', 10),
    'bump_up_fee' => (float) env('WALLET_BUMP_UP_FEE', 1),
    'urgent_fee' => (float) env('WALLET_URGENT_FEE', 2),
    'vip_fee' => (float) env('WALLET_VIP_FEE', 15),
    'verified_fee' => (float) env('WALLET_VERIFIED_FEE', 5),
    'vip_days' => (int) env('WALLET_VIP_DAYS', 30),
    'search_radius_km' => (float) env('SEARCH_RADIUS_KM', 15),
    'otp_ttl_minutes' => (int) env('OTP_TTL_MINUTES', 5),
    'otp_max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),
    // Local/dev: log OTP instead of SMS
    'otp_driver' => env('OTP_DRIVER', 'log'),
    // Process voice requests inline (no queue worker needed)
    'audio_sync' => filter_var(env('HOMESERVICE_AUDIO_SYNC', true), FILTER_VALIDATE_BOOL),
];
