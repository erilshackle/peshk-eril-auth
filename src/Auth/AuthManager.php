<?php

namespace Eril\Auth\Auth;

use Eril\Auth\Configuration\AuthConfig;
use Eril\Auth\Database\ConnectionResolver;
use Eril\Auth\Diagnostics\AuthDiagnostic;
use Eril\Auth\Profile\Profile;
use Eril\Auth\Profile\ProfileResolver;
use Eril\Auth\Providers\ProviderLoginManager;
use Eril\Auth\Providers\ProviderLosginManager;
use Eril\Auth\Session\SessionManager;
use PDO;

final class AuthManager
{
    private RememberMeManager $remember;
    private ProfileResolver $profiles;
    private ProviderLoginManager $providers;

    private ?string $error = null;

    public function __construct(
        private readonly AuthConfig $config,
        private readonly ConnectionResolver $pdo,
        private readonly SessionManager $session,
    ) {
        $this->remember = new RememberMeManager($this->config, $this->pdo);

        $this->profiles = new ProfileResolver(
            config: $this->config,
            connection: $this->pdo,
        );

        $this->providers = new ProviderLoginManager(
            config: $this->config,
            connection: $this->pdo,
            auth: $this,
        );
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

    public function loginWithProvider(string $provider, string $providerId): AuthUser|false
    {
        $this->error = null;

        $user = $this->providers->login($provider, $providerId);

        if (!$user) {
            $this->error = 'Provider login failed.';
        }

        return $user;
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

    public function profile(): ?Profile
    {
        return $this->profiles->resolve($this->user());
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

    public function diagnose(): array
    {
        return (new AuthDiagnostic(
            config: $this->config,
            pdo: $this->pdo,
            session: $this->session,
        ))->run();
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
        $fields = $this->config->loginField();

        if (is_string($fields)) {
            $fields = [$fields];
        }

        $where = [];
        $params = [];

        foreach ($fields as $index => $field) {
            $where[] = "{$field} = :login_{$index}";
            $params["login_{$index}"] = $login;
        }

        $sql = sprintf(
            'SELECT * FROM %s WHERE %s LIMIT 1',
            $this->config->userTable(),
            implode(' OR ', $where)
        );

        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    private function makeAuthUser(array $user): AuthUser
    {
        $data = [
            'id' => $user[$this->config->idField()] ?? null,
            'name' => $user[$this->config->nameField()] ?? null,
            'login' => $this->resolveLoginValue($user),
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

    private function resolveLoginValue(array $user): mixed
    {
        $fields = $this->config->loginField();

        if (is_string($fields)) {
            return $user[$fields] ?? null;
        }

        foreach ($fields as $field) {
            if (!empty($user[$field])) {
                return $user[$field];
            }
        }

        return null;
    }
}
