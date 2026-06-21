<?php

namespace Eril\Auth\Auth;

use Eril\Auth\Configuration\AuthConfig;
use Eril\Auth\Database\ConnectionResolver;
use PDO;

final class RememberMeManager
{
    public function __construct(
        private readonly AuthConfig $config,
        private readonly ConnectionResolver $pdo,
    ) {}

    public function remember(?AuthUser $user): void
    {
        if (!$user || !$this->config->rememberTokenField()) {
            return;
        }

        $token = bin2hex(random_bytes(32));
        $hash = password_hash($token, PASSWORD_DEFAULT);

        $sql = sprintf(
            'UPDATE %s SET %s = :token WHERE %s = :id',
            $this->config->userTable(),
            $this->config->rememberTokenField(),
            $this->config->idField()
        );

        $stmt = $this->db()->prepare($sql);

        $stmt->execute([
            'token' => $hash,
            'id' => $user->id(),
        ]);

        $this->setCookie($token);
    }

    public function attemptAutoLogin(AuthManager $auth): void
    {
        $token = $_COOKIE[$this->config->rememberCookie()] ?? null;

        if (!$token || !$this->config->rememberTokenField()) {
            return;
        }

        $sql = sprintf(
            'SELECT * FROM %s WHERE %s IS NOT NULL',
            $this->config->userTable(),
            $this->config->rememberTokenField()
        );

        $stmt = $this->db()->query($sql);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($users as $user) {
            $hash = $user[$this->config->rememberTokenField()] ?? null;

            if ($hash && password_verify($token, $hash)) {
                $auth->login($user);
                return;
            }
        }

        $this->forgetCookie();
    }

    public function forget(?AuthUser $user): void
    {
        if ($user && $this->config->rememberTokenField()) {
            $sql = sprintf(
                'UPDATE %s SET %s = NULL WHERE %s = :id',
                $this->config->userTable(),
                $this->config->rememberTokenField(),
                $this->config->idField()
            );

            $stmt = $this->db()->prepare($sql);

            $stmt->execute([
                'id' => $user->id(),
            ]);
        }

        $this->forgetCookie();
    }

    private function setCookie(string $token): void
    {
        setcookie(
            $this->config->rememberCookie(),
            $token,
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
        return $this->pdo->get();
    }
}
