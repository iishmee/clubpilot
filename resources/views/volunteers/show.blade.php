@extends('layouts.app')

@section('title', $opportunity->title.' · ClubPilot')

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <p class="text-muted mb-1">{{ $opportunity->event?->title }}</p>
            <h1 class="h2">{{ $opportunity->title }}</h1>
        </div>
        <span class="badge text-bg-primary">{{ str($opportunity->status)->title() }}</span>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <dl class="row">
        <dt class="col-sm-3">Description</dt>
        <dd class="col-sm-9">{!! nl2br(e($opportunity->description ?: 'No description provided.')) !!}</dd>
        <dt class="col-sm-3">Slots filled</dt>
        <dd class="col-sm-9">{{ $acceptedCount }} / {{ $opportunity->slots_needed }}</dd>
    </dl>

    @if (auth()->user()->isAdmin())
        <div class="mb-3"><a class="btn btn-outline-primary" href="{{ route('shifts.index', $opportunity) }}">View shifts</a></div>
    @endif

    @can('manage', $opportunity)
        <div class="mb-3"><a class="btn btn-outline-primary" href="{{ route('shifts.index', $opportunity) }}">Manage shifts</a></div>

        @if ($opportunity->status === 'open')
            <form method="POST" action="{{ route('volunteers.close', $opportunity) }}" class="mb-4">
                @csrf
                <button class="btn btn-outline-secondary">Close recruitment</button>
            </form>
        @endif

        <h2 class="h4">Applications</h2>
        @forelse ($applications as $application)
            <article class="border rounded p-3 mb-2">
                <div class="d-flex justify-content-between align-items-center">
                    <strong>{{ $application->user?->name }}</strong>
                    <span class="badge text-bg-secondary">{{ str($application->status)->title() }}</span>
                </div>
                @if ($application->message)<p class="mb-2 mt-2">{{ $application->message }}</p>@endif
                @if ($application->status === 'pending')
                    <form method="POST" action="{{ route('volunteers.applications.review', $application) }}" class="mt-2">
                        @csrf
                        <button name="decision" value="accepted" class="btn btn-sm btn-success">Accept</button>
                        <button name="decision" value="rejected" class="btn btn-sm btn-outline-danger">Reject</button>
                    </form>
                @endif
            </article>
        @empty
            <p>No applications yet.</p>
        @endforelse
    @endcan

    @if (auth()->user()->role === 'member')
        @if ($myApplication)
            <div class="alert alert-info">
                Your application status: <strong>{{ str($myApplication->status)->title() }}</strong>
            </div>
        @else
            @can('apply', $opportunity)
                <section class="card card-body">
                    <h2 class="h5">Apply to volunteer</h2>
                    <form method="POST" action="{{ route('volunteers.apply', $opportunity) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="message">Why do you want to help? (optional)</label>
                            <textarea class="form-control" id="message" name="message" rows="3">{{ old('message') }}</textarea>
                        </div>
                        <button class="btn btn-primary">Apply</button>
                    </form>
                </section>
            @else
                <p class="text-muted">This opportunity is closed.</p>
            @endcan
        @endif
    @endif

    <p class="mt-4"><a href="{{ route('volunteers.index') }}">← Back to all opportunities</a></p>
@endsection
