<?php
/**
 * ============================================
 * AuthService - Unified Authentication Service
 * ============================================
 * 
 * PURPOSE:
 * Handles all authentication logic including login,
 * logout, session management, and cookie handling.
 * 
 * INPUTS:
 * - Password for login verification
 * - Session and cookie data
 * 
 * OUTPUTS:
 * - Boolean for auth status
 * - Session/cookie modifications
 * 
 * SIDE EFFECTS:
 * - Modifies $_SESSION
 * - Sets/removes cookies
 * 
 * ============================================
 */

namespace App\Core\Auth;

class AuthService
{
    private array $config;
    private string $sessionKey;
    private string $cookieName;

    /**
     * Initialize the auth service with configuration.
     * 
     * @param array $config Authentication configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->sessionKey = $this->config['session']['key'] ?? 'admin_logged_in';
        $this->cookieName = $this->config['cookie']['name'] ?? 'admin_remember';
        
        $this->initSession();
    }

    /**
     * Get default configuration.
     */
    private function getDefaultConfig(): array
    {
        return [
            'password' => 'GL_Admin',
            'cookie' => [
                'name' => 'admin_remember',
                'duration' => 2592000, // 30 days
                'path' => '/',
                'secure' => false,
                'httponly' => true,
            ],
            'session' => [
                'key' => 'admin_logged_in',
                'lifetime' => 7200,
            ],
            'salt' => 'default_salt',
            'hash_algo' => 'sha256',
        ];
    }

    /**
     * Initialize PHP session if not started.
     */
    private function initSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Attempt to login with password.
     * 
     * @param string $password The password to verify
     * @param bool $rememberMe Whether to set remember cookie
     * @return bool True if login successful
     */
    public function login(string $password, bool $rememberMe = false): bool
    {
        if ($password !== $this->config['password']) {
            return false;
        }

        $_SESSION[$this->sessionKey] = true;
        $_SESSION['login_time'] = time();

        if ($rememberMe) {
            $this->setRememberCookie();
            $_SESSION['remembered'] = true;
        }

        return true;
    }

    /**
     * Logout the current user.
     */
    public function logout(): void
    {
        // Remove remember cookie
        $this->removeRememberCookie();

        // Clear session
        $_SESSION[$this->sessionKey] = false;
        unset($_SESSION['login_time']);
        unset($_SESSION['remembered']);
        
        session_destroy();
    }

    /**
     * Check if user is currently authenticated.
     * Also checks remember cookie if session not set.
     * 
     * @return bool True if authenticated
     */
    public function isAuthenticated(): bool
    {
        // Check session first
        if (isset($_SESSION[$this->sessionKey]) && $_SESSION[$this->sessionKey] === true) {
            return true;
        }

        // Check remember cookie
        if ($this->checkRememberCookie()) {
            $_SESSION[$this->sessionKey] = true;
            $_SESSION['login_time'] = time();
            $_SESSION['remembered'] = true;
            return true;
        }

        return false;
    }

    /**
     * Set the remember me cookie.
     */
    private function setRememberCookie(): void
    {
        $cookieValue = $this->generateCookieHash();
        $expiry = time() + $this->config['cookie']['duration'];
        
        setcookie(
            $this->cookieName,
            $cookieValue,
            $expiry,
            $this->config['cookie']['path'],
            '',
            $this->config['cookie']['secure'],
            $this->config['cookie']['httponly']
        );
    }

    /**
     * Remove the remember cookie.
     */
    private function removeRememberCookie(): void
    {
        if (isset($_COOKIE[$this->cookieName])) {
            setcookie(
                $this->cookieName,
                '',
                time() - 3600,
                $this->config['cookie']['path'],
                '',
                $this->config['cookie']['secure'],
                $this->config['cookie']['httponly']
            );
            unset($_COOKIE[$this->cookieName]);
        }
    }

    /**
     * Check if remember cookie is valid.
     * 
     * @return bool True if valid remember cookie exists
     */
    private function checkRememberCookie(): bool
    {
        if (!isset($_COOKIE[$this->cookieName])) {
            return false;
        }

        $expectedHash = $this->generateCookieHash();
        return $_COOKIE[$this->cookieName] === $expectedHash;
    }

    /**
     * Generate hash for cookie value.
     * 
     * @return string Hashed cookie value
     */
    private function generateCookieHash(): string
    {
        return hash(
            $this->config['hash_algo'],
            $this->config['password'] . $this->config['salt']
        );
    }

    /**
     * Get login time if authenticated.
     * 
     * @return int|null Unix timestamp or null
     */
    public function getLoginTime(): ?int
    {
        return $_SESSION['login_time'] ?? null;
    }

    /**
     * Check if current session was from remember cookie.
     * 
     * @return bool
     */
    public function wasRemembered(): bool
    {
        return $_SESSION['remembered'] ?? false;
    }
}

