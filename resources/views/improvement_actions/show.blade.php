
@extends('layouts.app')

@section('title', 'Improvement Action Details')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Improvement Action Details</h2>
        <p class="text-muted mb-0">
            {{ $improvementAction->event->title ?? 'Event #' . $improvementAction->event_id }}
        </p>
    </div>

    <a href="{{ route('improvement-actions.index') }}"
       class="btn btn-outline-secondary">
        Back to Actions
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

<div class="card shadow-sm mb-4">
    <div class="card-body">

        <div class="d-flex justify-content-between align-items-start mb-3">
            <h4>{{ $improvementAction->title }}</h4>

            @if($improvementAction->status === 'completed')
                <span class="badge bg-success">Completed</span>
            @elseif($improvementAction->status === 'in_progress')
                <span class="badge bg-primary">In Progress</span>
            @else
                <span class="badge bg-warning text-dark">Pending</span>
            @endif
        </div>

        <h6>Description</h6>
        <p>{{ $improvementAction->description }}</p>

        <hr>

        <div class="row">
            <div class="col-md-6 mb-3">
                <strong>Event</strong>
                <p class="mb-0">
                    {{ $improvementAction->event->title ?? 'Event #' . $improvementAction->event_id }}
                </p>
            </div>

            <div class="col-md-6 mb-3">
                <strong>Created By</strong>
                <p class="mb-0">
                    {{ $improvementAction->creator->name ?? 'Unknown' }}
                </p>
            </div>

            <div class="col-md-6 mb-3">
                <strong>Due Date</strong>
                <p class="mb-0">
                    {{ $improvementAction->due_date
                        ? $improvementAction->due_date->format('d M Y')
                        : 'Not set' }}
                </p>
            </div>

            <div class="col-md-6 mb-3">
                <strong>Created On</strong>
                <p class="mb-0">
                    {{ $improvementAction->created_at->format('d M Y, h:i A') }}
                </p>
            </div>
        </div>

        <hr>

        <h5 class="mb-3">Related Feedback</h5>

        @if($improvementAction->feedback)
            <p>
                <strong>Submitted By:</strong>
                {{ $improvementAction->feedback->user->name ?? 'Unknown' }}
            </p>

            <p>
                <strong>Rating:</strong>
                {{ $improvementAction->feedback->rating }} / 5
            </p>

            <p class="mb-0">
                <strong>Comments:</strong>
                {{ $improvementAction->feedback->comments ?: 'No comments provided.' }}
            </p>
        @else
            <p class="text-muted mb-0">
                This action is not linked to a specific feedback submission.
            </p>
        @endif

    </div>
</div>

<div class="d-flex gap-2">
    <a href="{{ route('improvement-actions.edit', $improvementAction) }}"
       class="btn btn-primary">
        Edit Action
    </a>

    <form action="{{ route('improvement-actions.destroy', $improvementAction) }}"
          method="POST"
          onsubmit="return confirm('Are you sure you want to delete this improvement action?');">

        @csrf
        @method('DELETE')

        <button type="submit" class="btn btn-outline-danger">
            Delete Action
        </button>
    </form>
</div>

@endsection
