
@extends('layouts.app')

@section('title', 'Create Budget')

@section('content')

<div class="mb-4">
    <h2>Create Event Budget</h2>
    <p class="text-muted">Create a budget for an approved event.</p>
</div>

<div class="card shadow-sm">
    <div class="card-body">

        <form action="{{ route('budgets.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="event_id" class="form-label">
                    Select Event
                </label>

                <select name="event_id"
                        id="event_id"
                        class="form-select @error('event_id') is-invalid @enderror"
                        required>

                    <option value="">-- Select an event --</option>

                    @foreach($events as $event)
                        <option value="{{ $event->id }}"
                            {{ old('event_id') == $event->id ? 'selected' : '' }}>
                            {{ $event->title ?? 'Event #' . $event->id }}
                        </option>
                    @endforeach

                </select>

                @error('event_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="notes" class="form-label">
                    Budget Notes (Optional)
                </label>

                <textarea name="notes"
                          id="notes"
                          rows="4"
                          class="form-control @error('notes') is-invalid @enderror"
                          placeholder="Enter additional budget information...">{{ old('notes') }}</textarea>

                @error('notes')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    Create Budget
                </button>

                <a href="{{ route('budgets.index') }}"
                   class="btn btn-outline-secondary">
                    Cancel
                </a>
            </div>

        </form>

    </div>
</div>

@endsection
