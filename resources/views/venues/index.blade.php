@extends('layouts.app')

@section('title', 'Venues · ClubPilot')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h2 mb-0">Venues</h1>
        @if (auth()->user()->isAdmin())
            <a class="btn btn-primary" href="{{ route('venues.create') }}">Add venue</a>
        @endif
    </div>
    @if ($venues->isEmpty())
        <p>No venues have been added yet.</p>
    @else
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Name</th><th>Location</th><th>Capacity</th><th>Availability</th></tr></thead>
                <tbody>
                    @foreach ($venues as $venue)
                        <tr>
                            <td>{{ $venue->name }}</td>
                            <td>{{ $venue->location }}</td>
                            <td>{{ $venue->capacity }}</td>
                            <td><span class="badge {{ $venue->availability_status === 'available' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ str($venue->availability_status)->title() }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $venues->links() }}
    @endif
@endsection
