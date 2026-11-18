
@extends('layouts.app')

@section('title', 'Feedback Details')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Feedback Details</h2>
        <p class="text-muted mb-0">
            {{ $feedback->event->title ?? 'Event #' . $feedback->event_id }}
        </p>
    </div>

    <a href="{{ route('feedback.index') }}"
       class="btn btn-outline-secondary">
        Back to Feedback
    </a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">

        <h5 class="card-title mb-3">Feedback Information</h5>

        <p>
            <strong>Event:</strong>
            {{ $feedback->event->title ?? 'Event #' . $feedback->event_id }}
        </p>

        <p>
            <strong>Submitted By:</strong>
            {{ $feedback->user->name ?? 'Unknown' }}
        </p>

        <p>
            <strong>Rating:</strong>

            <span class="badge bg-primary">
                {{ $feedback->rating }} / 5
            </span>
        </p>

        <p>
            <strong>Date Submitted:</strong>
            {{ $feedback->created_at->format('d M Y, h:i A') }}
        </p>

        <hr>

        <h5 class="mb-3">Comments</h5>

        @if($feedback->comments)
            <p class="mb-0">
                {{ $feedback->comments }}
            </p>
        @else
            <p class="text-muted mb-0">
                No additional comments were provided.
            </p>
        @endif

    </div>
</div>

@endsection
