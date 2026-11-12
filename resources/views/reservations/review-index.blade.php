@extends('layouts.app')

@section('title', 'Venue request review · ClubPilot')

@section('content')
    <h1 class="h2 mb-3">Venue requests awaiting review</h1>
    @forelse ($reservations as $reservation)
        <article class="card card-body mb-3">
            <h2 class="h5"><a href="{{ route('reservations.show', $reservation) }}">{{ $reservation->venue->name }}</a> · {{ $reservation->event->title }}</h2>
            <p>{{ $reservation->venue->location }} · {{ $reservation->starts_at->format('M j, Y H:i') }} – {{ $reservation->ends_at->format('M j, Y H:i') }}</p>
            <p class="mb-0">Requested by {{ $reservation->requester->name }}</p>
        </article>
    @empty
        <p>No venue requests are awaiting review.</p>
    @endforelse
    {{ $reservations->links() }}
@endsection
