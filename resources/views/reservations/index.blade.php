@extends('layouts.app')

@section('title', 'Reservations · ClubPilot')

@section('content')
    <h1 class="h2 mb-3">{{ auth()->user()->isAdmin() ? 'Venue requests' : 'My venue requests' }}</h1>
    @if ($reservations->isEmpty())
        <p>No venue requests.</p>
    @else
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Event</th><th>Venue</th><th>Requested time</th><th>Status</th><th>Requester</th></tr></thead>
                <tbody>
                    @foreach ($reservations as $reservation)
                        <tr>
                            <td><a href="{{ route('events.show', $reservation->event) }}">{{ $reservation->event->title }}</a></td>
                            <td>{{ $reservation->venue->name }}</td>
                            <td>{{ $reservation->starts_at->format('M j, Y H:i') }} – {{ $reservation->ends_at->format('M j, Y H:i') }}</td>
                            <td><a href="{{ route('reservations.show', $reservation) }}">{{ str($reservation->status)->title() }}</a></td>
                            <td>{{ $reservation->requester->name }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $reservations->links() }}
    @endif
@endsection
