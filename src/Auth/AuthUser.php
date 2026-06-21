<?php

namespace Eril\Auth\Auth;

use JsonSerializable;

final class AuthUser implements JsonSerializable
{
    public function __construct(
        private readonly array $attributes
    ) {}

    public function id(): int|string|null
    {
        return $this->attributes['id'] ?? null;
    }

    public function name(): ?string
    {
        return $this->attributes['name'] ?? null;
    }

    public function login(): ?string
    {
        return $this->attributes['login'] ?? null;
    }

    public function role(): ?string
    {
        return $this->attributes['role'] ?? null;
    }

    public function hasRole(string $role, string ...$roles): bool
    {
        $current = $this->role();

        if (!$current) {
            return false;
        }

        return in_array($current, [$role, ...$roles], true);
    }

    public function is(string $role): bool
    {
        return $this->hasRole($role);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function raw(): array
    {
        return $this->attributes['raw'] ?? [];
    }

    public function only(string ...$keys): array
    {
        $result = [];

        foreach ($keys as $key) {
            if (array_key_exists($key, $this->attributes)) {
                $result[$key] = $this->attributes[$key];
            }
        }

        return $result;
    }

    public function except(string ...$keys): array
    {
        return array_diff_key(
            $this->attributes,
            array_flip($keys)
        );
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    public function jsonSerialize(): array
    {
        return $this->except('raw');
    }
}