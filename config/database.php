<?php
/**
 * ============================================
 * Database Configuration
 * ============================================
 * Database connection settings.
 * Supports multiple connections (local and remote).
 * ============================================
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Default Database Connection
    |--------------------------------------------------------------------------
    | The default connection to use: 'local' or 'hostinger'
    */
    'default' => getenv('DB_CONNECTION') ?: 'hostinger',

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    */
    'connections' => [
        
        'local' => [
            'driver'   => 'mysql',
            'host'     => getenv('DB_LOCAL_HOST') ?: 'localhost',
            'port'     => getenv('DB_LOCAL_PORT') ?: '3306',
            'database' => getenv('DB_LOCAL_NAME') ?: '',
            'username' => getenv('DB_LOCAL_USERNAME') ?: 'root',
            'password' => getenv('DB_LOCAL_PASSWORD') ?: '',
            'charset'  => 'utf8mb4',
            'collation'=> 'utf8mb4_unicode_ci',
        ],

        'hostinger' => [
            'driver'   => 'mysql',
            'host'     => getenv('DB_HOST') ?: 'srv1788.hstgr.io',
            'port'     => getenv('DB_PORT') ?: '3306',
            'database' => getenv('DB_NAME') ?: 'u419999707_Mohamed',
            'username' => getenv('DB_USERNAME') ?: 'u419999707_Abuammar',
            'password' => getenv('DB_PASSWORD') ?: '',
            'charset'  => 'utf8mb4',
            'collation'=> 'utf8mb4_unicode_ci',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | PDO Options
    |--------------------------------------------------------------------------
    */
    'options' => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Table Prefixes (for different modules)
    |--------------------------------------------------------------------------
    */
    'prefixes' => [
        'prompt_manager' => 'reporter_prompt_',
        'catalog'        => 'catalog_',
        'dashboard'      => '',
    ],
];

