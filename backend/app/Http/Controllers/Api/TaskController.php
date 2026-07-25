<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListTaskRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\TaskService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly TaskService $taskService) {}

    public function index(ListTaskRequest $request)
    {
        Gate::authorize('viewAny', Task::class);

        return TaskResource::collection(
            $this->taskService->list($request->user(), $request->validated())
        )->additional([
            'success' => true,
            'message' => 'Tasks retrieved successfully.',
        ]);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        Gate::authorize('create', Task::class);

        return $this->successResponse(
            ['task' => new TaskResource($this->taskService->create($request->user(), $request->validated()))],
            'Task created successfully.',
            201
        );
    }

    public function show(Task $task): JsonResponse
    {
        Gate::authorize('view', $task);

        return $this->successResponse(['task' => new TaskResource($this->taskService->details($task->id))]);
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        Gate::authorize('update', $task);

        return $this->successResponse(
            ['task' => new TaskResource($this->taskService->update($request->user(), $task, $request->validated()))],
            'Task updated successfully.'
        );
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        Gate::authorize('delete', $task);
        $this->taskService->delete($request->user(), $task);

        return $this->successResponse(message: 'Task deleted successfully.');
    }
}
