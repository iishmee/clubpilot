@extends('layouts.app')

@section('title', 'Add venue · ClubPilot')

@section('content')
    <h1 class="h2 mb-3">Add venue</h1>
    <form method="POST" action="{{ route('venues.store') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="name">Venue name</label>
            <input class="form-control" id="name" name="name" value="{{ old('name') }}" required maxlength="255">
            @error('name')<div class="text-danger">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="form-label" for="location">Location</label>
            <input class="form-control" id="location" name="location" value="{{ old('location') }}" required maxlength="255">
            @error('location')<div class="text-danger">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="form-label" for="capacity">Capacity</label>
            <input class="form-control" id="capacity" name="capacity" type="number" min="1" value="{{ old('capacity') }}" required>
            @error('capacity')<div class="text-danger">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="form-label" for="availability_status">Availability</label>
            <select class="form-select" name="availability_status" id="availability_status" required>
                <option value="available" @selected(old('availability_status', 'available') === 'available')>Available</option>
                <option value="unavailable" @selected(old('availability_status') === 'unavailable')>Unavailable</option>
            </select>
        </div>
        <button class="btn btn-primary">Save venue</button>
        <a class="btn btn-outline-secondary" href="{{ route('venues.index') }}">Cancel</a>
    </form>
@endsection
