<?php

namespace App\Http\Controllers;

use App\Models\ServiceEntry;
use App\Models\User;
use App\Models\VolunteerApplication;
use App\Models\VolunteerOpportunity;
use App\Services\ServiceHoursService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ServiceHoursController extends Controller
{
    public function __construct(private ServiceHoursService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', ServiceEntry::class);
        $user = auth()->user();

        $entries = $this->visibleEntries($user)
            ->with(['user', 'opportunity.event'])
            ->latest('worked_on')
            ->latest('id')
            ->paginate(15);

        $isMember = ! $user->isAdmin() && ! $user->isOrganizer();

        // Members see their own totals; organizers/admins see a total per volunteer.
        $summary = $isMember
            ? [
                'approved' => $this->service->approvedHours($user),
                'pending' => $this->service->pendingHours($user),
            ]
            : null;

        $totals = $isMember
            ? collect()
            : $this->visibleEntries($user)
                ->selectRaw('user_id, SUM(hours) as total_hours')
                ->where('status', ServiceEntry::STATUS_APPROVED)
                ->groupBy('user_id')
                ->orderByDesc('total_hours')
                ->with('user')
                ->get();

        return view('service-hours.index', [
            'entries' => $entries,
            'summary' => $summary,
            'totals' => $totals,
            'isMember' => $isMember,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', ServiceEntry::class);

        $opportunities = VolunteerOpportunity::query()
            ->with('event')
            ->whereHas('applications', fn ($q) => $q
                ->where('user_id', auth()->id())
                ->where('status', VolunteerApplication::STATUS_ACCEPTED))
            ->orderBy('title')
            ->get();

        return view('service-hours.create', compact('opportunities'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', ServiceEntry::class);

        $data = $request->validate([
            'volunteer_opportunity_id' => ['required', 'integer', 'exists:volunteer_opportunities,id'],
            'hours' => ['required', 'numeric', 'min:0.25', 'max:24'],
            // 'tomorrow' (not 'today') because the app runs in UTC: late at night in Dhaka the local date is already one day ahead.
            'worked_on' => ['required', 'date', 'before_or_equal:tomorrow'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $opportunity = VolunteerOpportunity::query()->findOrFail($data['volunteer_opportunity_id']);
        $this->service->log($request->user(), $opportunity, $data);

        return redirect()->route('service-hours.index')->with('status', 'Hours submitted for review.');
    }

    public function review(Request $request, ServiceEntry $entry): RedirectResponse
    {
        Gate::authorize('review', $entry);

        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->service->review($entry, $request->user(), $data['decision'], $data['review_note'] ?? null);

        return redirect()->route('service-hours.index')->with('status', 'Hours '.$data['decision'].'.');
    }

    /** Admin: everything. Organizer: hours for their own events. Member: only their own. */
    private function visibleEntries(User $user): Builder
    {
        $query = ServiceEntry::query();

        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->isOrganizer()) {
            return $query->whereHas('opportunity.event', fn ($q) => $q->where('organizer_id', $user->id));
        }

        return $query->where('user_id', $user->id);
    }
}
