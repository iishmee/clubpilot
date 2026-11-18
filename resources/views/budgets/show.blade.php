
@extends('layouts.app')

@section('title', 'Budget Details')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Budget Details</h2>
        <p class="text-muted mb-0">
            {{ $budget->event->title ?? 'Event #' . $budget->event_id }}
        </p>
    </div>

    <a href="{{ route('budgets.index') }}"
       class="btn btn-outline-secondary">
        Back to Budgets
    </a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">

        <h5 class="card-title mb-3">Budget Information</h5>

        <p>
            <strong>Event:</strong>
            {{ $budget->event->title ?? 'Event #' . $budget->event_id }}
        </p>

        <p>
            <strong>Created By:</strong>
            {{ $budget->creator->name ?? 'Unknown' }}
        </p>

        <p>
            <strong>Notes:</strong>
            {{ $budget->notes ?: 'No additional notes.' }}
        </p>

        <hr>

        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Total Budget</h5>

            <h4 class="text-primary mb-0">
                {{ number_format($budget->total_amount, 2) }}
            </h4>
        </div>

    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0">Expense Items</h5>
    </div>

    <div class="card-body p-0">

        @if($budget->items->isEmpty())

            <p class="text-muted text-center p-4 mb-0">
                No expense items have been added yet.
            </p>

        @else

            <div class="table-responsive">
                <table class="table table-hover mb-0">

                    <thead class="table-light">
                        <tr>
                            <th>Description</th>
                            <th>Quantity</th>
                            <th>Unit Cost</th>
                            <th>Total Cost</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($budget->items as $item)
                            <tr>
                                <td>{{ $item->description }}</td>

                                <td>{{ $item->quantity }}</td>

                                <td>
                                    {{ number_format((float) $item->unit_cost, 2) }}
                                </td>

                                <td>
                                    {{ number_format($item->total_cost, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>

        @endif

    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0">Add Expense Item</h5>
    </div>

    <div class="card-body">

        <form action="{{ route('budgets.items.store', $budget) }}"
              method="POST">

            @csrf

            <div class="mb-3">
                <label for="description" class="form-label">
                    Description
                </label>

                <input type="text"
                       name="description"
                       id="description"
                       class="form-control @error('description') is-invalid @enderror"
                       value="{{ old('description') }}"
                       placeholder="e.g. Food, Decorations, Equipment"
                       required>

                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row">

                <div class="col-md-6 mb-3">
                    <label for="quantity" class="form-label">
                        Quantity
                    </label>

                    <input type="number"
                           name="quantity"
                           id="quantity"
                           class="form-control @error('quantity') is-invalid @enderror"
                           value="{{ old('quantity', 1) }}"
                           min="1"
                           required>

                    @error('quantity')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="unit_cost" class="form-label">
                        Unit Cost
                    </label>

                    <input type="number"
                           name="unit_cost"
                           id="unit_cost"
                           class="form-control @error('unit_cost') is-invalid @enderror"
                           value="{{ old('unit_cost') }}"
                           min="0"
                           step="0.01"
                           required>

                    @error('unit_cost')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

            </div>

            <button type="submit" class="btn btn-primary">
                Add Expense Item
            </button>

        </form>

    </div>
</div>

<div class="d-flex justify-content-end">

    @if(auth()->user()->isAdmin() ||
        (auth()->user()->isOrganizer() &&
         $budget->event->organizer_id == auth()->id()))

        <form action="{{ route('budgets.destroy', $budget) }}"
              method="POST"
              onsubmit="return confirm('Are you sure you want to delete this budget? All associated expense items will also be deleted.')">

            @csrf
            @method('DELETE')

            <button type="submit" class="btn btn-danger">
                Delete Budget
            </button>

        </form>

    @endif

</div>

@endsection
