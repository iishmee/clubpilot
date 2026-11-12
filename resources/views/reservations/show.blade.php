@extends('layouts.app')

@section('title', 'Venue request · ClubPilot')

@section('content')
    <h1 class="h2">Venue request</h1>
    @if ($reservation->event)
        <p><a href="{{ route('events.show', $reservation->event) }}">{{ $reservation->event->title ?: 'Untitled legacy event' }}</a></p>
    @else
        <p>Legacy reservation without an event link.</p>
    @endif
    <dl class="row">
        <dt class="col-sm-3">Venue</dt><dd class="col-sm-9">{{ $reservation->venue?->name ?? 'Unassigned venue' }} · {{ $reservation->venue?->location ?? 'Location unavailable' }} (capacity {{ $reservation->venue?->capacity ?? 'unknown' }})</dd>
        <dt class="col-sm-3">Requested time</dt><dd class="col-sm-9">{{ $reservation->starts_at?->format('M j, Y H:i') ?? 'Unscheduled' }} – {{ $reservation->ends_at?->format('M j, Y H:i') ?? 'Unscheduled' }}</dd>
        <dt class="col-sm-3">Status</dt><dd class="col-sm-9">{{ str($reservation->status)->title() }}</dd>
        <dt class="col-sm-3">Requested by</dt><dd class="col-sm-9">{{ $reservation->requester?->name ?? 'Unknown requester' }}</dd>
        @if ($reservation->review_feedback)
            <dt class="col-sm-3">Review feedback</dt><dd class="col-sm-9">{{ $reservation->review_feedback }}</dd>
        @endif
    </dl>

    @if (auth()->user()->isAdmin() && $reservation->status === \App\Models\Reservation::STATUS_PENDING)
        <section class="card card-body mt-4">
            <h2 class="h5">Review venue request</h2>
            <form method="POST" action="{{ route('reservations.review', $reservation) }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="decision">Decision</label>
                    <select class="form-select" name="decision" id="decision" required>
                        <option value="approved">Approve</option>
                        <option value="rejected">Reject</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="feedback">Feedback (required for rejection)</label>
                    <textarea class="form-control" name="feedback" id="feedback" rows="3"></textarea>
                    @error('feedback')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <button class="btn btn-primary">Record decision</button>
            </form>
        </section>
    @endif
@endsection
