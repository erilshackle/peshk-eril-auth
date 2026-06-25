<?php

namespace Eril\Auth\Console;

use PDO;
use Throwable;

final class SqlExecutor
{
    public function execute(PDO $pdo, string $sql): void
    {
        $statements = $this->splitStatements($sql);

        if ($statements === []) {
            return;
        }

        $inTransaction = false;

        try {
            if (!$pdo->inTransaction()) {
                $pdo->beginTransaction();
                $inTransaction = true;
            }

            foreach ($statements as $statement) {
                $pdo->exec($statement);
            }

            if ($inTransaction) {
                $pdo->commit();
            }
        } catch (Throwable $e) {
            if ($inTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    private function splitStatements(string $sql): array
    {
        return array_values(array_filter(
            array_map('trim', explode(';', $sql)),
            fn (string $statement): bool => $statement !== ''
        ));
    }
}