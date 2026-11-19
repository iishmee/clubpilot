
@extends('layouts.app')

@section('title', 'Membership Details · ClubPilot')

@section('content')

<div class="mb-4">
    <a href="{{ route('memberships.index') }}" class="text-decoration-none">
        &larr; Back to Membership Management
    </a>
</div>

{{-- Membership Information --}}

<div class="card border-0 shadow-sm mb-4">

    <div class="card-body p-4">

        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">

            <div>
                <p class="text-secondary small mb-1">
                    MEMBERSHIP DETAILS
                </p>

                <h1 class="h3 mb-2">
                    {{ $membership->user->name }}
                </h1>

                <p class="text-secondary mb-0">
                    {{ $membership->club->name }}
                </p>
            </div>

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

            <span class="badge text-bg-{{ $statusClass }} fs-6">
                {{ ucfirst($membership->status) }}
            </span>

        </div>

        <hr>

        <div class="row g-4">

            <div class="col-md-6">
                <p class="text-secondary small mb-1">Member Name</p>
                <p class="fw-semibold">{{ $membership->user->name }}</p>
            </div>

            <div class="col-md-6">
                <p class="text-secondary small mb-1">Email Address</p>
                <p class="fw-semibold">{{ $membership->user->email }}</p>
            </div>

            <div class="col-md-6">
                <p class="text-secondary small mb-1">Club</p>
                <p class="fw-semibold">{{ $membership->club->name }}</p>
            </div>

            <div class="col-md-6">
                <p class="text-secondary small mb-1">Application Date</p>
                <p class="fw-semibold">
                    {{ $membership->applied_at?->format('d M Y') ?? '—' }}
                </p>
            </div>

            <div class="col-md-6">
                <p class="text-secondary small mb-1">Invitation Date</p>
                <p class="fw-semibold">
                    {{ $membership->invited_at?->format('d M Y') ?? '—' }}
                </p>
            </div>

            <div class="col-md-6">
                <p class="text-secondary small mb-1">Joining Date</p>
                <p class="fw-semibold">
                    {{ $membership->joined_at?->format('d M Y') ?? '—' }}
                </p>
            </div>

            <div class="col-md-6">
                <p class="text-secondary small mb-1">Membership End Date</p>
                <p class="fw-semibold">
                    {{ $membership->ended_at?->format('d M Y') ?? '—' }}
                </p>
            </div>

            <div class="col-md-6">
                <p class="text-secondary small mb-1">Invited By</p>
                <p class="fw-semibold">
                    {{ $membership->invitedBy?->name ?? '—' }}
                </p>
            </div>

        </div>

        @if($membership->application_message)

            <hr>

            <h2 class="h6">Application Message</h2>

            <p class="text-secondary mb-0">
                {{ $membership->application_message }}
            </p>

        @endif

        @if($membership->status_reason)

            <hr>

            <h2 class="h6">Status Reason</h2>

            <p class="text-secondary mb-0">
                {{ $membership->status_reason }}
            </p>

        @endif

    </div>

</div>

{{-- Membership Actions --}}

<div class="card border-0 shadow-sm">

    <div class="card-header bg-white p-3">
        <h2 class="h5 mb-0">Membership Actions</h2>
    </div>

    <div class="card-body p-4">

        <div class="d-flex flex-wrap gap-2">

            @can('acceptInvitation', $membership)

                <form method="POST"
                      action="{{ route('memberships.accept', $membership) }}">
                    @csrf

                    <button type="submit" class="btn btn-success">
                        Accept Invitation
                    </button>
                </form>

            @endcan

            @can('approve', $membership)

                <form method="POST"
                      action="{{ route('memberships.approve', $membership) }}">
                    @csrf

                    <button type="submit" class="btn btn-success">
                        Approve Application
                    </button>
                </form>

            @endcan

            @can('deactivate', $membership)

                <form method="POST"
                      action="{{ route('memberships.deactivate', $membership) }}">
                    @csrf

                    <button type="submit" class="btn btn-outline-secondary">
                        Deactivate Member
                    </button>
                </form>

            @endcan

            @can('graduate', $membership)

                <form method="POST"
                      action="{{ route('memberships.graduate', $membership) }}">
                    @csrf

                    <button type="submit" class="btn btn-outline-primary">
                        Mark as Graduated
                    </button>
                </form>

            @endcan

            @can('reactivate', $membership)

                <form method="POST"
                      action="{{ route('memberships.reactivate', $membership) }}">
                    @csrf

                    <button type="submit" class="btn btn-success">
                        Reactivate Member
                    </button>
                </form>

            @endcan

        </div>

        @can('reject', $membership)

            <hr>

            <form method="POST"
                  action="{{ route('memberships.reject', $membership) }}">

                @csrf

                <div class="mb-3">

                    <label for="reason" class="form-label fw-semibold">
                        Reason for Rejection
                    </label>

                    <textarea
                        name="reason"
                        id="reason"
                        rows="3"
                        class="form-control @error('reason') is-invalid @enderror"
                        required
                    >{{ old('reason') }}</textarea>

                    @error('reason')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

                <button type="submit" class="btn btn-outline-danger">
                    Reject Application
                </button>

            </form>

        @endcan

    </div>

</div>

@endsection
