<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PreventRequestForgery::class);
        $this->withoutVite();
    }

    public function test_it_creates_a_project(): void
    {
        $response = $this->post(route('projects.store'), [
            'name' => 'Backend Refactor',
        ]);

        $project = Project::first();
        $response->assertRedirect(route('tasks.index', ['project_id' => $project->id]));
        $this->assertDatabaseHas('projects', ['name' => 'Backend Refactor']);
    }

    public function test_it_creates_tasks_with_auto_incrementing_priority_per_project(): void
    {
        $project = Project::factory()->create();

        $this->post(route('tasks.store'), [
            'project_id' => $project->id,
            'name'       => 'First task',
        ]);

        $this->post(route('tasks.store'), [
            'project_id' => $project->id,
            'name'       => 'Second task',
        ]);

        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'name'       => 'First task',
            'priority'   => 1,
        ]);

        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'name'       => 'Second task',
            'priority'   => 2,
        ]);
    }

    public function test_it_filters_tasks_by_selected_project(): void
    {
        $projectA = Project::factory()->create(['name' => 'Alpha']);
        $projectB = Project::factory()->create(['name' => 'Beta']);

        Task::factory()->create(['project_id' => $projectA->id, 'name' => 'Alpha Task', 'priority' => 1]);
        Task::factory()->create(['project_id' => $projectB->id, 'name' => 'Beta Task', 'priority' => 1]);

        $response = $this->get(route('tasks.index', ['project_id' => $projectB->id]));

        $response->assertOk();
        $response->assertSee('Beta Task');
        $response->assertDontSee('Alpha Task');
    }

    public function test_it_updates_a_task_name(): void
    {
        $task = Task::factory()->create(['name' => 'Old Title']);

        $response = $this->put(route('tasks.update', $task), [
            'name' => 'Updated Title',
        ]);

        $response->assertRedirect(route('tasks.index', ['project_id' => $task->project_id]));
        $this->assertDatabaseHas('tasks', [
            'id'   => $task->id,
            'name' => 'Updated Title',
        ]);
    }

    public function test_it_deletes_a_task_and_reindexes_remaining_priorities(): void
    {
        $project = Project::factory()->create();
        $task1 = Task::factory()->create(['project_id' => $project->id, 'priority' => 1]);
        $task2 = Task::factory()->create(['project_id' => $project->id, 'priority' => 2]);
        $task3 = Task::factory()->create(['project_id' => $project->id, 'priority' => 3]);

        $response = $this->delete(route('tasks.destroy', $task1));

        $response->assertRedirect(route('tasks.index', ['project_id' => $project->id]));
        $this->assertDatabaseMissing('tasks', ['id' => $task1->id]);
        $this->assertDatabaseHas('tasks', ['id' => $task2->id, 'priority' => 1]);
        $this->assertDatabaseHas('tasks', ['id' => $task3->id, 'priority' => 2]);
    }

    public function test_it_reorders_tasks_via_drag_and_drop_endpoint(): void
    {
        $project = Project::factory()->create();
        $task1 = Task::factory()->create(['project_id' => $project->id, 'priority' => 1]);
        $task2 = Task::factory()->create(['project_id' => $project->id, 'priority' => 2]);
        $task3 = Task::factory()->create(['project_id' => $project->id, 'priority' => 3]);

        // Move task3 to #1, task1 to #2, task2 to #3
        $response = $this->postJson(route('tasks.reorder'), [
            'ids' => [$task3->id, $task1->id, $task2->id],
        ]);

        $response->assertOk()->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('tasks', ['id' => $task3->id, 'priority' => 1]);
        $this->assertDatabaseHas('tasks', ['id' => $task1->id, 'priority' => 2]);
        $this->assertDatabaseHas('tasks', ['id' => $task2->id, 'priority' => 3]);
    }

    public function test_it_validates_reorder_payload_rejects_duplicate_or_missing_ids(): void
    {
        $task = Task::factory()->create();

        $response = $this->postJson(route('tasks.reorder'), [
            'ids' => [$task->id, $task->id], // Duplicate ID should fail 'distinct' rule
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['ids.1']);
    }
}
