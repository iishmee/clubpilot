@extends('layouts.app')

@section('title', 'New volunteer opportunity · ClubPilot')

@section('content')
    <h1 class="h2">New volunteer opportunity</h1>
    <p class="text-muted">For event: {{ $event->title }}</p>

    <form method="POST" action="{{ route('volunteers.store', $event) }}">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="title">Title (e.g. "Registration desk helper")</label>
            <input class="form-control" id="title" name="title" value="{{ old('title') }}" required>
            @error('title')<div class="text-danger">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="form-label" for="description">Description</label>
            <textarea class="form-control" id="description" name="description" rows="4">{{ old('description') }}</textarea>
            @error('description')<div class="text-danger">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="form-label" for="slots_needed">Volunteers needed</label>
            <input class="form-control" type="number" min="1" id="slots_needed" name="slots_needed" value="{{ old('slots_needed', 1) }}" required>
            @error('slots_needed')<div class="text-danger">{{ $message }}</div>@enderror
        </div>
        <button class="btn btn-primary">Create opportunity</button>
        <a class="btn btn-link" href="{{ route('volunteers.index') }}">Cancel</a>
    </form>
@endsection
