<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    /**
     * Display a listing of budgets.
     */
    public function index()
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            // Admins can view all budgets.
            $budgets = Budget::with(['event', 'creator', 'items'])
                ->latest()
                ->get();
        } elseif ($user->isOrganizer()) {
            // Organizers can view budgets for their own events.
            $budgets = Budget::with(['event', 'creator', 'items'])
                ->whereHas('event', function ($query) use ($user) {
                    $query->where('organizer_id', $user->id);
                })
                ->latest()
                ->get();
        } else {
            abort(403, 'You are not authorized to view budgets.');
        }

        return view('budgets.index', compact('budgets'));
    }

    public function create()
    {
    $user = auth()->user();

    if (!$user->isOrganizer()) {
        abort(403, 'Only organizers can create budgets.');
    }

    $events = $user->events()
        ->whereIn('proposal_status', ['approved'])
        ->whereDoesntHave('budget')
        ->get();

    return view('budgets.create', compact('events'));
    }

    public function store(Request $request)
    {
    $user = auth()->user();

    if (!$user->isOrganizer()) {
        abort(403, 'Only organizers can create budgets.');
    }

    $validated = $request->validate([
        'event_id' => ['required', 'exists:events,id'],
        'notes' => ['nullable', 'string', 'max:2000'],
    ]);

    $event = $user->events()
        ->where('id', $validated['event_id'])
        ->where('proposal_status', 'approved')
        ->firstOrFail();

    if ($event->budget()->exists()) {
        return back()->withErrors([
            'event_id' => 'A budget already exists for this event.',
        ]);
    }

    $budget = Budget::create([
        'event_id' => $event->id,
        'created_by' => $user->id,
        'notes' => $validated['notes'] ?? null,
    ]);

    return redirect()
        ->route('budgets.show', $budget)
        ->with('success', 'Budget created successfully.');
    }

    public function show(string $id)
    {
    $budget = Budget::with(['event', 'creator', 'items'])
        ->findOrFail($id);

    $user = auth()->user();

    if (
        !$user->isAdmin() &&
        (!$user->isOrganizer() || $budget->event->organizer_id != $user->id)
    ) {
        abort(403, 'You are not authorized to view this budget.');
    }

    return view('budgets.show', compact('budget'));
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        //
    }


    public function destroy(string $id)
    {
    $budget = Budget::with('event')->findOrFail($id);

    $user = auth()->user();

    if (
        !$user->isAdmin() &&
        (!$user->isOrganizer() || $budget->event->organizer_id != $user->id)
    ) {
        abort(403, 'You are not authorized to delete this budget.');
    }

    $budget->delete();

    return redirect()
        ->route('budgets.index')
        ->with('success', 'Budget deleted successfully.');
    }



    public function addItem(Request $request, Budget $budget)
    {
    $user = auth()->user();

    if (
        !$user->isAdmin() &&
        (!$user->isOrganizer() || $budget->event->organizer_id != $user->id)
    ) {
        abort(403, 'You are not authorized to modify this budget.');
    }

    $validated = $request->validate([
        'description' => ['required', 'string', 'max:255'],
        'quantity' => ['required', 'integer', 'min:1'],
        'unit_cost' => ['required', 'numeric', 'min:0'],
    ]);

    $budget->items()->create($validated);

    return redirect()
        ->route('budgets.show', $budget)
        ->with('success', 'Budget item added successfully.');
    }

}
