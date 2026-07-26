<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskAuthorizationAndValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        collect(['Admin', 'Manager', 'Employee'])->each(function (string $name): void {
            Role::query()->create(['name' => $name]);
        });
    }

    public function test_admin_can_manage_tasks_created_by_other_users(): void
    {
        $admin = $this->userWithRole('Admin');
        $task = Task::factory()->create(['created_by' => $this->userWithRole('Employee')->id]);
        Sanctum::actingAs($admin);

        $this->patchJson("/api/tasks/{$task->id}", ['priority' => 'high'])
            ->assertOk()
            ->assertJsonPath('data.task.priority', 'high');

        $this->deleteJson("/api/tasks/{$task->id}")
            ->assertOk();

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_task_creation_validates_required_fields_and_due_dates(): void
    {
        Sanctum::actingAs($this->userWithRole('Employee'));

        $this->postJson('/api/tasks', [
            'title' => '',
            'priority' => 'critical',
            'status' => 'unknown',
            'due_at' => 'not-a-date',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'priority', 'status', 'due_at']);
    }

    private function userWithRole(string $roleName): User
    {
        return User::factory()->create([
            'role_id' => Role::query()->where('name', $roleName)->value('id'),
            'status' => true,
        ]);
    }
}
