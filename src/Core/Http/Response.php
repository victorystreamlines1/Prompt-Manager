<?php
/**
 * ============================================
 * HTTP Response Service
 * ============================================
 * 
 * PURPOSE:
 * Standardizes HTTP responses across the application.
 * Handles JSON API responses and redirects.
 * 
 * INPUTS:
 * - Response data
 * - HTTP status codes
 * 
 * OUTPUTS:
 * - Formatted HTTP responses
 * 
 * SIDE EFFECTS:
 * - Sets HTTP headers
 * - Outputs response body
 * - May terminate script
 * 
 * ============================================
 */

namespace App\Core\Http;

class Response
{
    /**
     * Common HTTP status codes.
     */
    public const HTTP_OK = 200;
    public const HTTP_CREATED = 201;
    public const HTTP_NO_CONTENT = 204;
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHORIZED = 401;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_METHOD_NOT_ALLOWED = 405;
    public const HTTP_INTERNAL_ERROR = 500;

    /**
     * Send a JSON response.
     * 
     * @param bool $success Whether the operation was successful
     * @param string $message Response message
     * @param array|null $data Additional data
     * @param int $code HTTP status code
     * @param bool $exit Whether to exit after sending
     */
    public static function json(
        bool $success,
        string $message,
        ?array $data = null,
        int $code = self::HTTP_OK,
        bool $exit = true
    ): void {
        // Set headers
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        
        // Build response
        $response = [
            'success'   => $success,
            'message'   => $message,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        // Merge additional data
        if ($data !== null && is_array($data)) {
            $response = array_merge($response, $data);
        }

        // Output
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        if ($exit) {
            exit;
        }
    }

    /**
     * Send a success JSON response.
     * 
     * @param string $message Success message
     * @param array|null $data Additional data
     * @param int $code HTTP status code
     */
    public static function success(string $message, ?array $data = null, int $code = self::HTTP_OK): void
    {
        self::json(true, $message, $data, $code);
    }

    /**
     * Send an error JSON response.
     * 
     * @param string $message Error message
     * @param array|null $data Additional error details
     * @param int $code HTTP status code
     */
    public static function error(string $message, ?array $data = null, int $code = self::HTTP_BAD_REQUEST): void
    {
        self::json(false, $message, $data, $code);
    }

    /**
     * Send a validation error response.
     * 
     * @param array $errors Validation errors
     * @param string $message Optional message
     */
    public static function validationError(array $errors, string $message = 'Validation failed'): void
    {
        self::json(false, $message, ['errors' => $errors], self::HTTP_BAD_REQUEST);
    }

    /**
     * Send an unauthorized response.
     * 
     * @param string $message Error message
     */
    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        self::json(false, $message, null, self::HTTP_UNAUTHORIZED);
    }

    /**
     * Send a not found response.
     * 
     * @param string $message Error message
     */
    public static function notFound(string $message = 'Resource not found'): void
    {
        self::json(false, $message, null, self::HTTP_NOT_FOUND);
    }

    /**
     * Send a server error response.
     * 
     * @param string $message Error message
     * @param array|null $debug Debug info (only shown in debug mode)
     */
    public static function serverError(string $message = 'Internal server error', ?array $debug = null): void
    {
        $data = null;
        
        // Include debug info only in development
        if ($debug !== null && (getenv('APP_DEBUG') === 'true' || getenv('APP_DEBUG') === true)) {
            $data = ['debug' => $debug];
        }
        
        self::json(false, $message, $data, self::HTTP_INTERNAL_ERROR);
    }

    /**
     * Redirect to another URL.
     * 
     * @param string $url Target URL
     * @param int $code HTTP redirect code (301, 302, 303, 307)
     */
    public static function redirect(string $url, int $code = 302): void
    {
        http_response_code($code);
        header("Location: {$url}");
        exit;
    }

    /**
     * Send CORS headers.
     * 
     * @param string $origin Allowed origin (* for all)
     * @param array $methods Allowed methods
     * @param array $headers Allowed headers
     */
    public static function cors(
        string $origin = '*',
        array $methods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        array $headers = ['Content-Type', 'Authorization', 'X-Requested-With']
    ): void {
        header("Access-Control-Allow-Origin: {$origin}");
        header('Access-Control-Allow-Methods: ' . implode(', ', $methods));
        header('Access-Control-Allow-Headers: ' . implode(', ', $headers));
        header('Access-Control-Max-Age: 86400');
    }

    /**
     * Handle OPTIONS preflight request.
     */
    public static function handlePreflight(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            self::cors();
            http_response_code(200);
            exit;
        }
    }

    /**
     * Download a file.
     * 
     * @param string $content File content
     * @param string $filename Download filename
     * @param string $mimeType MIME type
     */
    public static function download(string $content, string $filename, string $mimeType = 'application/octet-stream'): void
    {
        header("Content-Type: {$mimeType}");
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: no-cache');
        
        echo $content;
        exit;
    }
}

