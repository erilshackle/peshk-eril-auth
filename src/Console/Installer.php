<?php

namespace Eril\Auth\Console;

use Eril\Auth\Auth;
use Eril\Auth\Configuration\ConfigPublisher;

final class Installer
{

    public function __construct(
        private readonly Terminal $terminal = new Terminal(),
        private readonly SqlGenerator $sql = new SqlGenerator(),
        private readonly SqlExecutor $executor = new SqlExecutor(),
    ) {}

    public function run(bool $executeSql = false): void
    {
        $this->terminal->line('Eril Auth Installer');
        $this->terminal->line('-------------------');

        $configDir = $this->terminal->ask('Configuration directory', 'config');
        $databaseDir = $this->terminal->ask('Database directory', 'database');

        $usersTable = $this->terminal->ask('Users table', 'users');
        $loginField = $this->terminal->ask('Login field', 'email');
        $loginField = explode(',', $loginField);
        $passwordField = $this->terminal->ask('Password field', 'password');
        $roleField = $this->terminal->ask('Role field', 'role');

        $remember = $this->terminal->confirm('Enable Remember Me?', true);
        $profiles = $this->terminal->confirm('Enable Profiles?', false);
        $providers = $this->terminal->confirm('Enable Provider Login?', false);

        $generateSql = $this->terminal->confirm('Generate SQL file?', true);

        $authPath = rtrim($configDir, '/\\') . DIRECTORY_SEPARATOR . 'auth.php';

        ConfigPublisher::publish($authPath);

        $this->terminal->success("Config published to {$authPath}");

        if ($generateSql) {
            $sqlPath = rtrim($databaseDir, '/\\') . DIRECTORY_SEPARATOR . 'auth.sql';

            $sql = $this->sql->generate([
                'remember' => $remember,
                'providers' => $providers,
                'users_table' => $usersTable,
            ]);

            $this->writeFile($sqlPath, $sql);

            $this->terminal->success("SQL generated at {$sqlPath}");
        }

        // if ($executeSql && $generateSql && trim($sql) !== '') {
        //     $this->terminal->line();

        //     $confirmed = $this->terminal->confirm(
        //         'This will execute SQL against your configured database. Continue?',
        //         false
        //     );

        //     if ($confirmed) {
        //         Auth::loadConfig($authPath);

        //         $pdo = Auth::connection();

        //         $this->executor->execute($pdo, $sql);

        //         $this->terminal->success('SQL executed successfully.');
        //     } else {
        //         $this->terminal->info('SQL execution skipped.');
        //     }
        // }

        $this->terminal->line();
        $this->terminal->success('Installation completed.');
    }

    private function writeFile(string $path, string $contents): void
    {
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents($path, $contents . PHP_EOL);
    }
}
