<?php

namespace Eril\Auth\Support;

use ArrayAccess;
use JsonSerializable;
use LogicException;

/**
 * @implements ArrayAccess<string,mixed>
 */
abstract class DataObject implements ArrayAccess, JsonSerializable
{
    /**
     * @param array<string,mixed> $attributes
     */
    public function __construct(
        protected readonly array $attributes
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    /**
     * @return array<string,mixed>
     */
    public function all(): array
    {
        return $this->toArray();
    }


    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->has((string) $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get((string) $offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException(static::class . ' is read-only.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException(static::class . ' is read-only.');
    }
}