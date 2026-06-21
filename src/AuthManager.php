<?php

namespace Eril\Auth;

use Eril\Auth\Migration\AuthMigration;
use PDO;

final class AuthManager
{
    private RememberMeManager $remember;

    private ?string $error = null;

    public function __construct(
        private readonly AuthConfig $config,
        private readonly PdoResolver $pdo,
        private readonly SessionManager $session,
    ) {
        $this->remember = new RememberMeManager($this->config, $this->pdo);
    }

    public function boot(): void
    {
        $this->session->start(
            $this->config->sessionLifetime()
        );

        if (!$this->check() && $this->config->rememberEnabled()) {
            $this->remember->attemptAutoLogin($this);
        }
    }

    public function attempt(string $login, string $password): AuthUser|false
    {
        $this->error = null;

        $user = $this->findUserByLogin($login);

        if (!$user) {
            $this->error = 'Invalid credentials.';
            return false;
        }

        $hash = $user[$this->config->passwordField()] ?? null;

        if (!$hash || !password_verify($password, $hash)) {
            $this->error = 'Invalid credentials.';
            return false;
        }

        return $this->login($user);
    }

    public function login(array $user): AuthUser
    {
        $authUser = $this->makeAuthUser($user);

        $this->session->regenerate();
        $this->session->put($this->config->sessionName(), $authUser->toArray());

        return $authUser;
    }

    public function logout(): void
    {
        if ($this->config->rememberEnabled()) {
            $this->remember->forget($this->user());
        }

        $this->session->forget($this->config->sessionName());
        $this->session->regenerate();
    }

    public function check(): bool
    {
        return $this->session->has($this->config->sessionName());
    }

    public function user(): ?AuthUser
    {
        $data = $this->session->get($this->config->sessionName());

        if (!is_array($data)) {
            return null;
        }

        return new AuthUser($data);
    }

    public function id(): int|string|null
    {
        return $this->user()?->id();
    }

    public function hasRole(string $role, string ...$roles): bool
    {
        return $this->user()?->hasRole($role, ...$roles) ?? false;
    }

    public function rememberUser(): void
    {
        if (!$this->config->rememberEnabled()) {
            return;
        }

        $this->remember->remember($this->user());
    }

    public function error(): ?string
    {
        return $this->error;
    }

    public function findUserById(int|string $id): ?array
    {
        $sql = sprintf(
            'SELECT * FROM %s WHERE %s = :id LIMIT 1',
            $this->config->userTable(),
            $this->config->idField()
        );

        $stmt = $this->db()->prepare($sql);
        $stmt->execute(['id' => $id]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    private function findUserByLogin(string $login): ?array
    {
        $sql = sprintf(
            'SELECT * FROM %s WHERE %s = :login LIMIT 1',
            $this->config->userTable(),
            $this->config->loginField()
        );

        $stmt = $this->db()->prepare($sql);
        $stmt->execute(['login' => $login]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    private function makeAuthUser(array $user): AuthUser
    {
        $data = [
            'id' => $user[$this->config->idField()] ?? null,
            'name' => $user[$this->config->nameField()] ?? null,
            'login' => $user[$this->config->loginField()] ?? null,
        ];

        if ($this->config->roleField()) {
            $data['role'] = $user[$this->config->roleField()] ?? null;
        }

        $data['raw'] = $user;

        return new AuthUser($data);
    }

    private function db(): PDO
    {
        return $this->pdo->get();
    }

    public function diagnose(): array
    {
        return (new AuthDiagnostic(
            config: $this->config,
            pdo: $this->pdo,
            session: $this->session,
        ))->run();
    }
}
