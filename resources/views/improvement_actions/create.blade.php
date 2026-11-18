
@extends('layouts.app')

@section('title', 'Create Improvement Action')

@section('content')

<div class="mb-4">
    <h2>Create Improvement Action</h2>
    <p class="text-muted">
        Record an action to address feedback and improve future events.
    </p>
</div>

<div class="card shadow-sm">
    <div class="card-body">

        <form action="{{ route('improvement-actions.store') }}" method="POST">
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
                <label for="feedback_id" class="form-label">
                    Related Feedback (Optional)
                </label>

                <select name="feedback_id"
                        id="feedback_id"
                        class="form-select @error('feedback_id') is-invalid @enderror">

                    <option value="">-- No specific feedback --</option>

                    @foreach($feedbacks as $feedback)
                        <option value="{{ $feedback->id }}"
                            {{ old('feedback_id') == $feedback->id ? 'selected' : '' }}>
                            {{ $feedback->event->title ?? 'Event #' . $feedback->event_id }}
                            — {{ $feedback->rating }}/5
                            — {{ $feedback->user->name ?? 'Unknown' }}
                        </option>
                    @endforeach

                </select>

                @error('feedback_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                <small class="text-muted">
                    Select feedback that this improvement action addresses.
                </small>
            </div>

            <div class="mb-3">
                <label for="title" class="form-label">
                    Action Title
                </label>

                <input type="text"
                       name="title"
                       id="title"
                       value="{{ old('title') }}"
                       class="form-control @error('title') is-invalid @enderror"
                       placeholder="Example: Improve event registration process"
                       required>

                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">
                    Description
                </label>

                <textarea name="description"
                          id="description"
                          rows="5"
                          class="form-control @error('description') is-invalid @enderror"
                          placeholder="Describe the action that needs to be taken..."
                          required>{{ old('description') }}</textarea>

                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="status" class="form-label">
                    Status
                </label>

                <select name="status"
                        id="status"
                        class="form-select @error('status') is-invalid @enderror"
                        required>

                    <option value="pending"
                        {{ old('status', 'pending') === 'pending' ? 'selected' : '' }}>
                        Pending
                    </option>

                    <option value="in_progress"
                        {{ old('status') === 'in_progress' ? 'selected' : '' }}>
                        In Progress
                    </option>

                    <option value="completed"
                        {{ old('status') === 'completed' ? 'selected' : '' }}>
                        Completed
                    </option>

                </select>

                @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="due_date" class="form-label">
                    Due Date (Optional)
                </label>

                <input type="date"
                       name="due_date"
                       id="due_date"
                       value="{{ old('due_date') }}"
                       class="form-control @error('due_date') is-invalid @enderror">

                @error('due_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    Create Action
                </button>

                <a href="{{ route('improvement-actions.index') }}"
                   class="btn btn-outline-secondary">
                    Cancel
                </a>
            </div>

        </form>

    </div>
</div>

@endsection
