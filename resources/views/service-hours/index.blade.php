@extends('layouts.app')

@section('title', 'Service hours · ClubPilot')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h2 mb-0">Service hours</h1>
        @can('create', \App\Models\ServiceEntry::class)
            <a class="btn btn-primary" href="{{ route('service-hours.create') }}">Log hours</a>
        @endcan
    </div>

    @if ($isMember)
        <div class="row mb-4">
            <div class="col-sm-6 mb-2">
                <div class="card card-body">
                    <div class="text-muted">Approved hours</div>
                    <div class="display-6">{{ number_format($summary['approved'], 2) }}</div>
                </div>
            </div>
            <div class="col-sm-6 mb-2">
                <div class="card card-body">
                    <div class="text-muted">Waiting for review</div>
                    <div class="display-6">{{ number_format($summary['pending'], 2) }}</div>
                </div>
            </div>
        </div>
    @else
        <section class="card card-body mb-4">
            <h2 class="h5">Approved hours per volunteer</h2>
            @if ($totals->isEmpty())
                <p class="mb-0 text-muted">No approved hours yet.</p>
            @else
                <table class="table table-sm mb-0">
                    <thead><tr><th>Volunteer</th><th class="text-end">Approved hours</th></tr></thead>
                    <tbody>
                        @foreach ($totals as $row)
                            <tr>
                                <td>{{ $row->user?->name }}</td>
                                <td class="text-end">{{ number_format((float) $row->total_hours, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    @endif

    <h2 class="h4">{{ $isMember ? 'My entries' : 'Logged hours' }}</h2>
    @if ($entries->isEmpty())
        <p>No hours logged yet.</p>
    @else
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        @unless ($isMember)<th>Volunteer</th>@endunless
                        <th>Opportunity</th>
                        <th>Hours</th>
                        <th>Status</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($entries as $entry)
                        <tr>
                            <td>{{ $entry->worked_on?->format('M j, Y') }}</td>
                            @unless ($isMember)<td>{{ $entry->user?->name }}</td>@endunless
                            <td>
                                {{ $entry->opportunity?->title }}
                                <div class="text-muted small">{{ $entry->opportunity?->event?->title }}</div>
                            </td>
                            <td>{{ number_format((float) $entry->hours, 2) }}</td>
                            <td>
                                <span class="badge text-bg-{{ $entry->status === 'approved' ? 'success' : ($entry->status === 'rejected' ? 'danger' : 'secondary') }}">
                                    {{ str($entry->status)->title() }}
                                </span>
                            </td>
                            <td>
                                @if ($entry->description)<div>{{ $entry->description }}</div>@endif
                                @if ($entry->review_note)<div class="text-muted small">Reviewer note: {{ $entry->review_note }}</div>@endif
                                @can('review', $entry)
                                    @if ($entry->status === 'pending')
                                        <form method="POST" action="{{ route('service-hours.review', $entry) }}" class="d-flex gap-2 mt-2">
                                            @csrf
                                            <input class="form-control form-control-sm" name="review_note" maxlength="1000" placeholder="Note (required to reject)">
                                            <button name="decision" value="approved" class="btn btn-sm btn-success">Approve</button>
                                            <button name="decision" value="rejected" class="btn btn-sm btn-outline-danger">Reject</button>
                                        </form>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $entries->links() }}
    @endif
@endsection
