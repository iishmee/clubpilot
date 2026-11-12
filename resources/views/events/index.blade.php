@extends('layouts.app')

@section('title', 'Event proposals · ClubPilot')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h2 mb-0">Event proposals</h1>
        @if (auth()->user()->isOrganizer())
            <a class="btn btn-primary" href="{{ route('events.create') }}">New proposal</a>
        @endif
    </div>

    @if ($events->isEmpty())
        <p>No proposals yet.</p>
    @else
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Event</th><th>Club</th><th>Organizer</th><th>Status</th><th>Proposed time</th></tr></thead>
                <tbody>
                    @foreach ($events as $event)
                        <tr>
                            <td><a href="{{ route('events.show', $event) }}">{{ $event->title ?: 'Untitled legacy event' }}</a></td>
                            <td>{{ $event->club_name ?: '—' }}</td>
                            <td>{{ $event->organizer?->name ?: 'Unassigned' }}</td>
                            <td><span class="badge text-bg-secondary">{{ str($event->proposal_status)->replace('_', ' ')->title() }}</span></td>
                            <td>{{ $event->starts_at?->format('M j, Y H:i') ?? 'Not scheduled' }} – {{ $event->ends_at?->format('M j, Y H:i') ?? 'Not scheduled' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $events->links() }}
    @endif
@endsection
