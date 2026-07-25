<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiExceptionHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        collect(['Manager', 'Employee'])->each(function (string $name): void {
            Role::query()->create(['name' => $name]);
        });
    }

    public function test_unauthenticated_api_requests_use_the_standard_error_envelope(): void
    {
        $this->getJson('/api/tasks')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthenticated.')
            ->assertJsonStructure(['errors']);
    }

    public function test_validation_failures_use_the_standard_error_envelope(): void
    {
        Sanctum::actingAs($this->userWithRole('Employee'));

        $this->postJson('/api/tasks', ['title' => '', 'status' => 'invalid'])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'The given data was invalid.')
            ->assertJsonValidationErrors(['title', 'status']);
    }

    public function test_missing_models_use_the_standard_error_envelope(): void
    {
        Sanctum::actingAs($this->userWithRole('Manager'));

        $this->getJson('/api/tasks/99999')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'The requested resource was not found.');
    }

    public function test_authorization_failures_use_the_standard_error_envelope(): void
    {
        $user = $this->userWithRole('Employee');
        $task = Task::factory()->create(['created_by' => $this->userWithRole('Employee')->id]);
        Sanctum::actingAs($user);

        $this->getJson("/api/tasks/{$task->id}")
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'This action is unauthorized.');
    }

    private function userWithRole(string $roleName): User
    {
        return User::factory()->create([
            'role_id' => Role::query()->where('name', $roleName)->value('id'),
            'status' => true,
        ]);
    }
}
