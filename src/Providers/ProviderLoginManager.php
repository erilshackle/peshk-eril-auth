<?php

namespace Eril\Auth\Providers;

use Eril\Auth\Auth\AuthManager;
use Eril\Auth\Auth\AuthUser;
use Eril\Auth\Configuration\AuthConfig;
use Eril\Auth\Database\ConnectionResolver;
use PDO;

final class ProviderLoginManager
{
    public function __construct(
        private readonly AuthConfig $config,
        private readonly ConnectionResolver $connection,
        private readonly AuthManager $auth,
    ) {}

    public function login(string $provider, string $providerId): AuthUser|false
    {
        $providerConfig = $this->config->providers()[$provider] ?? null;

        if (!$providerConfig) {
            return false;
        }

        $userId = $this->findUserId(
            provider: $provider,
            providerId: $providerId,
            config: $providerConfig
        );

        if (!$userId) {
            return false;
        }

        $user = $this->auth->findUserById($userId);

        if (!$user) {
            return false;
        }

        return $this->auth->login($user);
    }

    private function findUserId(string $provider, string $providerId, array $config): int|string|null
    {
        $table = $config['table'];
        $providerField = $config['provider_field'] ?? null;
        $providerIdField = $config['provider_id_field'];
        $userIdField = $config['user_id_field'];

        if ($providerField) {
            $sql = sprintf(
                'SELECT %s FROM %s WHERE %s = :provider AND %s = :provider_id LIMIT 1',
                $userIdField,
                $table,
                $providerField,
                $providerIdField
            );

            $stmt = $this->db()->prepare($sql);

            $stmt->execute([
                'provider' => $provider,
                'provider_id' => $providerId,
            ]);
        } else {
            $sql = sprintf(
                'SELECT %s FROM %s WHERE %s = :provider_id LIMIT 1',
                $userIdField,
                $table,
                $providerIdField
            );

            $stmt = $this->db()->prepare($sql);

            $stmt->execute([
                'provider_id' => $providerId,
            ]);
        }

        $id = $stmt->fetchColumn();

        return $id !== false ? $id : null;
    }

    private function db(): PDO
    {
        return $this->connection->get();
    }
}