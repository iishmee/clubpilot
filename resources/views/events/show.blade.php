@extends('layouts.app')

@section('title', ($event->title ?: 'Untitled event').' · ClubPilot')

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <p class="text-muted mb-1">{{ $event->club_name }}</p>
            <h1 class="h2">{{ $event->title ?: 'Untitled legacy event' }}</h1>
        </div>
        <span class="badge text-bg-primary">{{ str($event->proposal_status)->replace('_', ' ')->title() }}</span>
    </div>

    <dl class="row">
        <dt class="col-sm-3">Organizer</dt><dd class="col-sm-9">{{ $event->organizer?->name ?? 'Unassigned legacy event' }}</dd>
        <dt class="col-sm-3">Proposed time</dt><dd class="col-sm-9">{{ $event->starts_at?->format('M j, Y H:i') ?? 'Not scheduled' }} – {{ $event->ends_at?->format('M j, Y H:i') ?? 'Not scheduled' }}</dd>
        <dt class="col-sm-3">Description</dt><dd class="col-sm-9">{!! nl2br(e($event->description ?? 'No description available.')) !!}</dd>
        <dt class="col-sm-3">Objectives</dt><dd class="col-sm-9">{!! nl2br(e($event->objectives ?? 'No objectives available.')) !!}</dd>
        <dt class="col-sm-3">Requirements</dt><dd class="col-sm-9">{{ $event->requirements ?: 'None specified' }}</dd>
        @if ($event->review_feedback)
            <dt class="col-sm-3">Latest review feedback</dt><dd class="col-sm-9">{!! nl2br(e($event->review_feedback)) !!}</dd>
        @endif
    </dl>

    @can('update', $event)
        <a class="btn btn-outline-primary" href="{{ route('events.edit', $event) }}">Edit proposal</a>
        @if (in_array($event->proposal_status, [\App\Models\Event::STATUS_DRAFT, \App\Models\Event::STATUS_REVISION_REQUESTED], true))
            <form class="d-inline" method="POST" action="{{ route('events.submit', $event) }}">
                @csrf
                <button class="btn btn-primary">Submit for review</button>
            </form>
        @endif
    @endcan

    @if (auth()->user()->isAdmin() && $event->proposal_status === \App\Models\Event::STATUS_PENDING_REVIEW)
        <section class="card card-body my-4">
            <h2 class="h5">Review proposal</h2>
            <form method="POST" action="{{ route('events.review', $event) }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="decision">Decision</label>
                    <select class="form-select" name="decision" id="decision" required>
                        <option value="approved">Approve</option>
                        <option value="revision_requested">Request revision</option>
                        <option value="rejected">Reject</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="feedback">Feedback (required for revision or rejection)</label>
                    <textarea class="form-control" name="feedback" id="feedback" rows="3"></textarea>
                    @error('feedback')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <button class="btn btn-primary">Record decision</button>
            </form>
        </section>
    @endif

    @if ($event->proposal_status === \App\Models\Event::STATUS_APPROVED && auth()->user()->isOrganizer() && auth()->id() === $event->organizer_id)
        <a class="btn btn-primary my-3" href="{{ route('events.reservations.create', $event) }}">Request a venue</a>
    @endif

    <h2 class="h4 mt-4">Review history</h2>
    @forelse ($event->reviews as $review)
        <article class="border rounded p-3 mb-2">
            <strong>{{ str($review->decision)->replace('_', ' ')->title() }}</strong>
            <span class="text-muted"> · {{ $review->reviewed_at->format('M j, Y H:i') }} by {{ $review->reviewer?->name ?? 'Former reviewer' }}</span>
            @if ($review->feedback)<p class="mb-0 mt-2">{{ $review->feedback }}</p>@endif
        </article>
    @empty
        <p>No review decisions yet.</p>
    @endforelse

    <h2 class="h4 mt-4">Venue reservations</h2>
    @forelse ($event->reservations as $reservation)
        <article class="border rounded p-3 mb-2">
            <a href="{{ route('reservations.show', $reservation) }}">{{ $reservation->venue->name }}</a>
            <span class="badge text-bg-secondary">{{ str($reservation->status)->title() }}</span>
            <p class="mb-0">{{ $reservation->venue->location }} · {{ $reservation->starts_at->format('M j, Y H:i') }} – {{ $reservation->ends_at->format('M j, Y H:i') }}</p>
            @if ($reservation->review_feedback)<p class="mb-0 mt-1">{{ $reservation->review_feedback }}</p>@endif
        </article>
    @empty
        <p>No venue requests.</p>
    @endforelse
@endsection
