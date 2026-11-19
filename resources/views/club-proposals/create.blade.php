@extends('layouts.app')

@section('title', 'Submit a club proposal · ClubPilot')

@section('content')
    <div class="mb-4">
        <a href="{{ route('club-proposals.index') }}" class="text-decoration-none">← Back to my proposals</a>
        <h1 class="h2 mt-3 mb-1">Propose a new university club</h1>
        <p class="text-secondary mb-0">Provide the club details and objectives for administrator review.</p>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('club-proposals.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label fw-semibold">Proposed club name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}"
                           class="form-control @error('name') is-invalid @enderror" maxlength="150" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="details" class="form-label fw-semibold">Club details</label>
                    <textarea id="details" name="details" rows="5"
                              class="form-control @error('details') is-invalid @enderror" maxlength="5000" required>{{ old('details') }}</textarea>
                    @error('details') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="objectives" class="form-label fw-semibold">Club objectives</label>
                    <textarea id="objectives" name="objectives" rows="5"
                              class="form-control @error('objectives') is-invalid @enderror" maxlength="5000" required>{{ old('objectives') }}</textarea>
                    @error('objectives') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-4">
                    <label for="constitution" class="form-label fw-semibold">Club constitution (optional)</label>
                    <input id="constitution" name="constitution" type="file"
                           class="form-control @error('constitution') is-invalid @enderror"
                           accept=".pdf,.doc,.docx">
                    <div class="form-text">Accepted formats: PDF, DOC or DOCX. Maximum size: 10 MB. The file is stored privately.</div>
                    @error('constitution') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary">Submit proposal</button>
                    <a href="{{ route('club-proposals.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
