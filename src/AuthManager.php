<?php

namespace Eril\Auth;

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
        $this->session->start();

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
        $current = $this->user()?->role();

        if (!$current) {
            return false;
        }

        return in_array($current, [$role, ...$roles], true);
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
        $checks = [];

        $checks['pdo'] = [
            'ok' => $this->pdo->get() instanceof \PDO,
            'message' => 'PDO connection resolved successfully.',
        ];

        $checks['session'] = [
            'ok' => session_status() === PHP_SESSION_ACTIVE,
            'message' => session_status() === PHP_SESSION_ACTIVE
                ? 'Session is active.'
                : 'Session is not active.',
        ];

        $checks['user_table'] = [
            'ok' => $this->tableExists($this->config->userTable()),
            'message' => "User table [{$this->config->userTable()}] exists.",
        ];

        $requiredColumns = [
            $this->config->idField(),
            $this->config->loginField(),
            $this->config->passwordField(),
            $this->config->nameField(),
        ];

        if ($this->config->roleField()) {
            $requiredColumns[] = $this->config->roleField();
        }

        if ($this->config->rememberEnabled() && $this->config->rememberTokenField()) {
            $requiredColumns[] = $this->config->rememberTokenField();
        }

        foreach (array_unique($requiredColumns) as $column) {
            $checks["column:{$column}"] = [
                'ok' => $this->columnExists($this->config->userTable(), $column),
                'message' => "Column [{$column}] exists in [{$this->config->userTable()}].",
            ];
        }

        $checks['password_algo'] = [
            'ok' => defined('PASSWORD_DEFAULT'),
            'message' => 'Password hashing API is available.',
        ];

        $checks['remember'] = [
            'ok' => !$this->config->rememberEnabled()
                || $this->config->rememberTokenField() !== null,
            'message' => $this->config->rememberEnabled()
                ? 'Remember-me is enabled.'
                : 'Remember-me is disabled.',
        ];

        return [
            'ok' => !in_array(false, array_column($checks, 'ok'), true),
            'checks' => $checks,
        ];
    }

    private function tableExists(string $table): bool
    {
        try {
            $stmt = $this->db()->query("SELECT 1 FROM {$table} LIMIT 1");

            return $stmt !== false;
        } catch (\Throwable) {
            return false;
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        try {
            $stmt = $this->db()->query("SELECT {$column} FROM {$table} LIMIT 1");

            return $stmt !== false;
        } catch (\Throwable) {
            return false;
        }
    }
}
