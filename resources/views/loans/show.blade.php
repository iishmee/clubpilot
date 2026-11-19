@extends('layouts.app')

@section('title', 'Loan Details · ClubPilot')

@section('content')

@php
    $displayStatus = $loan->isOverdue()
        ? 'overdue'
        : $loan->status;

    $badgeClass = match($displayStatus) {
        'pending' => 'warning',
        'approved' => 'info',
        'issued' => 'primary',
        'returned' => 'success',
        'rejected' => 'danger',
        'overdue' => 'danger',
        'cancelled' => 'secondary',
        default => 'secondary',
    };
@endphp

{{-- Header --}}

<div class="mb-4">

    <a href="{{ route('loans.index') }}"
       class="text-decoration-none">
        &larr; Back to Equipment Lending
    </a>

    <div class="d-flex flex-wrap justify-content-between align-items-center mt-3">

        <div>
            <h1 class="h2 mb-1">Loan Details</h1>
            <p class="text-secondary mb-0">
                Loan Reference #{{ $loan->id }}
            </p>
        </div>

        <span class="badge text-bg-{{ $badgeClass }} fs-6">
            {{ ucfirst($displayStatus) }}
        </span>

    </div>

</div>

<div class="row g-4">

    {{-- Equipment Information --}}

    <div class="col-lg-6">

        <div class="card border-0 shadow-sm h-100">

            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Equipment Information</h5>
            </div>

            <div class="card-body">

                <h4>{{ $loan->equipment?->name ?? 'Equipment unavailable' }}</h4>

                <p class="text-secondary">
                    {{ $loan->equipment?->description ?? 'No description available.' }}
                </p>

                <hr>

                <div class="mb-3">
                    <strong>Club:</strong>
                    {{ $loan->equipment?->club?->name ?? 'N/A' }}
                </div>

                <div class="mb-3">
                    <strong>Category:</strong>
                    {{ $loan->equipment?->category ?? 'N/A' }}
                </div>

                <div class="mb-3">
                    <strong>Equipment Condition:</strong>
                    {{ ucfirst($loan->equipment?->item_condition ?? 'N/A') }}
                </div>

                <div class="mb-3">
                    <strong>Total Inventory:</strong>
                    {{ $loan->equipment?->quantity ?? 'N/A' }}
                </div>

                <div>
                    <strong>Requested Quantity:</strong>
                    {{ $loan->requested_quantity }}
                </div>

            </div>

        </div>

    </div>

    {{-- Student Information --}}

    <div class="col-lg-6">

        <div class="card border-0 shadow-sm h-100">

            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Borrower Information</h5>
            </div>

            <div class="card-body">

                <h4>
                    {{ $loan->membership?->user?->name ?? 'Unknown Student' }}
                </h4>

                <p class="text-secondary">
                    {{ $loan->membership?->user?->email ?? 'N/A' }}
                </p>

                <hr>

                <div class="mb-3">
                    <strong>Club:</strong>
                    {{ $loan->membership?->club?->name ?? 'N/A' }}
                </div>

                <div class="mb-3">
                    <strong>Student ID:</strong>
                    {{ $loan->membership?->student_id ?? 'N/A' }}
                </div>

                <div class="mb-3">
                    <strong>Department:</strong>
                    {{ $loan->membership?->department ?? 'N/A' }}
                </div>

                <div class="mb-3">
                    <strong>Semester:</strong>
                    {{ $loan->membership?->semester ?? 'N/A' }}
                </div>

                <div>
                    <strong>Membership Status:</strong>
                    {{ ucfirst($loan->membership?->status ?? 'N/A') }}
                </div>

            </div>

        </div>

    </div>

    {{-- Borrowing Information --}}

    <div class="col-12">

        <div class="card border-0 shadow-sm">

            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Borrowing Information</h5>
            </div>

            <div class="card-body">

                <div class="row g-3">

                    <div class="col-md-6">
                        <strong>Borrowing Start:</strong>
                        <p class="text-secondary mb-0">
                            {{ $loan->starts_at?->format('d M Y, h:i A') ?? 'N/A' }}
                        </p>
                    </div>

                    <div class="col-md-6">
                        <strong>Expected Return:</strong>
                        <p class="text-secondary mb-0">
                            {{ $loan->ends_at?->format('d M Y, h:i A') ?? 'N/A' }}
                        </p>
                    </div>

                    <div class="col-md-6">
                        <strong>Request Submitted:</strong>
                        <p class="text-secondary mb-0">
                            {{ $loan->created_at?->format('d M Y, h:i A') }}
                        </p>
                    </div>

                    <div class="col-md-6">
                        <strong>Approved At:</strong>
                        <p class="text-secondary mb-0">
                            {{ $loan->approved_at?->format('d M Y, h:i A') ?? 'Not approved' }}
                        </p>
                    </div>

                    <div class="col-md-6">
                        <strong>Issued At:</strong>
                        <p class="text-secondary mb-0">
                            {{ $loan->issued_at?->format('d M Y, h:i A') ?? 'Not issued' }}
                        </p>
                    </div>

                    <div class="col-md-6">
                        <strong>Returned At:</strong>
                        <p class="text-secondary mb-0">
                            {{ $loan->returned_at?->format('d M Y, h:i A') ?? 'Not returned' }}
                        </p>
                    </div>

                </div>

                <hr>

                <div class="mb-3">
                    <strong>Request Purpose / Note:</strong>
                    <p class="text-secondary mb-0">
                        {{ $loan->request_note ?: 'No additional information provided.' }}
                    </p>
                </div>

                <div>
                    <strong>Decision Note:</strong>
                    <p class="text-secondary mb-0">
                        {{ $loan->decision_note ?: 'No decision note available.' }}
                    </p>
                </div>

            </div>

        </div>

    </div>

    {{-- Management Actions --}}

    <div class="col-12">

        <div class="card border-0 shadow-sm">

            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Loan Management Actions</h5>
            </div>

            <div class="card-body">

                @if($loan->status === 'pending' || $loan->status === 'approved')

                    <div class="d-flex flex-wrap gap-2">

                        {{-- Approve --}}

                        @can('approve', $loan)

                            @if($loan->status === 'pending')

                                <form method="POST"
                                      action="{{ route('loans.approve', $loan) }}">

                                    @csrf

                                    <input type="hidden"
                                           name="decision_note"
                                           value="Approved">

                                    <button type="submit"
                                            class="btn btn-success">
                                        Approve Request
                                    </button>

                                </form>

                            @endif

                        @endcan

                        {{-- Reject --}}

                        @can('reject', $loan)

                            @if($loan->status === 'pending')

                                <form method="POST"
                                      action="{{ route('loans.reject', $loan) }}">

                                    @csrf

                                    <input type="hidden"
                                           name="decision_note"
                                           value="Request rejected by club management">

                                    <button type="submit"
                                            class="btn btn-danger"
                                            onclick="return confirm('Are you sure you want to reject this request?')">
                                        Reject Request
                                    </button>

                                </form>

                            @endif

                        @endcan

                        {{-- Issue Equipment --}}

                        @can('issue', $loan)

                            @if($loan->status === 'approved')

                                <form method="POST"
                                      action="{{ route('loans.issue', $loan) }}">

                                    @csrf

                                    <button type="submit"
                                            class="btn btn-primary"
                                            onclick="return confirm('Confirm that the equipment has been issued?')">
                                        Issue Equipment
                                    </button>

                                </form>

                            @endif

                        @endcan

                        {{-- Cancel Loan --}}

                        @can('cancel', $loan)

                            <form method="POST"
                                  action="{{ route('loans.cancel', $loan) }}">

                                @csrf

                                <input type="hidden"
                                       name="decision_note"
                                       value="Cancelled by user">

                                <button type="submit"
                                        class="btn btn-outline-danger"
                                        onclick="return confirm('Are you sure you want to cancel this loan?')">
                                    Cancel Request
                                </button>

                            </form>

                        @endcan

                    </div>

                @endif

                {{-- Return Equipment --}}

                @can('returnEquipment', $loan)

                    @if($loan->status === 'issued')

                        <form method="POST"
                              action="{{ route('loans.return', $loan) }}">

                            @csrf

                            <button type="submit"
                                    class="btn btn-success"
                                    onclick="return confirm('Confirm that the equipment has been returned?')">
                                Record Equipment Return
                            </button>

                        </form>

                    @endif

                @endcan

                {{-- No available action --}}

                @if(
                    !auth()->user()->can('approve', $loan)
                    && !auth()->user()->can('reject', $loan)
                    && !auth()->user()->can('issue', $loan)
                    && !auth()->user()->can('returnEquipment', $loan)
                    && !auth()->user()->can('cancel', $loan)
                )

                    <p class="text-secondary mb-0">
                        No management actions are currently available for this loan.
                    </p>

                @endif

            </div>

        </div>

    </div>

</div>

@endsection
