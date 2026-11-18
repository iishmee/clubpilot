
@extends('layouts.app')

@section('title', 'Submit Event Feedback')

@section('content')

<div class="mb-4">
    <h2>Submit Event Feedback</h2>
    <p class="text-muted">
        Share your experience and help improve future events.
    </p>
</div>

@if($events->isEmpty())

    <div class="alert alert-info">
        There are currently no eligible events for feedback.
    </div>

    <a href="{{ route('feedback.index') }}"
       class="btn btn-outline-secondary">
        Back to Feedback
    </a>

@else

    <div class="card shadow-sm">
        <div class="card-body">

            <form action="{{ route('feedback.store') }}" method="POST">
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
                    <label for="rating" class="form-label">
                        Overall Rating
                    </label>

                    <select name="rating"
                            id="rating"
                            class="form-select @error('rating') is-invalid @enderror"
                            required>

                        <option value="">-- Select a rating --</option>

                        <option value="5" {{ old('rating') == 5 ? 'selected' : '' }}>
                            5 - Excellent
                        </option>

                        <option value="4" {{ old('rating') == 4 ? 'selected' : '' }}>
                            4 - Very Good
                        </option>

                        <option value="3" {{ old('rating') == 3 ? 'selected' : '' }}>
                            3 - Average
                        </option>

                        <option value="2" {{ old('rating') == 2 ? 'selected' : '' }}>
                            2 - Poor
                        </option>

                        <option value="1" {{ old('rating') == 1 ? 'selected' : '' }}>
                            1 - Very Poor
                        </option>

                    </select>

                    @error('rating')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="comments" class="form-label">
                        Comments (Optional)
                    </label>

                    <textarea name="comments"
                              id="comments"
                              rows="5"
                              class="form-control @error('comments') is-invalid @enderror"
                              placeholder="Share your thoughts about the event...">{{ old('comments') }}</textarea>

                    @error('comments')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2">

                    <button type="submit" class="btn btn-primary">
                        Submit Feedback
                    </button>

                    <a href="{{ route('feedback.index') }}"
                       class="btn btn-outline-secondary">
                        Cancel
                    </a>

                </div>

            </form>

        </div>
    </div>

@endif

@endsection
