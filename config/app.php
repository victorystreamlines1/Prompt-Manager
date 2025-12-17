<?php
/**
 * ============================================
 * Application Configuration
 * ============================================
 * Central configuration for the application.
 * All settings are loaded from environment variables
 * with sensible defaults for development.
 * ============================================
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    */
    'name' => getenv('APP_NAME') ?: 'Prompt Manager',

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    | Values: local, development, staging, production
    */
    'env' => getenv('APP_ENV') ?: 'local',

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    */
    'debug' => filter_var(getenv('APP_DEBUG') ?: true, FILTER_VALIDATE_BOOLEAN),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    */
    'url' => getenv('APP_URL') ?: 'http://localhost',

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    */
    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale
    |--------------------------------------------------------------------------
    */
    'locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Paths
    |--------------------------------------------------------------------------
    */
    'paths' => [
        'root'      => dirname(__DIR__),
        'config'    => dirname(__DIR__) . '/config',
        'src'       => dirname(__DIR__) . '/src',
        'public'    => dirname(__DIR__) . '/public',
        'templates' => dirname(__DIR__) . '/templates',
        'storage'   => dirname(__DIR__) . '/storage',
        'uploads'   => dirname(__DIR__) . '/' . (getenv('UPLOAD_PATH') ?: 'uploads'),
        'logs'      => dirname(__DIR__) . '/' . (getenv('LOG_PATH') ?: 'storage/logs'),
        'cache'     => dirname(__DIR__) . '/' . (getenv('CACHE_PATH') ?: 'storage/cache'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'security' => [
        'allowed_extensions' => explode(',', getenv('ALLOWED_EXTENSIONS') ?: 'stl,obj,fbx,glb,gltf,html,htm,php,txt,json,csv'),
        'max_upload_size'    => (int)(getenv('MAX_UPLOAD_SIZE') ?: 52428800), // 50MB
    ],
];

