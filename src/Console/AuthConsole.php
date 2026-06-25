<?php

namespace Eril\Auth\Console;

use Eril\Auth\Auth;

final class AuthConsole
{
    public function run(array $argv): int
    {
        $terminal = new Terminal();

        $command = $argv[1] ?? 'help';

        try {
            return match ($command) {
                'install' => $this->install($argv),
                'publish' => $this->publish($argv),
                'sql' => $this->sql($argv),
                'diagnose' => $this->diagnose($argv),
                default => $this->help($terminal),
            };
        } catch (\Throwable $e) {
            $terminal->error($e->getMessage());

            return 1;
        }
    }

    private function install(array $argv): int
    {
        $executeSql = in_array('--run', $argv, true);

        (new Installer())->run($executeSql);

        return 0;
    }

    private function publish(array $argv): int
    {
        $path = $this->option($argv, '--config') ?? 'config/auth.php';

        \Eril\Auth\Configuration\ConfigPublisher::publish($path);

        (new Terminal())->success("Config published to {$path}");

        return 0;
    }

    private function sql(array $argv): int
    {
        $terminal = new Terminal();

        $remember = !in_array('--no-remember', $argv, true);
        $providers = in_array('--providers', $argv, true);

        $sql = (new SqlGenerator())->generate([
            'remember' => $remember,
            'providers' => $providers,
        ]);

        $terminal->line($sql);

        return 0;
    }

    private function diagnose(array $argv): int
    {
        $terminal = new Terminal();

        $path = $this->option($argv, '--config') ?? 'config/auth.php';

        Auth::loadConfig($path);

        foreach (Auth::diagnose() as $name => $check) {
            $terminal->line(
                ($check['ok'] ? '✓ ' : '✗ ')
                . $name
                . ': '
                . $check['message']
            );
        }

        return 0;
    }

    private function help(Terminal $terminal): int
    {
        $terminal->line('Eril Auth CLI');
        $terminal->line();
        $terminal->line('Usage:');
        $terminal->line('  auth install [--run]');
        $terminal->line('  auth publish [--config=config/auth.php]');
        $terminal->line('  auth sql [--providers] [--no-remember]');
        $terminal->line('  auth diagnose [--config=config/auth.php]');

        return 0;
    }

    private function option(array $argv, string $name): ?string
    {
        foreach ($argv as $arg) {
            if (str_starts_with($arg, $name . '=')) {
                return substr($arg, strlen($name) + 1);
            }
        }

        return null;
    }
}