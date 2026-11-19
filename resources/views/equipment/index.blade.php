
@extends('layouts.app')

@section('title', 'Equipment Inventory · ClubPilot')

@section('content')

@php
    $user = auth()->user();

    $canManageEquipment = $user->isUniversityAdmin()
        || $user->clubRoles()
            ->where('role', 'officer')
            ->exists();
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

    <div>
        <h1 class="h2 mb-1">Equipment Inventory</h1>

        <p class="text-secondary mb-0">
            Browse and manage club equipment.
        </p>
    </div>

    @if($canManageEquipment)
        <a href="{{ route('equipment.create') }}"
           class="btn btn-primary">
            + Add Equipment
        </a>
    @endif

</div>


{{-- Inventory Filters --}}

@if($canManageEquipment)

    <div class="d-flex flex-wrap gap-2 mb-4">

        <a href="{{ route('equipment.index', ['status' => 'all']) }}"
           class="btn {{ $status === 'all' ? 'btn-primary' : 'btn-outline-primary' }}">
            All Equipment
        </a>

        <a href="{{ route('equipment.index', ['status' => 'active']) }}"
           class="btn {{ $status === 'active' ? 'btn-primary' : 'btn-outline-primary' }}">
            Active
        </a>

        <a href="{{ route('equipment.index', ['status' => 'inactive']) }}"
           class="btn {{ $status === 'inactive' ? 'btn-primary' : 'btn-outline-primary' }}">
            Inactive
        </a>

    </div>

@endif


{{-- Equipment Directory --}}

<div class="card border-0 shadow-sm">

    <div class="card-header bg-white py-3">

        <h2 class="h5 mb-0">
            Equipment Directory
        </h2>

    </div>

    <div class="table-responsive">

        <table class="table table-hover align-middle mb-0">

            <thead class="table-light">

                <tr>
                    <th>Equipment</th>
                    <th>Club</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Condition</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>

            </thead>

            <tbody>

                @forelse($equipments as $equipment)

                    <tr>

                        <td>
                            <div class="fw-semibold">
                                {{ $equipment->name }}
                            </div>

                            <small class="text-secondary">
                                ID: {{ $equipment->id }}
                            </small>
                        </td>

                        <td>
                            {{ $equipment->club->name ?? 'N/A' }}
                        </td>

                        <td>
                            {{ $equipment->category ?? 'Uncategorized' }}
                        </td>

                        <td>
                            <span class="fw-semibold">
                                {{ $equipment->quantity }}
                            </span>
                        </td>

                        <td>
                            {{ ucfirst($equipment->item_condition ?? 'Unknown') }}
                        </td>

                        <td>

                            @if($equipment->is_active)

                                <span class="badge text-bg-success">
                                    Active
                                </span>

                            @else

                                <span class="badge text-bg-secondary">
                                    Inactive
                                </span>

                            @endif

                        </td>

                        <td class="text-end">

                            <a href="{{ route('equipment.show', $equipment) }}"
                               class="btn btn-sm btn-outline-primary">
                                View
                            </a>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="7" class="text-center py-5">

                            <h5 class="text-secondary">
                                No equipment found.
                            </h5>

                            <p class="text-secondary mb-0">

                                @if($canManageEquipment)

                                    Add equipment to your club to get started.

                                @else

                                    No active equipment is currently available in your clubs.

                                @endif

                            </p>

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>


{{-- Pagination --}}

<div class="mt-4">

    {{ $equipments->links() }}

</div>

@endsection
