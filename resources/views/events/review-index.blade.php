@extends('layouts.app')

@section('title', 'Proposal review · ClubPilot')

@section('content')
    <h1 class="h2 mb-3">Proposals awaiting review</h1>
    @forelse ($events as $event)
        <article class="card card-body mb-3">
            <h2 class="h5"><a href="{{ route('events.show', $event) }}">{{ $event->title ?: 'Untitled legacy event' }}</a> <small class="text-muted">· {{ $event->club_name ?: 'Unassigned' }}</small></h2>
            <p class="mb-1">Submitted by {{ $event->organizer?->name ?? 'Unassigned' }} · {{ $event->submitted_at?->format('M j, Y H:i') }}</p>
            <p class="mb-0">{{ $event->description }}</p>
        </article>
    @empty
        <p>No proposals are awaiting review.</p>
    @endforelse
    {{ $events->links() }}
@endsection
