<?php

namespace Eril\Auth\Session;

final class SessionManager
{
    public function start(?int $lifetime = null): void
    {
        if ($this->isStarted()) {
            return;
        }

        if (headers_sent()) {
            throw new \RuntimeException(
                'Cannot start session because headers have already been sent.'
            );
        }

        if ($lifetime !== null && $lifetime > 0) {
            ini_set('session.gc_maxlifetime', (string) $lifetime);

            session_set_cookie_params([
                'lifetime' => $lifetime,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => !empty($_SERVER['HTTPS']),
            ]);
        }

        session_start();
    }

    public function isStarted(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function regenerate(): void
    {
        if ($this->isStarted()) {
            session_regenerate_id(true);
        }
    }

    public function destroy(): void
    {
        if (!$this->isStarted()) {
            return;
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $params['path'] ?? '/',
                    'domain' => $params['domain'] ?? '',
                    'secure' => $params['secure'] ?? false,
                    'httponly' => $params['httponly'] ?? true,
                    'samesite' => $params['samesite'] ?? 'Lax',
                ]
            );
        }

        session_destroy();
    }
}
