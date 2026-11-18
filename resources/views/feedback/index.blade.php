
@extends('layouts.app')

@section('title', 'Event Feedback')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Event Feedback</h2>
        <p class="text-muted mb-0">
            Review feedback and evaluate event performance.
        </p>
    </div>

    @if(auth()->user()->role === 'member')
        <a href="{{ route('feedback.create') }}" class="btn btn-primary">
            Submit Feedback
        </a>
    @endif
</div>

@if(auth()->user()->isAdmin() || auth()->user()->isOrganizer())

    <h5 class="mb-3">Event Rating Summaries</h5>

    @if($feedbacks->isNotEmpty())

        @foreach($feedbacks->groupBy('event_id') as $eventFeedbacks)

            <div class="card shadow-sm mb-3">
                <div class="card-body">

                    <h6>
                        {{ $eventFeedbacks->first()->event->title ?? 'Event #' . $eventFeedbacks->first()->event_id }}
                    </h6>

                    <div class="row">
                        <div class="col-md-6">
                            <span class="text-muted">Average Rating</span>
                            <h4 class="text-primary">
                                {{ number_format($eventFeedbacks->avg('rating'), 1) }} / 5
                            </h4>
                        </div>

                        <div class="col-md-6">
                            <span class="text-muted">Total Responses</span>
                            <h4>
                                {{ $eventFeedbacks->count() }}
                            </h4>
                        </div>
                    </div>

                </div>
            </div>

        @endforeach

    @else
        <div class="alert alert-info">
            No feedback has been submitted for your events yet.
        </div>
    @endif

@endif

<h5 class="mb-3 mt-4">Feedback Submissions</h5>

@if($feedbacks->isEmpty())

    <div class="alert alert-info">
        No feedback submissions found.
    </div>

@else

    <div class="card shadow-sm">
        <div class="card-body p-0">

            <div class="table-responsive">
                <table class="table table-hover mb-0">

                    <thead class="table-light">
                        <tr>
                            <th>Event</th>
                            <th>Submitted By</th>
                            <th>Rating</th>
                            <th>Comments</th>
                            <th>Date Submitted</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($feedbacks as $feedback)
                            <tr>
                                <td>
                                    {{ $feedback->event->title ?? 'Event #' . $feedback->event_id }}
                                </td>

                                <td>
                                    {{ $feedback->user->name ?? 'Unknown' }}
                                </td>

                                <td>
                                    {{ $feedback->rating }} / 5
                                </td>

                                <td>
                                    {{ $feedback->comments ?: 'No comments' }}
                                </td>

                                <td>
                                    {{ $feedback->created_at->format('d M Y') }}
                                </td>

                                <td>
                                    <a href="{{ route('feedback.show', $feedback) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>

        </div>
    </div>

@endif

@endsection
