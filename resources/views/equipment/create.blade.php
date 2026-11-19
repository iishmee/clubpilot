@extends('layouts.app')

@section('title', 'Add Equipment · ClubPilot')

@section('content')

<div class="mb-4">
    <a href="{{ route('equipment.index') }}"
       class="text-decoration-none">
        &larr; Back to Equipment Inventory
    </a>
</div>

<div class="card border-0 shadow-sm">

    <div class="card-header bg-white p-4">
        <h1 class="h3 mb-1">Add New Equipment</h1>
        <p class="text-secondary mb-0">
            Register equipment for a club.
        </p>
    </div>

    <div class="card-body p-4">

        @if($clubs->isEmpty())

            <div class="alert alert-warning">
                No clubs are available for equipment registration.
                Please create or obtain access to a club first.
            </div>

        @else

            <form method="POST"
                  action="{{ route('equipment.store') }}">

                @csrf

                <div class="row g-3">

                    {{-- Club Selection --}}
                    <div class="col-md-6">

                        <label for="club_id" class="form-label">
                            Select Club *
                        </label>

                        <select name="club_id"
                                id="club_id"
                                class="form-select @error('club_id') is-invalid @enderror"
                                required>

                            <option value="">
                                Choose a club
                            </option>

                            @foreach($clubs as $club)

                                <option value="{{ $club->id }}"
                                    @selected(old('club_id') == $club->id)>
                                    {{ $club->name }}
                                </option>

                            @endforeach

                        </select>

                        @error('club_id')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Equipment Name --}}
                    <div class="col-md-6">

                        <label for="name" class="form-label">
                            Equipment Name *
                        </label>

                        <input type="text"
                               name="name"
                               id="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}"
                               maxlength="150"
                               placeholder="e.g. Projector"
                               required>

                        @error('name')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Category --}}
                    <div class="col-md-6">

                        <label for="category" class="form-label">
                            Category
                        </label>

                        <input type="text"
                               name="category"
                               id="category"
                               class="form-control @error('category') is-invalid @enderror"
                               value="{{ old('category') }}"
                               maxlength="100"
                               placeholder="e.g. Electronics">

                        @error('category')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Quantity --}}
                    <div class="col-md-6">

                        <label for="quantity" class="form-label">
                            Total Quantity *
                        </label>

                        <input type="number"
                               name="quantity"
                               id="quantity"
                               class="form-control @error('quantity') is-invalid @enderror"
                               value="{{ old('quantity', 1) }}"
                               min="1"
                               required>

                        @error('quantity')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Condition --}}
                    <div class="col-md-6">

                        <label for="item_condition" class="form-label">
                            Equipment Condition *
                        </label>

                        <input type="text"
                               name="item_condition"
                               id="item_condition"
                               class="form-control @error('item_condition') is-invalid @enderror"
                               value="{{ old('item_condition', 'Good') }}"
                               maxlength="50"
                               placeholder="e.g. Good, Fair, Needs Repair"
                               required>

                        @error('item_condition')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Description --}}
                    <div class="col-12">

                        <label for="description" class="form-label">
                            Description
                        </label>

                        <textarea name="description"
                                  id="description"
                                  rows="4"
                                  maxlength="2000"
                                  class="form-control @error('description') is-invalid @enderror"
                                  placeholder="Enter equipment details...">{{ old('description') }}</textarea>

                        @error('description')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                </div>


                {{-- Buttons --}}
                <div class="d-flex justify-content-end gap-2 mt-4">

                    <a href="{{ route('equipment.index') }}"
                       class="btn btn-outline-secondary">
                        Cancel
                    </a>

                    <button type="submit"
                            class="btn btn-primary">
                        Save Equipment
                    </button>

                </div>

            </form>

        @endif

    </div>

</div>

@endsection
