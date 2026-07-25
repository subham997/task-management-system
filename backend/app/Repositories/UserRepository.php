<?php

namespace App\Repositories;

use App\Models\Role;
use App\Models\User;

class UserRepository
{
    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): User
    {
        return User::query()->create($attributes);
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()->with('role')->where('email', $email)->first();
    }

    public function findRoleByName(string $name): ?Role
    {
        return Role::query()->where('name', $name)->first();
    }
}
