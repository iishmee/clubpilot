
@extends('layouts.app')

@section('title', $equipment->name . ' · Equipment Details')

@section('content')

<div class="mb-4">

    <a href="{{ route('equipment.index') }}"
       class="text-decoration-none">
        &larr; Back to Equipment Inventory
    </a>

</div>

<div class="card border-0 shadow-sm">

    {{-- Header --}}

    <div class="card-header bg-white p-4">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">

            <div>

                <p class="text-secondary mb-1">
                    EQUIPMENT DETAILS
                </p>

                <h1 class="h3 mb-1">
                    {{ $equipment->name }}
                </h1>

                <p class="text-secondary mb-0">
                    {{ $equipment->category ?? 'Uncategorized' }}
                </p>

            </div>

            <div>

                @if($equipment->is_active)

                    <span class="badge text-bg-success fs-6">
                        Active
                    </span>

                @else

                    <span class="badge text-bg-secondary fs-6">
                        Inactive
                    </span>

                @endif

            </div>

        </div>

    </div>


    {{-- Equipment Information --}}

    <div class="card-body p-4">

        <div class="row g-4">

            <div class="col-md-6">

                <p class="text-secondary mb-1">
                    Equipment Name
                </p>

                <h5>
                    {{ $equipment->name }}
                </h5>

            </div>


            <div class="col-md-6">

                <p class="text-secondary mb-1">
                    Club
                </p>

                <h5>
                    {{ $equipment->club->name ?? 'N/A' }}
                </h5>

            </div>


            <div class="col-md-6">

                <p class="text-secondary mb-1">
                    Category
                </p>

                <h5>
                    {{ $equipment->category ?? 'N/A' }}
                </h5>

            </div>


            <div class="col-md-6">

                <p class="text-secondary mb-1">
                    Total Quantity
                </p>

                <h5>
                    {{ $equipment->quantity }}
                </h5>

            </div>


            <div class="col-md-6">

                <p class="text-secondary mb-1">
                    Equipment Condition
                </p>

                <h5>
                    {{ $equipment->item_condition ?? 'Unknown' }}
                </h5>

            </div>


            <div class="col-md-6">

                <p class="text-secondary mb-1">
                    Registration Date
                </p>

                <h5>
                    {{ $equipment->created_at
                        ? $equipment->created_at->format('d M Y')
                        : 'N/A' }}
                </h5>

            </div>

        </div>


        <hr class="my-4">


        {{-- Description --}}

        <div>

            <h5 class="mb-2">
                Equipment Description
            </h5>

            <p class="text-secondary mb-0">
                {{ $equipment->description ?: 'No description provided.' }}
            </p>

        </div>

    </div>


    {{-- Footer --}}

    <div class="card-footer bg-white p-3">

        <div class="d-flex justify-content-end">

            <a href="{{ route('equipment.index') }}"
               class="btn btn-primary">

                Back to Equipment Inventory

            </a>

        </div>

    </div>

</div>

@endsection
