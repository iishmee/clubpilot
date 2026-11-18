
@extends('layouts.app')

@section('title', 'Event Budgets')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Event Budgets</h2>
        <p class="text-muted mb-0">Manage and monitor event expenses.</p>
    </div>

    @if(auth()->user()->isOrganizer())
        <a href="{{ route('budgets.create') }}" class="btn btn-primary">
            Create Budget
        </a>
    @endif
</div>

@if($budgets->isEmpty())
    <div class="alert alert-info">
        No budgets have been created yet.
    </div>
@else
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Event</th>
                            <th>Created By</th>
                            <th>Notes</th>
                            <th>Total Amount</th>
                            <th>Items</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($budgets as $budget)
                            <tr>
                                <td>
                                    {{ $budget->event->title ?? 'Event #' . $budget->event_id }}
                                </td>

                                <td>
                                    {{ $budget->creator->name ?? 'Unknown' }}
                                </td>

                                <td>
                                    {{ $budget->notes ?: 'N/A' }}
                                </td>

                                <td>
                                    {{ number_format($budget->total_amount, 2) }}
                                </td>

                                <td>
                                    {{ $budget->items->count() }}
                                </td>

                                <td>
                                    <a href="{{ route('budgets.show', $budget) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        View Details
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
