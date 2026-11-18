@extends('layouts.app')

@section('title', 'Volunteering · ClubPilot')

@section('content')
    <h1 class="h2 mb-3">Volunteer opportunities</h1>

    @if (auth()->user()->role === 'member')
        <p><a class="btn btn-outline-primary" href="{{ route('shifts.mine') }}">My shifts</a> <a class="btn btn-outline-primary" href="{{ route('tasks.mine') }}">My tasks</a></p>
    @endif

    @if (auth()->user()->isOrganizer())
        <section class="card card-body mb-4">
            <h2 class="h5">Recruit volunteers for an approved event</h2>
            @forelse ($events as $event)
                <div class="d-flex justify-content-between align-items-center py-1">
                    <span>{{ $event->title }}</span>
                    <a class="btn btn-sm btn-primary" href="{{ route('volunteers.create', $event) }}">Add opportunity</a>
                </div>
            @empty
                <p class="mb-0 text-muted">You have no approved events yet. An event must be approved before you can recruit volunteers.</p>
            @endforelse
        </section>
    @endif

    @if ($opportunities->isEmpty())
        <p>No volunteer opportunities yet.</p>
    @else
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Opportunity</th><th>Event</th><th>Slots filled</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach ($opportunities as $opportunity)
                        <tr>
                            <td><a href="{{ route('volunteers.show', $opportunity) }}">{{ $opportunity->title }}</a></td>
                            <td>{{ $opportunity->event?->title }}</td>
                            <td>{{ $opportunity->accepted_count }} / {{ $opportunity->slots_needed }}</td>
                            <td><span class="badge text-bg-secondary">{{ str($opportunity->status)->title() }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $opportunities->links() }}
    @endif
@endsection
