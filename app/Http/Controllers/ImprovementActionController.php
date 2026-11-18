<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Feedback;
use App\Models\ImprovementAction;
use Illuminate\Http\Request;

class ImprovementActionController extends Controller
{
    /**
     * Display improvement actions.
     */
    public function index()
    {
        $user = auth()->user();

        $this->authorizeAccess();

        $query = ImprovementAction::with([
            'event',
            'feedback',
            'creator',
        ]);

        if (!$user->isAdmin()) {
            $query->whereHas('event', function ($q) use ($user) {
                $q->where('organizer_id', $user->id);
            });
        }

        $actions = $query->latest()->get();

        return view('improvement_actions.index', compact('actions'));
    }

    /**
     * Show the form for creating an improvement action.
     */
    public function create()
    {
        $this->authorizeAccess();

        $events = $this->accessibleEvents()->get();

        $eventIds = $events->pluck('id');

        $feedbacks = Feedback::with(['event', 'user'])
            ->whereIn('event_id', $eventIds)
            ->latest()
            ->get();

        return view(
            'improvement_actions.create',
            compact('events', 'feedbacks')
        );
    }

    /**
     * Store a newly created improvement action.
     */
    public function store(Request $request)
    {
        $this->authorizeAccess();

        $validated = $request->validate([
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'feedback_id' => ['nullable', 'integer', 'exists:feedback,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'status' => [
                'required',
                'in:pending,in_progress,completed',
            ],
            'due_date' => ['nullable', 'date'],
        ]);

        $event = $this->accessibleEvents()
            ->findOrFail($validated['event_id']);

        if (!empty($validated['feedback_id'])) {
            $feedback = Feedback::findOrFail($validated['feedback_id']);

            if ($feedback->event_id != $event->id) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'feedback_id' =>
                            'The selected feedback does not belong to this event.',
                    ]);
            }
        }

        $action = ImprovementAction::create([
            'event_id' => $event->id,
            'feedback_id' => $validated['feedback_id'] ?? null,
            'created_by' => auth()->id(),
            'title' => $validated['title'],
            'description' => $validated['description'],
            'status' => $validated['status'],
            'due_date' => $validated['due_date'] ?? null,
        ]);

        return redirect()
            ->route('improvement-actions.show', $action)
            ->with('success', 'Improvement action created successfully.');
    }

    /**
     * Display a specific improvement action.
     */
    public function show(ImprovementAction $improvementAction)
    {
        $this->authorizeAction($improvementAction);

        $improvementAction->load([
            'event',
            'feedback.user',
            'creator',
        ]);

        return view(
            'improvement_actions.show',
            compact('improvementAction')
        );
    }

    /**
     * Show the form for editing an improvement action.
     */
    public function edit(ImprovementAction $improvementAction)
    {
        $this->authorizeAction($improvementAction);

        $events = $this->accessibleEvents()->get();

        $eventIds = $events->pluck('id');

        $feedbacks = Feedback::with(['event', 'user'])
            ->whereIn('event_id', $eventIds)
            ->latest()
            ->get();

        return view(
            'improvement_actions.edit',
            compact('improvementAction', 'events', 'feedbacks')
        );
    }

    /**
     * Update an existing improvement action.
     */
    public function update(
        Request $request,
        ImprovementAction $improvementAction
    ) {
        $this->authorizeAction($improvementAction);

        $validated = $request->validate([
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'feedback_id' => ['nullable', 'integer', 'exists:feedback,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'status' => [
                'required',
                'in:pending,in_progress,completed',
            ],
            'due_date' => ['nullable', 'date'],
        ]);

        $event = $this->accessibleEvents()
            ->findOrFail($validated['event_id']);

        if (!empty($validated['feedback_id'])) {
            $feedback = Feedback::findOrFail($validated['feedback_id']);

            if ($feedback->event_id != $event->id) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'feedback_id' =>
                            'The selected feedback does not belong to this event.',
                    ]);
            }
        }

        $improvementAction->update([
            'event_id' => $event->id,
            'feedback_id' => $validated['feedback_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'status' => $validated['status'],
            'due_date' => $validated['due_date'] ?? null,
        ]);

        return redirect()
            ->route('improvement-actions.show', $improvementAction)
            ->with('success', 'Improvement action updated successfully.');
    }

    /**
     * Delete an improvement action.
     */
    public function destroy(ImprovementAction $improvementAction)
    {
        $this->authorizeAction($improvementAction);

        $improvementAction->delete();

        return redirect()
            ->route('improvement-actions.index')
            ->with('success', 'Improvement action deleted successfully.');
    }

    /**
     * Ensure the current user is an admin or organizer.
     */
    private function authorizeAccess(): void
    {
        $user = auth()->user();

        abort_unless(
            $user->isAdmin() || $user->isOrganizer(),
            403
        );
    }

    /**
     * Return events accessible to the current user.
     */
    private function accessibleEvents()
    {
        $user = auth()->user();

        $query = Event::query();

        if (!$user->isAdmin()) {
            $query->where('organizer_id', $user->id);
        }

        return $query;
    }

    /**
     * Ensure the user can access a specific improvement action.
     */
    private function authorizeAction(
        ImprovementAction $improvementAction
    ): void {
        $this->authorizeAccess();

        $user = auth()->user();

        if (!$user->isAdmin()) {
            abort_unless(
                $improvementAction->event->organizer_id == $user->id,
                403
            );
        }
    }
}

