<?php
/**
 * ============================================
 * HTTP Request Handler
 * ============================================
 * 
 * PURPOSE:
 * Abstracts HTTP request handling. Provides clean
 * access to request data from various sources.
 * 
 * INPUTS:
 * - $_GET, $_POST, php://input
 * - $_FILES, $_SERVER
 * 
 * OUTPUTS:
 * - Sanitized request data
 * 
 * SIDE EFFECTS:
 * - None (read-only)
 * 
 * ============================================
 */

namespace App\Core\Http;

class Request
{
    private array $get;
    private array $post;
    private array $json;
    private array $files;
    private array $server;
    private array $all;

    /**
     * Initialize request data.
     */
    public function __construct()
    {
        $this->get    = $_GET;
        $this->post   = $_POST;
        $this->server = $_SERVER;
        $this->files  = $_FILES;
        $this->json   = $this->parseJsonInput();
        
        // Merge all input sources
        $this->all = array_merge($this->get, $this->post, $this->json);
    }

    /**
     * Parse JSON input from request body.
     * 
     * @return array Parsed JSON or empty array
     */
    private function parseJsonInput(): array
    {
        $rawInput = file_get_contents('php://input');
        
        if (empty($rawInput)) {
            return [];
        }

        $decoded = json_decode($rawInput, true);
        
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Get a request parameter from any source.
     * 
     * @param string $key Parameter name
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all[$key] ?? $default;
    }

    /**
     * Get all request parameters.
     * 
     * @return array
     */
    public function all(): array
    {
        return $this->all;
    }

    /**
     * Get only specified parameters.
     * 
     * @param array $keys Keys to retrieve
     * @return array
     */
    public function only(array $keys): array
    {
        return array_intersect_key($this->all, array_flip($keys));
    }

    /**
     * Get all parameters except specified.
     * 
     * @param array $keys Keys to exclude
     * @return array
     */
    public function except(array $keys): array
    {
        return array_diff_key($this->all, array_flip($keys));
    }

    /**
     * Check if parameter exists.
     * 
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->all[$key]);
    }

    /**
     * Check if multiple parameters exist.
     * 
     * @param array $keys
     * @return bool
     */
    public function hasAll(array $keys): bool
    {
        foreach ($keys as $key) {
            if (!$this->has($key)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get missing required parameters.
     * 
     * @param array $required Required parameter names
     * @return array Missing parameters
     */
    public function missing(array $required): array
    {
        $missing = [];
        foreach ($required as $key) {
            if (!$this->has($key) || $this->get($key) === '') {
                $missing[] = $key;
            }
        }
        return $missing;
    }

    /**
     * Validate required parameters exist.
     * Throws or returns errors.
     * 
     * @param array $required Required parameters
     * @return array Empty if valid, otherwise missing keys
     */
    public function validate(array $required): array
    {
        return $this->missing($required);
    }

    /**
     * Get query string parameter.
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->get[$key] ?? $default;
    }

    /**
     * Get POST parameter.
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    /**
     * Get JSON body parameter.
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function json(string $key, mixed $default = null): mixed
    {
        return $this->json[$key] ?? $default;
    }

    /**
     * Get uploaded file.
     * 
     * @param string $key File input name
     * @return array|null File info or null
     */
    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Check if file was uploaded.
     * 
     * @param string $key
     * @return bool
     */
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) 
            && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Get request method.
     * 
     * @return string GET, POST, PUT, DELETE, etc.
     */
    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Check if request method matches.
     * 
     * @param string $method
     * @return bool
     */
    public function isMethod(string $method): bool
    {
        return $this->method() === strtoupper($method);
    }

    /**
     * Check if this is a POST request.
     */
    public function isPost(): bool
    {
        return $this->isMethod('POST');
    }

    /**
     * Check if this is a GET request.
     */
    public function isGet(): bool
    {
        return $this->isMethod('GET');
    }

    /**
     * Check if this is an AJAX request.
     * 
     * @return bool
     */
    public function isAjax(): bool
    {
        return ($this->server['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
            || $this->has('action'); // Our convention for API calls
    }

    /**
     * Check if request expects JSON response.
     * 
     * @return bool
     */
    public function expectsJson(): bool
    {
        $accept = $this->server['HTTP_ACCEPT'] ?? '';
        return strpos($accept, 'application/json') !== false || $this->isAjax();
    }

    /**
     * Get client IP address.
     * 
     * @return string
     */
    public function ip(): string
    {
        $keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($keys as $key) {
            if (!empty($this->server[$key])) {
                $ip = explode(',', $this->server[$key])[0];
                return trim($ip);
            }
        }

        return '0.0.0.0';
    }

    /**
     * Get user agent.
     * 
     * @return string
     */
    public function userAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Get request URI.
     * 
     * @return string
     */
    public function uri(): string
    {
        return $this->server['REQUEST_URI'] ?? '/';
    }

    /**
     * Get request path without query string.
     * 
     * @return string
     */
    public function path(): string
    {
        $uri = $this->uri();
        $pos = strpos($uri, '?');
        return $pos !== false ? substr($uri, 0, $pos) : $uri;
    }
}

