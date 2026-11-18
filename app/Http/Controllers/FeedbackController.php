<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    /**
     * Display feedback based on the user's role.
     */
    public function index()
    {
        $user = auth()->user();

        $query = Feedback::with(['event', 'user']);

        if ($user->isAdmin()) {
            // Admin can view all feedback.
        } elseif ($user->isOrganizer()) {
            // Organizers can only view feedback for their events.
            $query->whereHas('event', function ($q) use ($user) {
                $q->where('organizer_id', $user->id);
            });
        } else {
            // Members can view their own feedback.
            $query->where('user_id', $user->id);
        }

        $feedbacks = $query->latest()->get();

        return view('feedback.index', compact('feedbacks'));
    }

    /**
     * Show the form for submitting feedback.
     */
    public function create()
    {
        $user = auth()->user();

        abort_unless($user->role === 'member', 403);

        $events = Event::where(
            'proposal_status',
            Event::STATUS_APPROVED
        )
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->whereDoesntHave('feedbacks', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->orderByDesc('ends_at')
            ->get();

        return view('feedback.create', compact('events'));
    }

    /**
     * Store submitted feedback.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        abort_unless($user->role === 'member', 403);

        $validated = $request->validate([
            'event_id' => ['required', 'exists:events,id'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comments' => ['nullable', 'string', 'max:5000'],
        ]);

        $event = Event::findOrFail($validated['event_id']);

        // Feedback is allowed only for approved events that have ended.
        if (
            $event->proposal_status !== Event::STATUS_APPROVED ||
            !$event->ends_at ||
            !$event->ends_at->isPast()
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'event_id' => 'Feedback can only be submitted for approved events that have ended.',
                ]);
        }

        // Prevent duplicate feedback from the same user.
        $alreadySubmitted = Feedback::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadySubmitted) {
            return back()
                ->withInput()
                ->withErrors([
                    'event_id' => 'You have already submitted feedback for this event.',
                ]);
        }

        Feedback::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'rating' => $validated['rating'],
            'comments' => $validated['comments'] ?? null,
        ]);

        return redirect()
            ->route('feedback.index')
            ->with('success', 'Your feedback has been submitted successfully.');
    }

    /**
     * Display an individual feedback entry.
     */
    public function show(string $id)
    {
        $feedback = Feedback::with(['event', 'user'])
            ->findOrFail($id);

        $user = auth()->user();

        $canView = $user->isAdmin()
            || (
                $user->isOrganizer() &&
                $feedback->event->organizer_id == $user->id
            )
            || $feedback->user_id == $user->id;

        abort_unless($canView, 403);

        return view('feedback.show', compact('feedback'));
    }
}
