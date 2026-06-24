<?php

namespace Eril\Auth\Configuration;

use Eril\Auth\Exceptions\ConfigurationException;
use Eril\Auth\Support\SqlIdentifier;
use InvalidArgumentException;

final class AuthConfig
{

    public function __construct(
        private readonly mixed $db,
        private readonly string $userTable = 'users',
        private readonly string $loginField = 'email',
        private readonly string $passwordField = 'password_hash',
        private readonly string $idField = 'id',
        private readonly string $nameField = 'name',
        private readonly ?string $roleField = 'role',
        private readonly array $profiles = [],
        private readonly array $permissions = [],
        private readonly array $providers = [],
        private readonly string $sessionName = 'auth_user',
        private readonly int $sessionLifetime = 3600,
        private readonly bool $rememberEnabled = true,
        private readonly string $rememberCookie = 'remember_token',
        private readonly ?string $rememberTokenField = 'remember_token',
        private readonly ?string $rememberSelectorField = null,
        private readonly int $rememberDays = 7,
    ) {
        if ($this->db === null) {
            throw new ConfigurationException('Auth database connection [db] is required.');
        }

        SqlIdentifier::validate($this->userTable, 'user_table');
        SqlIdentifier::validate($this->loginField, 'login_field');
        SqlIdentifier::validate($this->passwordField, 'password_field');
        SqlIdentifier::validate($this->idField, 'id_field');
        SqlIdentifier::validate($this->nameField, 'name_field');
        SqlIdentifier::nullable($this->roleField, 'role_field');
        SqlIdentifier::nullable($this->rememberTokenField, 'remember_token_field');
        SqlIdentifier::nullable($this->rememberSelectorField, 'remember_selector_field');

        foreach ($this->profiles as $role => $profile) {
            SqlIdentifier::validate((string) $role, 'profile role');

            if (!is_array($profile)) {
                throw new ConfigurationException("Invalid profile configuration for role [{$role}].");
            }

            SqlIdentifier::validate($profile['table'] ?? '', "profile table for role [{$role}]");
            SqlIdentifier::validate($profile['foreign_key'] ?? '', "profile foreign_key for role [{$role}]");
        }

        foreach ($this->providers as $provider => $config) {
            SqlIdentifier::validate((string) $provider, 'provider name');

            if (!is_array($config)) {
                throw new ConfigurationException("Invalid provider configuration for [{$provider}].");
            }

            SqlIdentifier::validate($config['table'] ?? '', "provider table for [{$provider}]");
            SqlIdentifier::nullable($config['provider_field'] ?? null, "provider_field for [{$provider}]");
            SqlIdentifier::validate($config['provider_id_field'] ?? '', "provider_id_field for [{$provider}]");
            SqlIdentifier::validate($config['user_id_field'] ?? '', "user_id_field for [{$provider}]");
        }

        if ($this->sessionName === '') {
            throw new ConfigurationException('Auth session_name cannot be empty.');
        }

        if ($this->sessionLifetime < 1) {
            throw new ConfigurationException('Auth session_lifetime must be greater than zero.');
        }

        if ($this->rememberDays < 1) {
            throw new ConfigurationException('Auth remember_days must be greater than zero.');
        }

        if ($this->rememberEnabled && $this->rememberTokenField === null) {
            throw new ConfigurationException(
                'remember_token_field cannot be null when remember_enabled is true.'
            );
        }
    }

    /**
     * Create an AuthConfig instance from an array.
     *
     * Available options:
     *
     * @param array{
     *     db:\PDO|callable,
     *     user_table?:string,
     *     id_field?:string,
     *     name_field?:string,
     *     login_field?:string,
     *     password_field?:string,
     *     role_field?:string|null,
     *     profiles?:array,
     *     permissions?:array,
     *     providers?:array,
     *     session_name?:string,
     *     session_lifetime?:int,
     *     remember_enabled?:bool,
     *     remember_cookie?:string,
     *     remember_token_field?:string|null,
     *     remember_selector_field?:string|null,
     *     remember_days?:int
     * } $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            db: $config['db'] ?? null,
            userTable: $config['user_table'] ?? 'users',
            loginField: $config['login_field'] ?? 'email',
            passwordField: $config['password_field'] ?? 'password_hash',
            idField: $config['id_field'] ?? 'id',
            nameField: $config['name_field'] ?? 'name',
            roleField: $config['role_field'] ?? 'role',
            profiles: $config['profiles'] ?? [],
            permissions: $config['permissions'] ?? [],
            providers: $config['providers'] ?? [],
            sessionName: $config['session_name'] ?? 'auth_user',
            sessionLifetime: $config['session_lifetime'] ?? 3600,
            rememberEnabled: $config['remember_enabled'] ?? true,
            rememberCookie: $config['remember_cookie'] ?? 'remember_token',
            rememberTokenField: $config['remember_token_field'] ?? 'remember_token',
            rememberSelectorField: $config['remember_selector_field'] ?? 'remember_selector',
            rememberDays: $config['remember_days'] ?? 7,
        );
    }

    public function db(): mixed
    {
        return $this->db;
    }

    public function userTable(): string
    {
        return $this->userTable;
    }

    public function loginField(): string
    {
        return $this->loginField;
    }

    public function passwordField(): string
    {
        return $this->passwordField;
    }

    public function idField(): string
    {
        return $this->idField;
    }

    public function nameField(): string
    {
        return $this->nameField;
    }

    public function roleField(): ?string
    {
        return $this->roleField;
    }

    public function profiles(): array
    {
        return $this->profiles;
    }

    public function permissions(): array
    {
        return $this->permissions;
    }

    public function providers(): array
    {
        return $this->providers;
    }

    public function sessionName(): string
    {
        return $this->sessionName;
    }

    public function sessionLifetime(): int
    {
        return $this->sessionLifetime;
    }

    public function rememberEnabled(): bool
    {
        return $this->rememberEnabled;
    }

    public function rememberCookie(): string
    {
        return $this->rememberCookie;
    }

    public function rememberTokenField(): ?string
    {
        return $this->rememberTokenField;
    }

    public function rememberSelectorField(): ?string
    {
        return $this->rememberSelectorField;
    }

    public function rememberDays(): int
    {
        return $this->rememberDays;
    }
}
