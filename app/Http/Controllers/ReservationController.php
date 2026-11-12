<?php

namespace App\Http\Controllers;

use App\Actions\CreateVenueReservation;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\Venue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Reservation::class);

        $reservations = Reservation::query()
            ->whereNotNull('event_id')
            ->whereNotNull('venue_id')
            ->whereNotNull('requester_id')
            ->whereNotNull('starts_at')
            ->whereNotNull('ends_at')
            ->with(['event', 'venue', 'requester'])
            ->when(! auth()->user()->isAdmin(), fn ($query) => $query->where('requester_id', auth()->id()))
            ->latest()
            ->paginate(15);

        return view('reservations.index', compact('reservations'));
    }

    public function create(Event $event): View
    {
        Gate::authorize('create', [Reservation::class, $event]);

        $venues = Venue::query()
            ->where('availability_status', 'available')
            ->whereNotNull('name')
            ->whereNotNull('location')
            ->whereNotNull('capacity')
            ->orderBy('name')
            ->get();

        return view('reservations.create', compact('event', 'venues'));
    }

    public function store(Request $request, Event $event, CreateVenueReservation $createReservation): RedirectResponse
    {
        Gate::authorize('create', [Reservation::class, $event]);

        $data = $request->validate([
            'venue_id' => ['required', 'integer', 'exists:venues,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ]);
        $createReservation->handle(
            $event->id,
            $data['venue_id'],
            $request->user(),
            $data['starts_at'],
            $data['ends_at'],
        );

        return redirect()->route('events.show', $event)->with('status', 'Venue request submitted for approval.');
    }

    public function show(Reservation $reservation): View
    {
        $reservation->load(['event.organizer', 'venue', 'requester', 'reviewer']);
        Gate::authorize('view', $reservation);

        return view('reservations.show', compact('reservation'));
    }

    public function reviewIndex(): View
    {
        Gate::authorize('reviewAny', Reservation::class);

        $reservations = Reservation::query()
            ->where('status', Reservation::STATUS_PENDING)
            ->whereNotNull('event_id')
            ->whereNotNull('venue_id')
            ->whereNotNull('requester_id')
            ->whereNotNull('starts_at')
            ->whereNotNull('ends_at')
            ->with(['event', 'venue', 'requester'])
            ->oldest()
            ->paginate(15);

        return view('reservations.review-index', compact('reservations'));
    }

    public function review(Request $request, Reservation $reservation): RedirectResponse
    {
        Gate::authorize('review', $reservation);

        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'feedback' => ['nullable', 'string', 'required_if:decision,rejected', 'max:5000'],
        ]);

        DB::transaction(function () use ($data, $reservation, $request): void {
            $lockedReservation = Reservation::query()->lockForUpdate()->findOrFail($reservation->id);
            abort_unless(
                $lockedReservation->status === Reservation::STATUS_PENDING,
                409,
                'Only pending venue requests can be reviewed.',
            );

            $lockedReservation->update([
                'status' => $data['decision'],
                'review_feedback' => $data['feedback'] ?? null,
                'reviewer_id' => $request->user()->id,
                'reviewed_at' => now(),
            ]);
        });

        return redirect()->route('reservations.show', $reservation)->with('status', 'Venue request review recorded.');
    }
}
