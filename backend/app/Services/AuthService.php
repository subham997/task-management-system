<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthService
{
    public function __construct(private readonly UserRepository $users) {}

    /** @param array<string, mixed> $attributes */
    public function register(array $attributes): User
    {
        return DB::transaction(function () use ($attributes): User {
            $employeeRole = $this->users->findRoleByName('Employee');

            return $this->users->create([
                ...$attributes,
                'role_id' => $employeeRole?->id,
                'status' => true,
            ])->load('role');
        });
    }

    /** @param array<string, mixed> $credentials */
    public function authenticate(array $credentials): ?User
    {
        $user = $this->users->findByEmail($credentials['email']);

        if ($user === null || ! $user->status || ! Hash::check($credentials['password'], $user->password)) {
            Log::warning('auth.login_failed', ['email' => $credentials['email']]);

            return null;
        }

        Log::info('auth.login_succeeded', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return $user;
    }

    /** @return array{user: User, token: string} */
    public function issueToken(User $user, string $tokenName = 'auth-token'): array
    {
        return [
            'user' => $user,
            'token' => $user->createToken($tokenName)->plainTextToken,
        ];
    }

    public function revokeCurrentToken(User $user): void
    {
        $user->currentAccessToken()?->delete();

        Log::info('auth.logout_succeeded', ['user_id' => $user->id]);
    }
}
