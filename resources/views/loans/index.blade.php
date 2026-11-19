@extends('layouts.app')

@section('title', 'Equipment Lending · ClubPilot')

@section('content')

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2 mb-1">Equipment Lending</h1>
        <p class="text-secondary mb-0">
            Manage equipment requests, lending and returns.
        </p>
    </div>

    @if(auth()->user()->isStudent())
        <a href="{{ route('loans.create') }}"
           class="btn btn-primary">
            Request Equipment
        </a>
    @endif
</div>

{{-- Status Filters --}}

<div class="d-flex flex-wrap gap-2 mb-4">

    <a href="{{ route('loans.index') }}"
       class="btn {{ $status === 'all' ? 'btn-primary' : 'btn-outline-primary' }}">
        All
    </a>

    @foreach($statuses as $item)
        <a href="{{ route('loans.index', ['status' => $item]) }}"
           class="btn {{ $status === $item ? 'btn-primary' : 'btn-outline-primary' }}">
            {{ ucfirst($item) }}
        </a>
    @endforeach

</div>

{{-- Loan Directory --}}

<div class="card border-0 shadow-sm">

    <div class="card-header bg-white py-3">
        <h5 class="mb-0">Equipment Loan Records</h5>
    </div>

    <div class="table-responsive">

        <table class="table table-hover align-middle mb-0">

            <thead class="table-light">
                <tr>
                    <th>Equipment</th>
                    <th>Club</th>
                    <th>Member</th>
                    <th>Quantity</th>
                    <th>Borrowing Period</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>

            <tbody>

                @forelse($loans as $loan)

                    <tr>

                        <td class="fw-semibold">
                            {{ $loan->equipment?->name ?? 'Unavailable' }}
                        </td>

                        <td>
                            {{ $loan->equipment?->club?->name ?? 'N/A' }}
                        </td>

                        <td>
                            {{ $loan->membership?->user?->name ?? 'N/A' }}
                        </td>

                        <td>
                            {{ $loan->requested_quantity }}
                        </td>

                        <td>
                            @if($loan->starts_at && $loan->ends_at)
                                {{ \Illuminate\Support\Carbon::parse($loan->starts_at)->format('d M Y') }}
                                <br>
                                <small class="text-secondary">
                                    to
                                    {{ \Illuminate\Support\Carbon::parse($loan->ends_at)->format('d M Y') }}
                                </small>
                            @else
                                —
                            @endif
                        </td>

                        <td>

                            @php
                                $badge = match($loan->status) {
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

                            <span class="badge text-bg-{{ $badge }}">
                                {{ ucfirst($loan->status) }}
                            </span>

                        </td>

                        <td class="text-end">

                            <a href="{{ route('loans.show', $loan) }}"
                               class="btn btn-sm btn-outline-primary">
                                View Details
                            </a>

                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="7"
                            class="text-center text-secondary py-5">

                            <h5>No loan records found.</h5>

                            <p class="mb-0">
                                Equipment loan requests will appear here.
                            </p>

                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

{{-- Pagination --}}

<div class="mt-3">
    {{ $loans->links() }}
</div>

@endsection
