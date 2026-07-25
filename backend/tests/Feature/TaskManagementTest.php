<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        collect(['Admin', 'Manager', 'Employee'])->each(function (string $name): void {
            Role::query()->create(['name' => $name]);
        });
    }

    public function test_manager_can_create_read_update_and_delete_a_task(): void
    {
        $manager = $this->userWithRole('Manager');
        Sanctum::actingAs($manager);

        $createResponse = $this->postJson('/api/tasks', [
            'title' => 'Prepare sprint report',
            'description' => 'Summarize task delivery.',
            'priority' => 'high',
            'due_at' => '2026-08-01 09:00:00',
        ]);

        $taskId = $createResponse
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.task.title', 'Prepare sprint report')
            ->json('data.task.id');

        $this->getJson("/api/tasks/{$taskId}")
            ->assertOk()
            ->assertJsonPath('data.task.creator.id', $manager->id);

        $this->patchJson("/api/tasks/{$taskId}", [
            'status' => 'completed',
        ])
            ->assertOk()
            ->assertJsonPath('data.task.status', 'completed');

        $this->assertDatabaseMissing('tasks', [
            'id' => $taskId,
            'completed_at' => null,
        ]);

        $this->deleteJson("/api/tasks/{$taskId}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('tasks', ['id' => $taskId]);
    }

    public function test_employee_can_only_view_and_manage_their_own_tasks(): void
    {
        $employee = $this->userWithRole('Employee');
        $otherEmployee = $this->userWithRole('Employee');
        $ownTask = Task::factory()->create(['created_by' => $employee->id]);
        $otherTask = Task::factory()->create(['created_by' => $otherEmployee->id]);

        Sanctum::actingAs($employee);

        $this->getJson('/api/tasks')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownTask->id);

        $this->getJson("/api/tasks/{$otherTask->id}")
            ->assertForbidden();

        $this->patchJson("/api/tasks/{$otherTask->id}", ['title' => 'Not allowed'])
            ->assertForbidden();

        $this->deleteJson("/api/tasks/{$otherTask->id}")
            ->assertForbidden();
    }

    public function test_manager_can_filter_search_sort_and_paginate_tasks(): void
    {
        $manager = $this->userWithRole('Manager');
        $creator = $this->userWithRole('Employee');

        Task::factory()->create([
            'created_by' => $creator->id,
            'title' => 'Zeta release notes',
            'status' => 'pending',
            'priority' => 'high',
            'due_at' => '2026-08-10',
        ]);
        Task::factory()->create([
            'created_by' => $creator->id,
            'title' => 'Alpha release checklist',
            'status' => 'pending',
            'priority' => 'medium',
            'due_at' => '2026-08-05',
        ]);
        Task::factory()->create([
            'created_by' => $creator->id,
            'title' => 'Completed release',
            'status' => 'completed',
        ]);

        Sanctum::actingAs($manager);

        $this->getJson('/api/tasks?status=pending&search=release&sort_by=title&sort_direction=asc&per_page=1')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Alpha release checklist')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.per_page', 1);
    }

    public function test_task_validation_rejects_invalid_status_and_priority(): void
    {
        Sanctum::actingAs($this->userWithRole('Employee'));

        $this->postJson('/api/tasks', [
            'title' => '',
            'status' => 'unknown',
            'priority' => 'urgent',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'status', 'priority']);
    }

    private function userWithRole(string $roleName): User
    {
        return User::factory()->create([
            'role_id' => Role::query()->where('name', $roleName)->value('id'),
            'status' => true,
        ]);
    }
}
