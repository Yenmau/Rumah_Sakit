<?php
/**
 * Security Configuration (Secure Programming COMP6695)
 */

return [
    'session' => [
        'name' => 'RS_SESSION_ID',
        'lifetime' => 7200,             // 2 hours
        'idle_timeout' => 1800,         // 30 minutes of inactivity
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax',
    ],

    'auth' => [
        'max_failed_attempts' => 5,
        'lockout_duration_seconds' => 900, // 15 minutes lockout
        'bcrypt_cost' => 12,
    ],

    'headers' => [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Content-Security-Policy' => "default-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; img-src 'self' data:; connect-src 'self' http://localhost:* http://127.0.0.1:*;",
    ]
];
