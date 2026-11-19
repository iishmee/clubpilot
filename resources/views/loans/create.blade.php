@extends('layouts.app')

@section('title', 'Request Equipment · ClubPilot')

@section('content')

<div class="mb-4">
    <a href="{{ route('loans.index') }}" class="text-decoration-none">
        &larr; Back to Equipment Lending
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">

        <div class="card border-0 shadow-sm">

            <div class="card-header bg-white p-4">
                <h2 class="h4 mb-1">Request Equipment</h2>
                <p class="text-secondary mb-0">
                    Complete the form to borrow equipment from your club.
                </p>
            </div>

            <div class="card-body p-4">

                @if($memberships->isEmpty())
                    <div class="alert alert-warning">
                        You do not have any active club memberships.
                        You must have an active membership before requesting equipment.
                    </div>

                    <a href="{{ route('loans.index') }}"
                       class="btn btn-outline-primary">
                        Back to Equipment Lending
                    </a>

                @elseif($equipments->isEmpty())

                    <div class="alert alert-warning">
                        No active equipment is currently available for your clubs.
                    </div>

                    <a href="{{ route('loans.index') }}"
                       class="btn btn-outline-primary">
                        Back to Equipment Lending
                    </a>

                @else

                    <form method="POST" action="{{ route('loans.store') }}">
                        @csrf

                        {{-- Club Membership --}}

                        <div class="mb-3">
                            <label for="membership_id" class="form-label fw-semibold">
                                Select Club Membership
                            </label>

                            <select name="membership_id"
                                    id="membership_id"
                                    class="form-select @error('membership_id') is-invalid @enderror"
                                    required>

                                <option value="">Choose your club</option>

                                @foreach($memberships as $membership)
                                    <option value="{{ $membership->id }}"
                                            data-club-id="{{ $membership->club_id }}"
                                            {{ old('membership_id') == $membership->id ? 'selected' : '' }}>

                                        {{ $membership->club->name ?? 'Unknown Club' }}

                                    </option>
                                @endforeach

                            </select>

                            @error('membership_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Equipment --}}

                        <div class="mb-3">
                            <label for="equipment_id" class="form-label fw-semibold">
                                Select Equipment
                            </label>

                            <select name="equipment_id"
                                    id="equipment_id"
                                    class="form-select @error('equipment_id') is-invalid @enderror"
                                    required>

                                <option value="">Choose equipment</option>

                                @foreach($equipments as $equipment)
                                    <option value="{{ $equipment->id }}"
                                            data-club-id="{{ $equipment->club_id }}"
                                            {{ old('equipment_id') == $equipment->id ? 'selected' : '' }}>

                                        {{ $equipment->name }}
                                        ({{ $equipment->club->name ?? 'Unknown Club' }})

                                    </option>
                                @endforeach

                            </select>

                            @error('equipment_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Quantity --}}

                        <div class="mb-3">
                            <label for="requested_quantity" class="form-label fw-semibold">
                                Requested Quantity
                            </label>

                            <input type="number"
                                   name="requested_quantity"
                                   id="requested_quantity"
                                   class="form-control @error('requested_quantity') is-invalid @enderror"
                                   min="1"
                                   value="{{ old('requested_quantity', 1) }}"
                                   required>

                            @error('requested_quantity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Borrowing Start Date --}}

                        <div class="mb-3">
                            <label for="starts_at" class="form-label fw-semibold">
                                Borrowing Start Date and Time
                            </label>

                            <input type="datetime-local"
                                   name="starts_at"
                                   id="starts_at"
                                   class="form-control @error('starts_at') is-invalid @enderror"
                                   min="{{ now()->format('Y-m-d\TH:i') }}"
                                   value="{{ old('starts_at') }}"
                                   required>

                            @error('starts_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Borrowing End Date --}}

                        <div class="mb-3">
                            <label for="ends_at" class="form-label fw-semibold">
                                Expected Return Date and Time
                            </label>

                            <input type="datetime-local"
                                   name="ends_at"
                                   id="ends_at"
                                   class="form-control @error('ends_at') is-invalid @enderror"
                                   value="{{ old('ends_at') }}"
                                   required>

                            @error('ends_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Request Note --}}

                        <div class="mb-4">
                            <label for="request_note" class="form-label fw-semibold">
                                Purpose / Additional Information
                            </label>

                            <textarea name="request_note"
                                      id="request_note"
                                      class="form-control @error('request_note') is-invalid @enderror"
                                      rows="4"
                                      maxlength="2000"
                                      placeholder="Explain why you need this equipment...">{{ old('request_note') }}</textarea>

                            @error('request_note')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Submit --}}

                        <div class="d-flex justify-content-end gap-2">

                            <a href="{{ route('loans.index') }}"
                               class="btn btn-outline-secondary">
                                Cancel
                            </a>

                            <button type="submit" class="btn btn-primary">
                                Submit Request
                            </button>

                        </div>

                    </form>

                @endif

            </div>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const membershipSelect = document.getElementById('membership_id');
    const equipmentSelect = document.getElementById('equipment_id');

    if (!membershipSelect || !equipmentSelect) {
        return;
    }

    function filterEquipment() {

        const selectedMembership =
            membershipSelect.options[membershipSelect.selectedIndex];

        const clubId = selectedMembership
            ? selectedMembership.dataset.clubId
            : '';

        Array.from(equipmentSelect.options).forEach(function (option) {

            if (!option.value) {
                option.hidden = false;
                return;
            }

            option.hidden = option.dataset.clubId !== clubId;

            if (option.hidden && option.selected) {
                equipmentSelect.value = '';
            }

        });
    }

    membershipSelect.addEventListener('change', filterEquipment);

    filterEquipment();

});
</script>
@endpush
