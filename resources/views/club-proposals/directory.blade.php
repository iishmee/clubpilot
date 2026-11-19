@extends('layouts.app')

@section('title', 'Club directory · ClubPilot')

@section('content')
    <div class="mb-4">
        <h1 class="h2 mb-1">Club directory</h1>
        <p class="text-secondary mb-0">University clubs approved through ClubPilot.</p>
    </div>

    <div class="row g-3">
        @forelse ($clubs as $club)
            <div class="col-12 col-md-6">
                <article class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="h5">{{ $club->name }}</h2>
                        <p class="text-secondary" style="white-space: pre-line">{{ $club->details }}</p>
                        <h3 class="h6">Objectives</h3>
                        <p class="mb-0" style="white-space: pre-line">{{ $club->objectives }}</p>
                    </div>
                </article>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center text-secondary py-5">
                        No approved clubs are available yet.
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-3">{{ $clubs->links() }}</div>
@endsection
