@extends('layouts.app')

@section('title', $clubProposal->name . ' · ClubPilot')

@section('content')
    <div class="mb-4">
        <a href="{{ route('club-proposals.index') }}" class="text-decoration-none">← Back to proposals</a>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">{{ $clubProposal->name }}</h1>
            <p class="text-secondary mb-0">Submitted by {{ $clubProposal->student->name }} on {{ $clubProposal->created_at->format('d M Y, h:i A') }}</p>
        </div>
        @php
            $statusClass = match ($clubProposal->status) {
                'approved' => 'success',
                'rejected' => 'danger',
                default => 'warning text-dark',
            };
        @endphp
        <span class="badge text-bg-{{ $statusClass }} fs-6">{{ ucfirst($clubProposal->status) }}</span>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h2 class="h5">Club details</h2>
            <p class="mb-4" style="white-space: pre-line">{{ $clubProposal->details }}</p>

            <h2 class="h5">Objectives</h2>
            <p class="mb-4" style="white-space: pre-line">{{ $clubProposal->objectives }}</p>

            @if ($clubProposal->constitution_path)
                <h2 class="h5">Constitution</h2>
                <a href="{{ route('club-proposals.constitution', $clubProposal) }}">Download constitution</a>
            @endif

            @if ($clubProposal->club)
                <div class="alert alert-success mt-4 mb-0">
                    This proposal has been approved. <a href="{{ route('clubs.directory') }}" class="alert-link">View the club directory</a>.
                </div>
            @endif
        </div>
    </div>

    @if (auth()->user()->isUniversityAdmin() && $clubProposal->status === 'pending')
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Administrator review</h2>
                <div class="d-flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('club-proposals.approve', $clubProposal) }}">
                        @csrf
                        <button type="submit" class="btn btn-success">Approve proposal</button>
                    </form>
                </div>
                <form method="POST" action="{{ route('club-proposals.reject', $clubProposal) }}" class="mt-3">
                    @csrf
                    <label for="reason" class="form-label">Reason for rejection (optional)</label>
                    <textarea id="reason" name="reason" rows="3" maxlength="1000" class="form-control mb-2">{{ old('reason') }}</textarea>
                    <button type="submit" class="btn btn-outline-danger">Reject proposal</button>
                </form>
            </div>
        </div>
    @endif

    @if ($clubProposal->auditEntries->isNotEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Review history</h2>
                <div class="list-group list-group-flush">
                    @foreach ($clubProposal->auditEntries as $entry)
                        <div class="list-group-item px-0">
                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                <strong>{{ ucfirst($entry->from_state ?? 'submitted') }} → {{ ucfirst($entry->to_state) }}</strong>
                                <small class="text-secondary">{{ $entry->created_at->format('d M Y, h:i A') }}</small>
                            </div>
                            <div class="text-secondary small">Reviewed by {{ $entry->actor->name }}</div>
                            @if ($entry->reason)
                                <p class="mb-0 mt-1">{{ $entry->reason }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
@endsection
