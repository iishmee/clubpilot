@extends('layouts.app')

@section('title', 'My tasks · ClubPilot')

@section('content')
    <h1 class="h2 mb-3">My tasks</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    @forelse ($tasks as $task)
        <article class="border rounded p-3 mb-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <strong>{{ $task->title }}</strong>
                    <span class="badge text-bg-secondary ms-2">{{ str($task->status)->replace('_', ' ')->title() }}</span>
                    <div class="text-muted">
                        {{ $task->opportunity?->event?->title }} · {{ $task->opportunity?->title }}
                        @if ($task->due_at) · Due {{ $task->due_at->format('M j, Y H:i') }} @endif
                    </div>
                    @if ($task->description)<p class="mb-0 mt-1">{{ $task->description }}</p>@endif
                </div>
            </div>
            <form method="POST" action="{{ route('tasks.status', $task) }}" class="d-flex gap-2 mt-2">
                @csrf
                <select class="form-select form-select-sm" name="status">
                    @foreach (\App\Models\VolunteerTask::STATUSES as $status)
                        <option value="{{ $status }}" @selected($task->status === $status)>{{ str($status)->replace('_', ' ')->title() }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-primary">Update status</button>
            </form>
        </article>
    @empty
        <p>You have no assigned tasks yet.</p>
    @endforelse

    <p class="mt-4"><a href="{{ route('volunteers.index') }}">← Back to volunteering</a></p>
@endsection
