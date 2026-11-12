<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReorderTasksRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::orderBy('name')->get();
        $selectedProjectId = $request->query('project_id', $projects->first()?->id);

        $tasks = Task::where('project_id', $selectedProjectId)
            ->orderBy('priority')
            ->get();

        return view('tasks.index', compact('projects', 'selectedProjectId', 'tasks'));
    }

    public function store(StoreTaskRequest $request)
    {
        $validated = $request->validated();

        $nextPriority = (Task::where('project_id', $validated['project_id'])->max('priority') ?? 0) + 1;

        Task::create([
            'project_id' => $validated['project_id'],
            'name'       => $validated['name'],
            'priority'   => $nextPriority,
        ]);

        return redirect()->route('tasks.index', ['project_id' => $validated['project_id']]);
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        $task->update($request->validated());

        return redirect()->route('tasks.index', ['project_id' => $task->project_id]);
    }

    public function destroy(Task $task)
    {
        $projectId = $task->project_id;
        $task->delete();

        $remainingTasks = Task::where('project_id', $projectId)->orderBy('priority')->get();
        foreach ($remainingTasks as $index => $item) {
            $item->update(['priority' => $index + 1]);
        }

        return redirect()->route('tasks.index', ['project_id' => $projectId]);
    }

    public function reorder(ReorderTasksRequest $request)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            foreach ($validated['ids'] as $index => $id) {
                Task::where('id', $id)->update(['priority' => $index + 1]);
            }
        });

        return response()->json(['status' => 'success']);
    }
}
