<?php

namespace App\Http\Controllers;
use App\Http\Requests\StoreProjectRequest;
use App\Models\Project;

use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function store(StoreProjectRequest $request)
    {
        $project = Project::create($request->validated());

        return redirect()
                ->route('tasks.index', ['project_id' => $project->id])
                ->with('success', "Project \"{$project->name}\" created.");
    }
}
