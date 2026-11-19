
@extends('layouts.app')

@section('title', 'Membership Management · ClubPilot')

@section('content')

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

    <div>
        <h1 class="h2 mb-1">Membership Management</h1>
        <p class="text-secondary mb-0">
            Manage club members, applications and invitations.
        </p>
    </div>

    @if(auth()->user()->isStudent())
        <a href="{{ route('memberships.create') }}" class="btn btn-primary">
            Apply for Membership
        </a>
    @endif

</div>

{{-- Membership Statistics --}}

<div class="row g-3 mb-4">

    @php
        $cards = [
            ['label' => 'Total Members', 'value' => $statistics['total'], 'color' => 'primary'],
            ['label' => 'Pending', 'value' => $statistics['pending'], 'color' => 'warning'],
            ['label' => 'Invited', 'value' => $statistics['invited'], 'color' => 'info'],
            ['label' => 'Active', 'value' => $statistics['active'], 'color' => 'success'],
            ['label' => 'Inactive', 'value' => $statistics['inactive'], 'color' => 'secondary'],
            ['label' => 'Graduated', 'value' => $statistics['graduated'], 'color' => 'dark'],
        ];
    @endphp

    @foreach($cards as $card)

        <div class="col-6 col-md-4 col-lg-2">

            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">

                    <p class="text-secondary small mb-2">
                        {{ $card['label'] }}
                    </p>

                    <h3 class="text-{{ $card['color'] }} mb-0">
                        {{ $card['value'] }}
                    </h3>

                </div>
            </div>

        </div>

    @endforeach

</div>

{{-- Invite Students --}}

@if($managedClubs->isNotEmpty())

    <div class="card border-0 shadow-sm mb-4">

        <div class="card-header bg-white p-3">
            <h2 class="h5 mb-0">Invite Students</h2>
        </div>

        <div class="card-body">

            @forelse($managedClubs as $club)

                <form method="POST"
                      action="{{ route('memberships.invite', $club) }}"
                      class="border rounded p-3 mb-3">

                    @csrf

                    <h3 class="h6 mb-3">
                        {{ $club->name }}
                    </h3>

                    @if($students->isNotEmpty())

                        <div class="row g-2">

                            <div class="col-md-8">

                                <select name="user_id"
                                        class="form-select"
                                        required>

                                    <option value="">
                                        Select a student
                                    </option>

                                    @foreach($students as $student)

                                        <option value="{{ $student->id }}">
                                            {{ $student->name }} ({{ $student->email }})
                                        </option>

                                    @endforeach

                                </select>

                            </div>

                            <div class="col-md-4">

                                <button type="submit"
                                        class="btn btn-primary w-100">
                                    Send Invitation
                                </button>

                            </div>

                        </div>

                    @else

                        <p class="text-secondary mb-0">
                            No student accounts are available.
                        </p>

                    @endif

                </form>

            @empty

                <p class="text-secondary mb-0">
                    No clubs available.
                </p>

            @endforelse

        </div>

    </div>

@endif

{{-- Membership Filters --}}

<div class="d-flex flex-wrap gap-2 mb-3">

    <a href="{{ route('memberships.index') }}"
       class="btn btn-sm {{ !$status ? 'btn-primary' : 'btn-outline-primary' }}">
        All
    </a>

    @foreach(['pending', 'invited', 'active', 'inactive', 'graduated', 'rejected'] as $filter)

        <a href="{{ route('memberships.index', ['status' => $filter]) }}"
           class="btn btn-sm {{ $status === $filter ? 'btn-primary' : 'btn-outline-primary' }}">
            {{ ucfirst($filter) }}
        </a>

    @endforeach

</div>

{{-- Membership Directory --}}

<div class="card border-0 shadow-sm">

    <div class="card-header bg-white p-3">

        <h2 class="h5 mb-0">
            Membership Directory
        </h2>

    </div>

    <div class="table-responsive">

        <table class="table table-hover align-middle mb-0">

            <thead class="table-light">

                <tr>
                    <th>Member</th>
                    <th>Club</th>
                    <th>Status</th>
                    <th>Joined Date</th>
                    <th class="text-end">Action</th>
                </tr>

            </thead>

            <tbody>

                @forelse($memberships as $membership)

                    <tr>

                        <td>
                            <div class="fw-semibold">
                                {{ $membership->user->name }}
                            </div>

                            <small class="text-secondary">
                                {{ $membership->user->email }}
                            </small>
                        </td>

                        <td>
                            {{ $membership->club->name }}
                        </td>

                        <td>

                            @php
                                $statusClass = match($membership->status) {
                                    'active' => 'success',
                                    'pending' => 'warning text-dark',
                                    'invited' => 'info',
                                    'inactive' => 'secondary',
                                    'graduated' => 'dark',
                                    'rejected' => 'danger',
                                    default => 'secondary',
                                };
                            @endphp

                            <span class="badge text-bg-{{ $statusClass }}">
                                {{ ucfirst($membership->status) }}
                            </span>

                        </td>

                        <td>
                            {{ $membership->joined_at?->format('d M Y') ?? '—' }}
                        </td>

                        <td class="text-end">

                            <a href="{{ route('memberships.show', $membership) }}"
                               class="btn btn-sm btn-outline-primary">
                                View
                            </a>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="5"
                            class="text-center text-secondary py-5">

                            No membership records found.

                            @if(auth()->user()->isStudent())
                                <div class="mt-2">
                                    You can apply to join an available club.
                                </div>
                            @endif

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

<div class="mt-3">
    {{ $memberships->links() }}
</div>

@endsection
