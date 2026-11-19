@extends('layouts.app')

@section('title', 'Club proposals · ClubPilot')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">{{ auth()->user()->isUniversityAdmin() ? 'Club proposal reviews' : 'My club proposals' }}</h1>
            <p class="text-secondary mb-0">
                {{ auth()->user()->isUniversityAdmin() ? 'Review proposals submitted by students.' : 'Track the proposals you have submitted.' }}
            </p>
        </div>

        @if (auth()->user()->isStudent())
            <a href="{{ route('club-proposals.create') }}" class="btn btn-primary">Submit a club proposal</a>
        @endif
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Proposed club</th>
                        @if (auth()->user()->isUniversityAdmin())
                            <th>Submitted by</th>
                        @endif
                        <th>Submitted</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($proposals as $proposal)
                        <tr>
                            <td class="fw-semibold">{{ $proposal->name }}</td>
                            @if (auth()->user()->isUniversityAdmin())
                                <td>{{ $proposal->student->name }}</td>
                            @endif
                            <td>{{ $proposal->created_at->format('d M Y') }}</td>
                            <td>
                                @php
                                    $statusClass = match ($proposal->status) {
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        default => 'warning text-dark',
                                    };
                                @endphp
                                <span class="badge text-bg-{{ $statusClass }}">{{ ucfirst($proposal->status) }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('club-proposals.show', $proposal) }}" class="btn btn-outline-primary btn-sm">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->isUniversityAdmin() ? 5 : 4 }}" class="text-center text-secondary py-5">
                                No club proposals found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $proposals->links() }}</div>
@endsection
