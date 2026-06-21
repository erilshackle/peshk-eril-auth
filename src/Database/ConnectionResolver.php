<?php

namespace Eril\Auth\Database;

use Eril\Auth\Exceptions\ConfigurationException;
use PDO;

final class ConnectionResolver
{
    private ?PDO $resolved = null;

    public function __construct(
        private mixed $db
    ) {}

    public function get(): PDO
    {
        if ($this->resolved instanceof PDO) {
            return $this->resolved;
        }

        if ($this->db instanceof PDO) {
            return $this->resolved = $this->db;
        }

        if (is_callable($this->db)) {
            $pdo = call_user_func($this->db);

            if ($pdo instanceof PDO) {
                return $this->resolved = $pdo;
            }
        }

        throw new ConfigurationException('Invalid PDO configuration.');
    }
}