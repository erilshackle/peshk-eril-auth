<?php

namespace Eril\Auth\Profile;

use Eril\Auth\Auth\AuthUser;
use Eril\Auth\Configuration\AuthConfig;
use Eril\Auth\Database\ConnectionResolver;
use PDO;

final class ProfileResolver
{
    public function __construct(
        private readonly AuthConfig $config,
        private readonly ConnectionResolver $connection,
    ) {}

    public function resolve(?AuthUser $user): ?Profile
    {
        if (!$user || !$user->role()) {
            return null;
        }

        $profile = $this->config->profiles()[$user->role()] ?? null;

        if (!$profile) {
            return null;
        }

        $table = $profile['table'];
        $foreignKey = $profile['foreign_key'];

        $sql = sprintf(
            'SELECT * FROM %s WHERE %s = :user_id LIMIT 1',
            $table,
            $foreignKey
        );

        $stmt = $this->db()->prepare($sql);

        $stmt->execute([
            'user_id' => $user->id(),
        ]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? new Profile($data) : null;
    }

    private function db(): PDO
    {
        return $this->connection->get();
    }
}