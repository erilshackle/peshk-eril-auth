<?php

namespace Eril\Auth\RateLimit;

use Eril\Auth\Configuration\AuthConfig;
use Eril\Auth\Session\SessionManager;

final class LoginRateLimiter
{
    private const SESSION_KEY = '_auth_rate_limits';

    public function __construct(
        private readonly AuthConfig $config,
        private readonly SessionManager $session,
    ) {}

    public function enabled(): bool
    {
        return (bool) ($this->config->rateLimit()['enabled'] ?? false);
    }

    public function tooManyAttempts(string $login): bool
    {
        if (!$this->enabled()) {
            return false;
        }

        $record = $this->record($login);

        if (!$record) {
            return false;
        }

        return $record['attempts'] >= $this->maxAttempts()
            && $record['expires_at'] > time();
    }

    public function hit(string $login): void
    {
        if (!$this->enabled()) {
            return;
        }

        $key = $this->key($login);
        $limits = $this->limits();

        $record = $limits[$key] ?? [
            'attempts' => 0,
            'expires_at' => time() + $this->decaySeconds(),
        ];

        if (($record['expires_at'] ?? 0) <= time()) {
            $record = [
                'attempts' => 0,
                'expires_at' => time() + $this->decaySeconds(),
            ];
        }

        $record['attempts']++;

        $limits[$key] = $record;

        $this->session->put(self::SESSION_KEY, $limits);
    }

    public function clear(string $login): void
    {
        if (!$this->enabled()) {
            return;
        }

        $key = $this->key($login);
        $limits = $this->limits();

        unset($limits[$key]);

        $this->session->put(self::SESSION_KEY, $limits);
    }

    public function availableIn(string $login): int
    {
        $record = $this->record($login);

        if (!$record) {
            return 0;
        }

        return max(0, $record['expires_at'] - time());
    }

    private function record(string $login): ?array
    {
        $limits = $this->limits();
        $key = $this->key($login);

        $record = $limits[$key] ?? null;

        if (!$record) {
            return null;
        }

        if (($record['expires_at'] ?? 0) <= time()) {
            unset($limits[$key]);
            $this->session->put(self::SESSION_KEY, $limits);

            return null;
        }

        return $record;
    }

    private function limits(): array
    {
        $limits = $this->session->get(self::SESSION_KEY, []);

        return is_array($limits) ? $limits : [];
    }

    private function key(string $login): string
    {
        $mode = $this->config->rateLimit()['key'] ?? 'login_ip';

        $login = strtolower(trim($login));
        $ip = $this->ip();

        return match ($mode) {
            'login' => sha1('login|' . $login),
            'ip' => sha1('ip|' . $ip),
            default => sha1('login_ip|' . $login . '|' . $ip),
        };
    }

    private function maxAttempts(): int
    {
        return (int) ($this->config->rateLimit()['max_attempts'] ?? 5);
    }

    private function decaySeconds(): int
    {
        return (int) ($this->config->rateLimit()['decay_seconds'] ?? 300);
    }

    private function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? 'cli';
    }
}