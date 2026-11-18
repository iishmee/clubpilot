@extends('layouts.app')

@section('title', 'Tasks · ClubPilot')

@section('content')
    <p class="text-muted mb-1">{{ $opportunity->event?->title }}</p>
    <h1 class="h2 mb-3">Tasks: {{ $opportunity->title }}</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    @if ($canManage)
        <section class="card card-body mb-4">
            <h2 class="h5">Add a task</h2>
            <form method="POST" action="{{ route('tasks.store', $opportunity) }}" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label class="form-label" for="title">Title</label>
                    <input class="form-control" id="title" name="title" value="{{ old('title') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="assigned_to">Assign to (optional)</label>
                    <select class="form-select" id="assigned_to" name="assigned_to">
                        <option value="">Unassigned</option>
                        @foreach ($volunteers as $volunteer)
                            <option value="{{ $volunteer->id }}" @selected(old('assigned_to') == $volunteer->id)>{{ $volunteer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="due_at">Due (optional)</label>
                    <input class="form-control" type="datetime-local" id="due_at" name="due_at" value="{{ old('due_at') }}">
                </div>
                <div class="col-12">
                    <label class="form-label" for="description">Description (optional)</label>
                    <textarea class="form-control" id="description" name="description" rows="2">{{ old('description') }}</textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary">Add task</button>
                </div>
            </form>
        </section>
    @endif

    @forelse ($tasks as $task)
        <article class="border rounded p-3 mb-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <strong>{{ $task->title }}</strong>
                    <span class="badge text-bg-secondary ms-2">{{ str($task->status)->replace('_', ' ')->title() }}</span>
                    <div class="text-muted">
                        Assigned to: {{ $task->assignee?->name ?? 'Nobody yet' }}
                        @if ($task->due_at) · Due {{ $task->due_at->format('M j, Y H:i') }} @endif
                    </div>
                    @if ($task->description)<p class="mb-0 mt-1">{{ $task->description }}</p>@endif
                </div>
                @if ($canManage)
                    <form method="POST" action="{{ route('tasks.destroy', $task) }}">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                @endif
            </div>

            @if ($canManage)
                <div class="row g-2 mt-2">
                    <form method="POST" action="{{ route('tasks.assign', $task) }}" class="col-md-6 d-flex gap-2">
                        @csrf
                        <select class="form-select form-select-sm" name="assigned_to">
                            <option value="">Unassigned</option>
                            @foreach ($volunteers as $volunteer)
                                <option value="{{ $volunteer->id }}" @selected($task->assigned_to === $volunteer->id)>{{ $volunteer->name }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-sm btn-outline-primary">Reassign</button>
                    </form>
                    <form method="POST" action="{{ route('tasks.status', $task) }}" class="col-md-6 d-flex gap-2">
                        @csrf
                        <select class="form-select form-select-sm" name="status">
                            @foreach (\App\Models\VolunteerTask::STATUSES as $status)
                                <option value="{{ $status }}" @selected($task->status === $status)>{{ str($status)->replace('_', ' ')->title() }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-sm btn-outline-secondary">Set status</button>
                    </form>
                </div>
            @endif
        </article>
    @empty
        <p>No tasks yet.</p>
    @endforelse

    <p class="mt-4"><a href="{{ route('volunteers.show', $opportunity) }}">← Back to opportunity</a></p>
@endsection
