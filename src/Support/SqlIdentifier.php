<?php

namespace Eril\Auth\Support;

use InvalidArgumentException;

final class SqlIdentifier
{
    public static function validate(string $value, string $name = 'identifier'): string
    {
        $value = trim($value);

        if ($value === '') {
            throw new InvalidArgumentException("Invalid {$name}: empty value.");
        }

        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $value)) {
            throw new InvalidArgumentException("Invalid {$name}: {$value}.");
        }

        return $value;
    }

    public static function nullable(?string $value, string $name = 'identifier'): ?string
    {
        if ($value === null) {
            return null;
        }

        return self::validate($value, $name);
    }
}