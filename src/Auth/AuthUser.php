<?php

namespace Eril\Auth\Auth;

use Eril\Auth\Auth;
use Eril\Auth\Authorization\Authorization;
use Eril\Auth\Profile\Profile;
use Eril\Auth\Support\DataObject;

final class AuthUser extends DataObject
{
    public function id(): int|string|null
    {
        return $this->get('id');
    }

    public function name(): ?string
    {
        return $this->get('name');
    }

    public function login(): ?string
    {
        return $this->get('login');
    }

    public function role(): ?string
    {
        return $this->get('role');
    }

    public function hasRole(string $role, string ...$roles): bool
    {
        $current = $this->role();

        return $current !== null
            && in_array($current, [$role, ...$roles], true);
    }

    public function is(string $role): bool
    {
        return $this->hasRole($role);
    }

    public function can(string $permission): bool
    {
        return Authorization::can($permission);
    }

    public function cannot(string $permission): bool
    {
        return Authorization::cannot($permission);
    }

    public function profile(): ?Profile
    {
        return Auth::profile();
    }

    /**
     * @return array<string,mixed>
     */
    public function raw(): array
    {
        return $this->get('raw', []);
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        $data = $this->toArray();

        unset($data['raw']);

        return $data;
    }
}