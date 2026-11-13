<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Laravel Task Manager</title>
    <script src="https://tailwindcss.com"></script>
    <script src="https://jsdelivr.net"></script>
</head>
<body class="bg-slate-100 text-slate-800 antialiased">
    <div class="mx-auto max-w-4xl px-4 py-10">
        <h1 class="mb-6 text-2xl font-bold text-slate-900">Task Manager</h1>

        <!-- Validation Errors -->
        @if ($errors->any())
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Project Selector & New Project Form -->
        <div class="mb-6 grid gap-4 rounded-lg bg-white p-5 shadow-sm sm:grid-cols-2">
            <form method="GET" action="{{ route('tasks.index') }}">
                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Select Project</label>
                <select name="project_id" onchange="this.form.submit()" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                    @forelse ($projects as $project)
                        <option value="{{ $project->id }}" @selected($project->id == $selectedProjectId)>
                            {{ $project->name }}
                        </option>
                    @empty
                        <option value="">No projects yet</option>
                    @endforelse
                </select>
            </form>

            <form method="POST" action="{{ route('projects.store') }}">
                @csrf
                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">New Project</label>
                <div class="flex gap-2">
                    <input type="text" name="name" required placeholder="e.g. Marketing Q4" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                    <button type="submit" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Add</button>
                </div>
            </form>
        </div>

        <!-- Add Task Form -->
        @if ($selectedProjectId)
            <form method="POST" action="{{ route('tasks.store') }}" class="mb-6 flex gap-3 rounded-lg bg-white p-5 shadow-sm">
                @csrf
                <input type="hidden" name="project_id" value="{{ $selectedProjectId }}">
                <input type="text" name="name" required placeholder="Enter new task name..." class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                <button type="submit" class="whitespace-nowrap rounded-md bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    + Add Task
                </button>
            </form>
        @endif

        <!-- Drag-and-Drop Tasks Table -->
        <div class="overflow-hidden rounded-lg bg-white shadow-sm">
            <table class="w-full border-collapse text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="w-20 p-4">Priority</th>
                        <th class="p-4">Task Name</th>
                        <th class="w-40 p-4">Created</th>
                        <th class="w-40 p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="sortable-tasks" class="divide-y divide-slate-200">
                    @forelse ($tasks as $task)
                        <tr data-id="{{ $task->id }}" class="cursor-grab bg-white hover:bg-slate-50 active:cursor-grabbing">
                            <td class="priority-cell p-4 font-bold text-blue-600">#{{ $task->priority }}</td>
                            <td class="p-4">
                                <span id="task-label-{{ $task->id }}">{{ $task->name }}</span>
                                <form id="edit-form-{{ $task->id }}" method="POST" action="{{ route('tasks.update', $task) }}" class="hidden flex gap-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="name" value="{{ $task->name }}" required class="w-full rounded border border-slate-300 px-2 py-1 text-sm">
                                    <button type="submit" class="rounded bg-green-600 px-2.5 py-1 text-xs text-white hover:bg-green-700">Save</button>
                                    <button type="button" onclick="toggleEdit({{ $task->id }})" class="rounded bg-slate-200 px-2.5 py-1 text-xs text-slate-700">Cancel</button>
                                </form>
                            </td>
                            <td class="p-4 text-xs text-slate-500">{{ $task->created_at->format('M d, Y H:i') }}</td>
                            <td class="p-4 text-right">
                                <button type="button" onclick="toggleEdit({{ $task->id }})" class="mr-2 text-xs font-medium text-blue-600 hover:underline">Edit</button>
                                <form method="POST" action="{{ route('tasks.destroy', $task) }}" class="inline" onsubmit="return confirm('Delete this task?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-medium text-red-600 hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-8 text-center text-slate-400">No tasks found for this project. Add one above!</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function toggleEdit(id) {
            document.getElementById(`task-label-${id}`).classList.toggle('hidden');
            document.getElementById(`edit-form-${id}`).classList.toggle('hidden');
        }

        const tbody = document.getElementById('sortable-tasks');
        if (tbody && tbody.querySelector('tr[data-id]')) {
            Sortable.create(tbody, {
                animation: 150,
                ghostClass: 'bg-blue-50',
                onEnd: async function () {
                    const rows = Array.from(tbody.querySelectorAll('tr[data-id]'));

                    rows.forEach((row, index) => {
                        row.querySelector('.priority-cell').textContent = `#${index + 1}`;
                    });

                    const orderedIds = rows.map(row => row.dataset.id);
                    await fetch('{{ route('tasks.reorder') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ ids: orderedIds })
                    });
                }
            });
        }
    </script>
</body>
</html>
