<?php

namespace Eril\Auth\Console;

final class Terminal
{
    public function line(string $message = ''): void
    {
        fwrite(STDOUT, $message . PHP_EOL);
    }

    public function info(string $message): void
    {
        $this->line('[INFO] ' . $message);
    }

    public function success(string $message): void
    {
        $this->line('[OK] ' . $message);
    }

    public function error(string $message): void
    {
        fwrite(STDERR, '[ERROR] ' . $message . PHP_EOL);
    }

    public function ask(string $question, ?string $default = null): string
    {
        $label = $default !== null
            ? "{$question} [{$default}]: "
            : "{$question}: ";

        fwrite(STDOUT, $label);

        $answer = trim((string) fgets(STDIN));

        return $answer !== '' ? $answer : (string) $default;
    }

    public function confirm(string $question, bool $default = false): bool
    {
        $suffix = $default ? '[Y/n]' : '[y/N]';

        fwrite(STDOUT, "{$question} {$suffix}: ");

        $answer = strtolower(trim((string) fgets(STDIN)));

        if ($answer === '') {
            return $default;
        }

        return in_array($answer, ['y', 'yes', 's', 'sim'], true);
    }
}