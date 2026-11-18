@extends('layouts.app')

@section('title', 'Venue request review · ClubPilot')

@section('content')
    <h1 class="h2 mb-3">Venue requests awaiting review</h1>
    @forelse ($reservations as $reservation)
        <article class="card card-body mb-3">
            <h2 class="h5"><a href="{{ route('reservations.show', $reservation) }}">{{ $reservation->event->title ?: 'Untitled event' }}</a></h2>
            <p class="mb-1"><strong>Organizer:</strong> {{ $reservation->requester->name }}</p>
            <p class="mb-1"><strong>Requested venue:</strong> {{ $reservation->venue->name }} · {{ $reservation->venue->location }}</p>
            <p class="mb-1"><strong>Requested time:</strong> {{ $reservation->starts_at->format('M j, Y H:i') }} – {{ $reservation->ends_at->format('M j, Y H:i') }}</p>
            <p><strong>Status:</strong> Pending Administrator Approval</p>
            <div class="d-flex flex-wrap gap-2">
                <form method="POST" action="{{ route('reservations.review', $reservation) }}">
                    @csrf
                    <input type="hidden" name="decision" value="approved">
                    <button class="btn btn-success">Approve and assign venue</button>
                </form>
                <form method="POST" action="{{ route('reservations.review', $reservation) }}" class="d-flex flex-wrap gap-2">
                    @csrf
                    <input type="hidden" name="decision" value="rejected">
                    <label class="visually-hidden" for="feedback-{{ $reservation->id }}">Rejection reason for {{ $reservation->event->title }}</label>
                    <input class="form-control" id="feedback-{{ $reservation->id }}" name="feedback" placeholder="Reason required to reject" required>
                    <button class="btn btn-outline-danger">Reject</button>
                </form>
            </div>
        </article>
    @empty
        <p>No venue requests are awaiting review.</p>
    @endforelse
    {{ $reservations->links() }}
@endsection
