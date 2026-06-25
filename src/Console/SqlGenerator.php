<?php

namespace Eril\Auth\Console;

final class SqlGenerator
{
    public function generate(array $options): string
    {
        $sql = [];

        if ($options['remember'] ?? false) {
            $sql[] = $this->rememberSql(
                usersTable: $options['users_table'] ?? 'users',
                selectorField: $options['remember_selector_field'] ?? 'remember_selector',
                tokenField: $options['remember_token_field'] ?? 'remember_token',
            );
        }

        if ($options['providers'] ?? false) {
            $sql[] = $this->providersSql(
                providersTable: $options['providers_table'] ?? 'user_providers',
                providerField: $options['provider_field'] ?? 'provider',
                providerIdField: $options['provider_id_field'] ?? 'provider_id',
                userIdField: $options['provider_user_id_field'] ?? 'user_id',
            );
        }

        return trim(implode(PHP_EOL . PHP_EOL, array_filter($sql)));
    }

    private function rememberSql(
        string $usersTable,
        string $selectorField,
        string $tokenField
    ): string {
        return <<<SQL
ALTER TABLE {$usersTable}
ADD {$selectorField} VARCHAR(64) NULL,
ADD {$tokenField} VARCHAR(255) NULL;
SQL;
    }

    private function providersSql(
        string $providersTable,
        string $providerField,
        string $providerIdField,
        string $userIdField
    ): string {
        return <<<SQL
CREATE TABLE {$providersTable} (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    {$userIdField} INTEGER NOT NULL,
    {$providerField} VARCHAR(50) NOT NULL,
    {$providerIdField} VARCHAR(255) NOT NULL,
    created_at DATETIME NULL,
    UNIQUE ({$providerField}, {$providerIdField})
);
SQL;
    }
} 