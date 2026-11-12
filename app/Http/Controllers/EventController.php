<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Event::class);

        $events = auth()->user()->isAdmin()
            ? Event::query()->with('organizer')->latest()->paginate(15)
            : Event::query()->where('organizer_id', auth()->id())->latest()->paginate(15);

        return view('events.index', compact('events'));
    }

    public function create(): View
    {
        Gate::authorize('create', Event::class);

        return view('events.create');
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Event::class);
        $event = Event::query()->create([
            ...$this->validatedProposal($request),
            'organizer_id' => $request->user()->id,
            'proposal_status' => Event::STATUS_DRAFT,
        ]);

        return redirect()->route('events.show', $event)->with('status', 'Proposal saved as a draft.');
    }

    public function show(Event $event): View
    {
        Gate::authorize('view', $event);
        $event->load(['organizer', 'reviews.reviewer', 'reservations.venue', 'reservations.requester']);

        return view('events.show', compact('event'));
    }

    public function edit(Event $event): View
    {
        Gate::authorize('update', $event);

        return view('events.edit', compact('event'));
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('update', $event);
        $proposal = $this->validatedProposal($request);

        DB::transaction(function () use ($event, $proposal, $request): void {
            $lockedEvent = Event::query()->lockForUpdate()->findOrFail($event->id);
            Gate::forUser($request->user())->authorize('update', $lockedEvent);
            $lockedEvent->update($proposal);
        });

        return redirect()->route('events.show', $event)->with('status', 'Proposal updated.');
    }

    public function submit(Event $event): RedirectResponse
    {
        Gate::authorize('submit', $event);

        DB::transaction(function () use ($event): void {
            $lockedEvent = Event::query()->lockForUpdate()->findOrFail($event->id);
            Gate::authorize('submit', $lockedEvent);
            $lockedEvent->update([
                'proposal_status' => Event::STATUS_PENDING_REVIEW,
                'submitted_at' => now(),
            ]);
        });

        return redirect()->route('events.show', $event)->with('status', 'Proposal submitted for review.');
    }

    public function reviewIndex(): View
    {
        Gate::authorize('review', Event::class);

        $events = Event::query()
            ->where('proposal_status', Event::STATUS_PENDING_REVIEW)
            ->with('organizer')
            ->latest('submitted_at')
            ->paginate(15);

        return view('events.review-index', compact('events'));
    }

    public function review(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('review', Event::class);

        $data = $request->validate([
            'decision' => ['required', 'in:approved,revision_requested,rejected'],
            'feedback' => ['nullable', 'string', 'required_if:decision,revision_requested,rejected', 'max:5000'],
        ]);

        DB::transaction(function () use ($data, $event, $request): void {
            $lockedEvent = Event::query()->lockForUpdate()->findOrFail($event->id);
            abort_unless(
                $lockedEvent->proposal_status === Event::STATUS_PENDING_REVIEW,
                409,
                'Only pending proposals can be reviewed.',
            );

            $lockedEvent->update([
                'proposal_status' => $data['decision'],
                'review_feedback' => $data['feedback'] ?? null,
            ]);

            EventReview::query()->create([
                'event_id' => $lockedEvent->id,
                'reviewer_id' => $request->user()->id,
                'decision' => $data['decision'],
                'feedback' => $data['feedback'] ?? null,
                'reviewed_at' => now(),
            ]);
        });

        return redirect()->route('events.show', $event)->with('status', 'Proposal review recorded.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedProposal(Request $request): array
    {
        return $request->validate([
            'club_name' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'objectives' => ['required', 'string', 'max:10000'],
            'requirements' => ['nullable', 'string', 'max:10000'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ]);
    }
}
