<?php

namespace Eril\Auth\Auth;

use Eril\Auth\Configuration\AuthConfig;
use Eril\Auth\Database\ConnectionResolver;
use Eril\Auth\Auth\AuthManager;
use Eril\Auth\Auth\AuthUser;
use PDO;

final class RememberMeManager
{
    public function __construct(
        private readonly AuthConfig $config,
        private readonly ConnectionResolver $connection,
    ) {}

    public function remember(?AuthUser $user): void
    {
        if (!$user || !$this->enabled()) {
            return;
        }

        $selector = bin2hex(random_bytes(16));
        $token = bin2hex(random_bytes(32));
        $hash = password_hash($token, PASSWORD_DEFAULT);

        $sql = sprintf(
            'UPDATE %s SET %s = :selector, %s = :token WHERE %s = :id',
            $this->config->userTable(),
            $this->config->rememberSelectorField(),
            $this->config->rememberTokenField(),
            $this->config->idField()
        );

        $stmt = $this->db()->prepare($sql);

        $stmt->execute([
            'selector' => $selector,
            'token' => $hash,
            'id' => $user->id(),
        ]);

        $this->setCookie($selector . ':' . $token);
    }

    public function attemptAutoLogin(AuthManager $auth): void
    {
        if (!$this->enabled()) {
            return;
        }

        $cookie = $_COOKIE[$this->config->rememberCookie()] ?? null;

        if (!$cookie || !str_contains($cookie, ':')) {
            return;
        }

        [$selector, $token] = explode(':', $cookie, 2);

        if ($selector === '' || $token === '') {
            $this->forgetCookie();
            return;
        }

        $sql = sprintf(
            'SELECT * FROM %s WHERE %s = :selector LIMIT 1',
            $this->config->userTable(),
            $this->config->rememberSelectorField()
        );

        $stmt = $this->db()->prepare($sql);
        $stmt->execute([
            'selector' => $selector,
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $this->forgetCookie();
            return;
        }

        $hash = $user[$this->config->rememberTokenField()] ?? null;

        if (!$hash || !password_verify($token, $hash)) {
            $this->clearDatabaseToken($user[$this->config->idField()] ?? null);
            $this->forgetCookie();
            return;
        }

        $auth->login($user);

        /**
         * Token rotation:
         * after a successful auto-login, generate a new token.
         */
        $this->remember($auth->user());
    }

    public function forget(?AuthUser $user): void
    {
        if ($user && $this->enabled()) {
            $this->clearDatabaseToken($user->id());
        }

        $this->forgetCookie();
    }

    private function clearDatabaseToken(int|string|null $id): void
    {
        if ($id === null) {
            return;
        }

        $sql = sprintf(
            'UPDATE %s SET %s = NULL, %s = NULL WHERE %s = :id',
            $this->config->userTable(),
            $this->config->rememberSelectorField(),
            $this->config->rememberTokenField(),
            $this->config->idField()
        );

        $stmt = $this->db()->prepare($sql);
        $stmt->execute(['id' => $id]);
    }

    private function enabled(): bool
    {
        return $this->config->rememberEnabled()
            && $this->config->rememberSelectorField() !== null
            && $this->config->rememberTokenField() !== null;
    }

    private function setCookie(string $value): void
    {
        setcookie(
            $this->config->rememberCookie(),
            $value,
            [
                'expires' => time() + ($this->config->rememberDays() * 86400),
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => !empty($_SERVER['HTTPS']),
            ]
        );
    }

    private function forgetCookie(): void
    {
        setcookie(
            $this->config->rememberCookie(),
            '',
            [
                'expires' => time() - 3600,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => !empty($_SERVER['HTTPS']),
            ]
        );
    }

    private function db(): PDO
    {
        return $this->connection->get();
    }
}