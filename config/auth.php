<?php
/**
 * ============================================
 * Authentication Configuration
 * ============================================
 * Settings for user authentication,
 * sessions, and cookies.
 * ============================================
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Password
    |--------------------------------------------------------------------------
    | The master password for admin access.
    | In production, this should ALWAYS come from environment.
    */
    'password' => getenv('AUTH_PASSWORD') ?: 'GL_Admin',

    /*
    |--------------------------------------------------------------------------
    | Cookie Settings
    |--------------------------------------------------------------------------
    */
    'cookie' => [
        'name'     => 'admin_remember',
        'duration' => (int)(getenv('AUTH_COOKIE_DURATION') ?: 2592000), // 30 days
        'path'     => '/',
        'secure'   => false, // Set to true in production with HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Settings
    |--------------------------------------------------------------------------
    */
    'session' => [
        'name'     => 'prompt_manager_session',
        'lifetime' => 7200, // 2 hours
        'key'      => 'admin_logged_in',
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Salt
    |--------------------------------------------------------------------------
    | Used for hashing cookies and tokens.
    | MUST be unique per installation!
    */
    'salt' => getenv('AUTH_SALT') ?: 'default_salt_change_me_in_production',

    /*
    |--------------------------------------------------------------------------
    | Hash Algorithm
    |--------------------------------------------------------------------------
    */
    'hash_algo' => 'sha256',
];

