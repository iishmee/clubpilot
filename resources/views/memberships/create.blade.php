
@extends('layouts.app')

@section('title', 'Apply for Membership · ClubPilot')

@section('content')

<div class="mb-4">
    <a href="{{ route('dashboard') }}" class="text-decoration-none">
        &larr; Back to Dashboard
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">

        <div class="card border-0 shadow-sm">

            <div class="card-body p-4 p-md-5">

                <h1 class="h3 mb-2">Club Membership Application</h1>

                <p class="text-secondary mb-4">
                    Complete the form below to apply for membership
                    in your preferred university club.
                </p>

                @if(session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger">
                        <strong>Please correct the following errors:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('memberships.store') }}">

                    @csrf

                    <h2 class="h5 mb-3">Student Information</h2>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Student Name
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            value="{{ auth()->user()->name }}"
                            readonly
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Student ID
                        </label>

                        <input
                            type="text"
                            name="student_id"
                            class="form-control"
                            value="{{ old('student_id') }}"
                            placeholder="Enter your student ID"
                            required
                        >

                        @error('student_id')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Department
                        </label>

                        <input
                            type="text"
                            name="department"
                            class="form-control"
                            value="{{ old('department') }}"
                            placeholder="Enter your department"
                            required
                        >

                        @error('department')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            Current Semester
                        </label>

                        <input
                            type="text"
                            name="semester"
                            class="form-control"
                            value="{{ old('semester') }}"
                            placeholder="Example: 5th Semester"
                            required
                        >

                        @error('semester')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <hr>

                    <h2 class="h5 mb-3">Club Selection</h2>

                    <div class="mb-3">

                        <label for="club_id" class="form-label fw-semibold">
                            Select Your Preferred Club
                        </label>

                        <select
                            name="club_id"
                            id="club_id"
                            class="form-select"
                            required
                        >

                            <option value="">Choose a club</option>

                            @foreach($clubs as $club)
                                <option
                                    value="{{ $club->id }}"
                                    @selected(old('club_id') == $club->id)
                                >
                                    {{ $club->name }}
                                </option>
                            @endforeach

                        </select>

                        @error('club_id')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror

                        @if($clubs->isEmpty())
                            <div class="alert alert-warning mt-2">
                                No recognised clubs are currently available.
                            </div>
                        @endif

                    </div>

                    <div class="mb-4">

                        <label for="application_message"
                               class="form-label fw-semibold">
                            Why do you want to join this club?
                        </label>

                        <textarea
                            name="application_message"
                            id="application_message"
                            rows="4"
                            class="form-control"
                            placeholder="Write a short introduction about yourself and your interest..."
                        >{{ old('application_message') }}</textarea>

                        @error('application_message')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror

                    </div>

                    <div class="d-flex flex-wrap gap-2">

                        <button
                            type="submit"
                            class="btn btn-primary px-4"
                            {{ $clubs->isEmpty() ? 'disabled' : '' }}
                        >
                            Submit Application
                        </button>

                        <a href="{{ route('dashboard') }}"
                           class="btn btn-outline-secondary">
                            Cancel
                        </a>

                    </div>

                </form>

            </div>
        </div>

    </div>
</div>

@endsection

