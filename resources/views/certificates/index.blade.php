@extends('layouts.app')

@section('title', 'Certificates · ClubPilot')

@section('content')
    <h1 class="h2 mb-3">Participation certificates</h1>

    @if (auth()->user()->isOrganizer())
        <section class="card card-body mb-4">
            <h2 class="h5">Ready to issue</h2>
            <p class="text-muted">Volunteers of your events who have approved service hours and no certificate yet.</p>
            @if ($eligible->isEmpty())
                <p class="mb-0">Nobody is waiting for a certificate. Approve logged hours on the
                    <a href="{{ route('service-hours.index') }}">Service hours</a> page first.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Volunteer</th><th>Opportunity</th><th>Approved hours</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($eligible as $row)
                                <tr>
                                    <td>{{ $row->user?->name }}</td>
                                    <td>
                                        {{ $row->opportunity?->title }}
                                        <div class="text-muted small">{{ $row->opportunity?->event?->title }}</div>
                                    </td>
                                    <td>{{ number_format((float) $row->total_hours, 2) }}</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('certificates.store', $row->opportunity) }}">
                                            @csrf
                                            <input type="hidden" name="user_id" value="{{ $row->user_id }}">
                                            <button class="btn btn-sm btn-primary">Issue certificate</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif

    <h2 class="h4">{{ $isMember ? 'My certificates' : 'Issued certificates' }}</h2>
    @if ($certificates->isEmpty())
        <p>
            No certificates yet.
            @if ($isMember)
                Certificates are issued by the event organizer after your service hours are approved.
            @endif
        </p>
    @else
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Certificate no.</th>
                        @unless ($isMember)<th>Volunteer</th>@endunless
                        <th>Opportunity</th>
                        <th>Hours</th>
                        <th>Issued</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($certificates as $certificate)
                        <tr>
                            <td>{{ $certificate->certificate_number }}</td>
                            @unless ($isMember)<td>{{ $certificate->user?->name }}</td>@endunless
                            <td>
                                {{ $certificate->opportunity?->title }}
                                <div class="text-muted small">{{ $certificate->opportunity?->event?->title }}</div>
                            </td>
                            <td>{{ number_format((float) $certificate->hours, 2) }}</td>
                            <td>{{ $certificate->issued_at?->format('M j, Y') }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('certificates.download', $certificate) }}">Download PDF</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $certificates->links() }}
    @endif
@endsection
