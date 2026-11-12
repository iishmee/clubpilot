@extends('layouts.app')

@section('title', 'Request a venue · ClubPilot')

@section('content')
    <h1 class="h2">Request a venue</h1>
    <p>For <a href="{{ route('events.show', $event) }}">{{ $event->title }}</a></p>
    @if ($venues->isEmpty())
        <div class="alert alert-info">No venues are currently available. Contact an administrator.</div>
    @else
        <form method="POST" action="{{ route('events.reservations.store', $event) }}">
            @csrf
            <div class="mb-3">
                <label class="form-label" for="venue_id">Venue</label>
                <select class="form-select" name="venue_id" id="venue_id" required>
                    @foreach ($venues as $venue)
                        <option value="{{ $venue->id }}" @selected(old('venue_id') == $venue->id)>{{ $venue->name }} · {{ $venue->location }} · capacity {{ $venue->capacity }}</option>
                    @endforeach
                </select>
                @error('venue_id')<div class="text-danger">{{ $message }}</div>@enderror
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="starts_at">Requested start</label>
                    <input class="form-control" id="starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at', $event->starts_at->format('Y-m-d\TH:i')) }}" required>
                    @error('starts_at')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="ends_at">Requested end</label>
                    <input class="form-control" id="ends_at" name="ends_at" type="datetime-local" value="{{ old('ends_at', $event->ends_at->format('Y-m-d\TH:i')) }}" required>
                    @error('ends_at')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
            </div>
            <p class="text-muted">Pending and approved requests reserve their time slot. Adjacent time slots are allowed.</p>
            <button class="btn btn-primary">Submit venue request</button>
            <a class="btn btn-outline-secondary" href="{{ route('events.show', $event) }}">Cancel</a>
        </form>
    @endif
@endsection
