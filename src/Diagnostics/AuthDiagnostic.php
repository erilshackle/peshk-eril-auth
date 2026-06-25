<?php

namespace Eril\Auth\Diagnostics;

use Eril\Auth\Configuration\AuthConfig;
use Eril\Auth\Database\ConnectionResolver;
use Eril\Auth\Session\SessionManager;
use PDO;
use Throwable;

final class AuthDiagnostic
{
    public function __construct(
        private readonly AuthConfig $config,
        private readonly ConnectionResolver $pdo,
        private readonly SessionManager $session,
    ) {}

    public function run(): array
    {
        $checks = [];

        $checks['configured'] = $this->ok('Auth is configured.');
        $checks['pdo_connection'] = $this->checkPdoConnection();
        $checks['user_table'] = $this->checkTableExists($this->config->userTable());

        $checks['id_field'] = $this->checkColumnExists(
            $this->config->userTable(),
            $this->config->idField()
        );

        $checks['password_field'] = $this->checkColumnExists(
            $this->config->userTable(),
            $this->config->passwordField()
        );

        $checks['name_field'] = $this->checkColumnExists(
            $this->config->userTable(),
            $this->config->nameField()
        );

        if ($this->config->roleField()) {
            $checks['role_field'] = $this->checkColumnExists(
                $this->config->userTable(),
                $this->config->roleField()
            );
        }


        foreach ((array) $this->config->loginField() as $field) {
            $check = $this->checkColumnExists(
                $this->config->userTable(),
                $field
            );

            $checks["login_field_{$field}"] = $check;
            $checks["login_fields"] = ($checks["login_field"] ?? true) ? $check : false;
        }

        $checks['permissions'] = $this->checkPermissions();

        foreach ($this->checkProfiles() as $key => $check) {
            $checks[$key] = $check;
        }

        foreach ($this->checkProviders() as $key => $check) {
            $checks[$key] = $check;
        }

        if ($this->config->rememberEnabled()) {
            $checks['remember_selector_field'] = $this->checkColumnExists(
                $this->config->userTable(),
                $this->config->rememberSelectorField()
            );

            $checks['remember_token_field'] = $this->checkColumnExists(
                $this->config->userTable(),
                $this->config->rememberTokenField()
            );
        }

        $checks['session_status'] = [
            'ok' => session_status() === PHP_SESSION_ACTIVE,
            'message' => session_status() === PHP_SESSION_ACTIVE
                ? 'Session is active.'
                : 'Session is not active.',
        ];

        return $checks;
    }

    private function checkPdoConnection(): array
    {
        try {
            $this->db()->query('SELECT 1');

            return $this->ok('PDO connection is working.');
        } catch (Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    private function checkTableExists(?string $table): array
    {

        if (!$table) {
            return $this->fail('Table name is missing.');
        }

        try {
            $driver = $this->driver();

            $sql = match ($driver) {
                'mysql' => 'SHOW TABLES LIKE :table',
                'sqlite' => "SELECT name FROM sqlite_master WHERE type = 'table' AND name = :table",
                default => null,
            };

            if (!$sql) {
                return $this->fail("Table check is not supported for driver [{$driver}].");
            }

            $stmt = $this->db()->prepare($sql);
            $stmt->execute(['table' => $table]);

            $exists = (bool) $stmt->fetchColumn();

            return $exists
                ? $this->ok("Table [{$table}] exists.")
                : $this->fail("Table [{$table}] does not exist.");
        } catch (Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    private function checkPermissions(): array
    {
        $permissions = $this->config->permissions();

        if ($permissions === []) {
            return $this->ok('No permissions configured.');
        }

        foreach ($permissions as $role => $items) {
            if (!is_array($items)) {
                return $this->fail("Permissions for role [{$role}] must be an array.");
            }
        }

        return $this->ok('Permissions are configured.');
    }

    private function checkProfiles(): array
    {
        $checks = [];

        foreach ($this->config->profiles() as $role => $profile) {
            $table = $profile['table'] ?? null;
            $foreignKey = $profile['foreign_key'] ?? null;

            $checks["profile_{$role}_table"] = $this->checkTableExists($table);
            $checks["profile_{$role}_foreign_key"] = $this->checkColumnExists($table, $foreignKey);
        }

        return $checks;
    }

    private function checkProviders(): array
    {
        $providers = $this->config->providers();

        if ($providers === []) {
            return [];
        }

        $table = $providers['table'] ?? null;

        return [
            'provider_table' => $this->checkTableExists($table),

            'provider_field' => $this->checkColumnExists(
                $table,
                $providers['provider_field'] ?? null
            ),

            'provider_id_field' => $this->checkColumnExists(
                $table,
                $providers['provider_id_field'] ?? null
            ),

            'provider_user_id_field' => $this->checkColumnExists(
                $table,
                $providers['user_id_field'] ?? null
            ),
        ];
    }

    private function checkColumnExists(?string $table, ?string $column): array
    {

        if (!$table) {
            return $this->fail('Table name is missing.');
        }

        if (!$table && !$column) {
            return $this->fail('Column name is missing.');
        }

        if (!$column) {
            return $this->ok('Column check skipped.');
        }

        try {
            $driver = $this->driver();

            if ($driver === 'mysql') {
                $sql = "
                    SELECT COLUMN_NAME
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = :table
                      AND COLUMN_NAME = :column
                    LIMIT 1
                ";

                $stmt = $this->db()->prepare($sql);

                $stmt->execute([
                    'table' => $table,
                    'column' => $column,
                ]);

                $exists = (bool) $stmt->fetchColumn();

                return $exists
                    ? $this->ok("Column [{$column}] exists on [{$table}].")
                    : $this->fail("Column [{$column}] does not exist on [{$table}].");
            }

            if ($driver === 'sqlite') {
                $stmt = $this->db()->query(sprintf(
                    'PRAGMA table_info("%s")',
                    str_replace('"', '""', $table)
                ));

                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($columns as $info) {
                    if (($info['name'] ?? null) === $column) {
                        return $this->ok("Column [{$column}] exists on [{$table}].");
                    }
                }

                return $this->fail("Column [{$column}] does not exist on [{$table}].");
            }

            return $this->fail("Column check is not supported for driver [{$driver}].");
        } catch (Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    private function driver(): string
    {
        return $this->db()->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    private function db(): PDO
    {
        return $this->pdo->get();
    }

    private function ok(string $message): array
    {
        return [
            'ok' => true,
            'message' => $message,
        ];
    }

    private function fail(string $message): array
    {
        return [
            'ok' => false,
            'message' => $message,
        ];
    }
}
