<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::query()->create([
            'name' => 'Employee',
            'description' => 'Employee role',
        ]);
    }

    public function test_user_can_register_and_receives_a_sanctum_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'department' => 'Engineering',
            'designation' => 'Developer',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'jane@example.com')
            ->assertJsonPath('data.user.role.name', 'Employee')
            ->assertJsonStructure(['data' => ['token']]);

        $user = User::query()->where('email', 'jane@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_registered_user_can_log_in_and_access_their_profile(): void
    {
        $user = User::factory()->create([
            'role_id' => Role::query()->where('name', 'Employee')->value('id'),
            'password' => Hash::make('password123'),
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $token = $loginResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->json('data.token');

        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.role.name', 'Employee');
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create(['password' => Hash::make('password123')]);

        $this->postJson('/api/auth/login', [
            'email' => 'missing@example.com',
            'password' => 'password123',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_authenticated_user_can_log_out_and_revoke_the_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
