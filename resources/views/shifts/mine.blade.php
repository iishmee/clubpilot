@extends('layouts.app')

@section('title', 'My shifts · ClubPilot')

@section('content')
    <h1 class="h2 mb-3">My shifts</h1>

    @if ($assignments->isEmpty())
        <p>You have no assigned shifts yet.</p>
    @else
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Event</th><th>Role</th><th>Starts</th><th>Ends</th></tr></thead>
                <tbody>
                    @foreach ($assignments as $assignment)
                        <tr>
                            <td>{{ $assignment->shift->opportunity?->event?->title }}</td>
                            <td>{{ $assignment->shift->opportunity?->title }}</td>
                            <td>{{ $assignment->shift->starts_at->format('M j, Y H:i') }}</td>
                            <td>{{ $assignment->shift->ends_at->format('M j, Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <p class="mt-4"><a href="{{ route('volunteers.index') }}">← Back to volunteering</a></p>
@endsection
