<?php

namespace Eril\Auth;

final class AuthUser
{
    public function __construct(
        private array $attributes
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

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function toArray(): array
    {
        return $this->attributes;
    }
}