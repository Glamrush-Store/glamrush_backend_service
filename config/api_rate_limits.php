<?php

return [
    'general_per_minute' => (int) env('API_RATE_LIMIT_GENERAL_PER_MINUTE', 120),
    'catalog_per_minute' => (int) env('API_RATE_LIMIT_CATALOG_PER_MINUTE', 300),
    'login_per_minute' => (int) env('API_RATE_LIMIT_LOGIN_PER_MINUTE', 5),
    'onboarding_per_minute' => (int) env('API_RATE_LIMIT_ONBOARDING_PER_MINUTE', 5),
    'password_forgot_per_hour_per_email' => (int) env('API_RATE_LIMIT_PASSWORD_FORGOT_EMAIL_PER_HOUR', 3),
    'password_forgot_per_hour_per_ip' => (int) env('API_RATE_LIMIT_PASSWORD_FORGOT_IP_PER_HOUR', 10),
    'password_verify_per_request' => (int) env('API_RATE_LIMIT_PASSWORD_VERIFY_PER_REQUEST', 5),
    'password_reset_per_hour' => (int) env('API_RATE_LIMIT_PASSWORD_RESET_PER_HOUR', 5),
    'cart_mutations_per_minute' => (int) env('API_RATE_LIMIT_CART_MUTATIONS_PER_MINUTE', 30),
    'checkout_payment_per_minute' => (int) env('API_RATE_LIMIT_CHECKOUT_PAYMENT_PER_MINUTE', 10),
    'payment_verification_per_minute' => (int) env('API_RATE_LIMIT_PAYMENT_VERIFY_PER_MINUTE', 10),
    'webhook_per_minute_per_ip' => (int) env('API_RATE_LIMIT_WEBHOOK_IP_PER_MINUTE', 120),
    'webhook_per_minute_per_provider' => (int) env('API_RATE_LIMIT_WEBHOOK_PROVIDER_PER_MINUTE', 600),
    'newsletter_subscribe_per_hour_per_email' => (int) env('API_RATE_LIMIT_NEWSLETTER_EMAIL_PER_HOUR', 3),
    'newsletter_subscribe_per_hour_per_ip' => (int) env('API_RATE_LIMIT_NEWSLETTER_IP_PER_HOUR', 10),
    'newsletter_action_per_minute' => (int) env('API_RATE_LIMIT_NEWSLETTER_ACTION_PER_MINUTE', 10),
];
