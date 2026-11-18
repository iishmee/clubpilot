@extends('layouts.app')

@section('title', 'Log service hours · ClubPilot')

@section('content')
    <h1 class="h2">Log service hours</h1>
    <p class="text-muted">Hours you log here are reviewed by the event organizer before they count.</p>

    @if ($opportunities->isEmpty())
        <div class="alert alert-info">
            You can log hours only for opportunities where you were accepted as a volunteer.
            <a href="{{ route('volunteers.index') }}">Browse volunteer opportunities</a>.
        </div>
    @else
        <form method="POST" action="{{ route('service-hours.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label" for="volunteer_opportunity_id">Opportunity</label>
                <select class="form-select" id="volunteer_opportunity_id" name="volunteer_opportunity_id" required>
                    <option value="">Choose…</option>
                    @foreach ($opportunities as $opportunity)
                        <option value="{{ $opportunity->id }}" @selected(old('volunteer_opportunity_id') == $opportunity->id)>
                            {{ $opportunity->title }} — {{ $opportunity->event?->title }}
                        </option>
                    @endforeach
                </select>
                @error('volunteer_opportunity_id')<div class="text-danger">{{ $message }}</div>@enderror
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="hours">Hours worked</label>
                    <input class="form-control" type="number" step="0.25" min="0.25" max="24" id="hours" name="hours" value="{{ old('hours') }}" required>
                    @error('hours')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="worked_on">Date worked</label>
                    <input class="form-control" type="date" id="worked_on" name="worked_on" value="{{ old('worked_on') }}" required>
                    @error('worked_on')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="description">What did you do? (optional)</label>
                <textarea class="form-control" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                @error('description')<div class="text-danger">{{ $message }}</div>@enderror
            </div>
            <button class="btn btn-primary">Submit hours</button>
            <a class="btn btn-link" href="{{ route('service-hours.index') }}">Cancel</a>
        </form>
    @endif
@endsection
