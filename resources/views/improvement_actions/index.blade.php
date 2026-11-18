
@extends('layouts.app')

@section('title', 'Improvement Actions')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Improvement Actions</h2>
        <p class="text-muted mb-0">
            Track actions taken to improve future events.
        </p>
    </div>

    <a href="{{ route('improvement-actions.create') }}"
       class="btn btn-primary">
        Create Improvement Action
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if($actions->isEmpty())

    <div class="alert alert-info">
        No improvement actions have been created yet.
    </div>

@else

    <div class="card shadow-sm">
        <div class="card-body p-0">

            <div class="table-responsive">
                <table class="table table-hover mb-0">

                    <thead class="table-light">
                        <tr>
                            <th>Action</th>
                            <th>Event</th>
                            <th>Status</th>
                            <th>Due Date</th>
                            <th>Created By</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($actions as $action)
                            <tr>
                                <td>
                                    {{ $action->title }}
                                </td>

                                <td>
                                    {{ $action->event->title ?? 'Event #' . $action->event_id }}
                                </td>

                                <td>
                                    @if($action->status === 'completed')
                                        <span class="badge bg-success">Completed</span>
                                    @elseif($action->status === 'in_progress')
                                        <span class="badge bg-primary">In Progress</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>

                                <td>
                                    {{ $action->due_date
                                        ? $action->due_date->format('d M Y')
                                        : 'Not set' }}
                                </td>

                                <td>
                                    {{ $action->creator->name ?? 'Unknown' }}
                                </td>

                                <td>
                                    <a href="{{ route('improvement-actions.show', $action) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>

        </div>
    </div>

@endif

@endsection
