<?php
/**
 * ============================================
 * Application Bootstrap
 * ============================================
 * 
 * PURPOSE:
 * Initializes the application:
 * - Loads configuration
 * - Sets up autoloading
 * - Configures error handling
 * - Provides helper functions
 * 
 * USAGE:
 * require_once __DIR__ . '/src/bootstrap.php';
 * 
 * ============================================
 */

// ============================================
// CONSTANTS
// ============================================

define('APP_ROOT', dirname(__DIR__));
define('APP_START', microtime(true));

// ============================================
// ERROR HANDLING
// ============================================

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', APP_ROOT . '/storage/logs/php_errors.log');

// ============================================
// ENVIRONMENT LOADING
// ============================================

/**
 * Load environment variables from .env file.
 * Simple implementation without external dependencies.
 */
function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Skip comments
        if (str_starts_with(trim($line), '#')) {
            continue;
        }

        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            // Remove quotes
            $value = trim($value, '"\'');
            
            // Don't override existing env vars
            if (!getenv($name)) {
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
            }
        }
    }
}

// Load .env if exists
loadEnv(APP_ROOT . '/.env');

// ============================================
// AUTOLOADER
// ============================================

/**
 * PSR-4 style autoloader for App namespace.
 */
spl_autoload_register(function (string $class) {
    // Only handle App namespace
    $prefix = 'App\\';
    $baseDir = APP_ROOT . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// ============================================
// CONFIGURATION LOADER
// ============================================

/**
 * Load and cache configuration.
 * 
 * @param string $name Config file name (without .php)
 * @return array
 */
function config(string $name): array
{
    static $configs = [];

    if (!isset($configs[$name])) {
        $path = APP_ROOT . "/config/{$name}.php";
        $configs[$name] = file_exists($path) ? require $path : [];
    }

    return $configs[$name];
}

/**
 * Get a specific config value using dot notation.
 * 
 * @param string $key e.g., 'app.name' or 'database.connections.local'
 * @param mixed $default Default value if not found
 * @return mixed
 */
function configGet(string $key, mixed $default = null): mixed
{
    $parts = explode('.', $key);
    $configName = array_shift($parts);
    $config = config($configName);

    foreach ($parts as $part) {
        if (!is_array($config) || !isset($config[$part])) {
            return $default;
        }
        $config = $config[$part];
    }

    return $config;
}

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Get application path.
 * 
 * @param string $path Relative path
 * @return string Full path
 */
function appPath(string $path = ''): string
{
    return APP_ROOT . ($path ? '/' . ltrim($path, '/') : '');
}

/**
 * Get public path.
 */
function publicPath(string $path = ''): string
{
    return APP_ROOT . '/public' . ($path ? '/' . ltrim($path, '/') : '');
}

/**
 * Get storage path.
 */
function storagePath(string $path = ''): string
{
    return APP_ROOT . '/storage' . ($path ? '/' . ltrim($path, '/') : '');
}

/**
 * Get uploads path.
 */
function uploadsPath(string $path = ''): string
{
    $base = getenv('UPLOAD_PATH') ?: 'uploads';
    return APP_ROOT . '/' . $base . ($path ? '/' . ltrim($path, '/') : '');
}

/**
 * Check if running in debug mode.
 */
function isDebug(): bool
{
    return filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOLEAN);
}

/**
 * Check if running in production.
 */
function isProduction(): bool
{
    return (getenv('APP_ENV') ?: 'local') === 'production';
}

/**
 * Dump and die (debug helper).
 */
function dd(mixed ...$vars): never
{
    echo '<pre style="background:#1e1e2e;color:#cdd6f4;padding:20px;border-radius:8px;font-family:monospace;">';
    foreach ($vars as $var) {
        var_dump($var);
        echo "\n";
    }
    echo '</pre>';
    exit;
}

/**
 * Log message to file.
 * 
 * @param string $message
 * @param string $level info, warning, error, debug
 * @param array $context Additional context
 */
function logMessage(string $message, string $level = 'info', array $context = []): void
{
    $logDir = storagePath('logs');
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $logFile = $logDir . '/app.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = $context ? ' ' . json_encode($context) : '';
    
    $logLine = "[{$timestamp}] [{$level}] {$message}{$contextStr}\n";
    
    file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
}

// ============================================
// ENSURE REQUIRED DIRECTORIES
// ============================================

$requiredDirs = [
    storagePath('logs'),
    storagePath('cache'),
    uploadsPath(),
];

foreach ($requiredDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// ============================================
// TIMEZONE
// ============================================

date_default_timezone_set(configGet('app.timezone', 'UTC'));

