@extends('layouts.app')

@section('title', 'Shifts · ClubPilot')

@section('content')
    <p class="text-muted mb-1">{{ $opportunity->event?->title }}</p>
    <h1 class="h2 mb-3">Shifts: {{ $opportunity->title }}</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    @if ($canManage)
        <section class="card card-body mb-4">
            <h2 class="h5">Add a shift</h2>
            <form method="POST" action="{{ route('shifts.store', $opportunity) }}" class="row g-3">
                @csrf
                <div class="col-md-3">
                    <label class="form-label" for="title">Shift name</label>
                    <input class="form-control" type="text" id="title" name="title" value="{{ old('title') }}" required maxlength="255" placeholder="Morning registration">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="starts_at">Starts</label>
                    <input class="form-control" type="datetime-local" id="starts_at" name="starts_at" value="{{ old('starts_at') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="ends_at">Ends</label>
                    <input class="form-control" type="datetime-local" id="ends_at" name="ends_at" value="{{ old('ends_at') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="capacity">Volunteers needed</label>
                    <input class="form-control" type="number" min="1" id="capacity" name="capacity" value="{{ old('capacity', 1) }}" required>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary">Add shift</button>
                </div>
            </form>
        </section>
    @endif

    @forelse ($shifts as $shift)
        <article class="border rounded p-3 mb-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <strong>{{ $shift->title ? $shift->title . ' · ' : '' }}{{ $shift->starts_at->format('M j, Y H:i') }} – {{ $shift->ends_at->format('M j, Y H:i') }}</strong>
                    <span class="badge text-bg-secondary ms-2">{{ $shift->assignments->count() }} / {{ $shift->capacity }} filled</span>
                </div>
                @if ($canManage)
                    <form method="POST" action="{{ route('shifts.destroy', $shift) }}">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">Delete shift</button>
                    </form>
                @endif
            </div>

            <ul class="list-unstyled mt-2 mb-2">
                @forelse ($shift->assignments as $assignment)
                    <li class="d-flex justify-content-between align-items-center py-1">
                        <span>{{ $assignment->user?->name }}</span>
                        @if ($canManage)
                            <form method="POST" action="{{ route('shifts.unassign', $assignment) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-secondary">Remove</button>
                            </form>
                        @endif
                    </li>
                @empty
                    <li class="text-muted">No volunteers assigned yet.</li>
                @endforelse
            </ul>

            @if ($canManage)
                @php($assignedIds = $shift->assignments->pluck('user_id')->all())
                @php($available = $volunteers->reject(fn ($volunteer) => in_array($volunteer->id, $assignedIds, true)))
                @if ($available->isNotEmpty())
                    <form method="POST" action="{{ route('shifts.assign', $shift) }}" class="d-flex gap-2">
                        @csrf
                        <select class="form-select" name="user_id" required>
                            <option value="">Choose an accepted volunteer…</option>
                            @foreach ($available as $volunteer)
                                <option value="{{ $volunteer->id }}">{{ $volunteer->name }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-primary">Assign</button>
                    </form>
                @else
                    <p class="text-muted mb-0">No more accepted volunteers available to assign.</p>
                @endif
            @endif
        </article>
    @empty
        <p>No shifts yet.</p>
    @endforelse

    <p class="mt-4"><a href="{{ route('volunteers.show', $opportunity) }}">← Back to opportunity</a></p>
@endsection
