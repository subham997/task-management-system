<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role = Role::query()
            ->whereIn('name', ['Admin', 'Manager'])
            ->first() ?? Role::query()->first();

        User::query()->updateOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Demo Manager',
                'password' => Hash::make('password123'),
                'role_id' => $role?->id,
                'department' => 'Operations',
                'designation' => 'Demo Manager',
                'status' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
