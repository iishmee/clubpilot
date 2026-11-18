<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\VolunteerApplication;
use App\Models\VolunteerOpportunity;
use App\Services\VolunteerRecruitmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class VolunteerRecruitmentController extends Controller
{
    public function __construct(private VolunteerRecruitmentService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', VolunteerOpportunity::class);
        $user = auth()->user();

        $query = VolunteerOpportunity::query()
            ->with('event')
            ->withCount(['applications as accepted_count' => fn ($q) => $q->where('status', VolunteerApplication::STATUS_ACCEPTED)])
            ->latest();

        if ($user->isOrganizer()) {
            $query->whereHas('event', fn ($q) => $q->where('organizer_id', $user->id));
        } elseif (! $user->isAdmin()) {
            $query->where('status', VolunteerOpportunity::STATUS_OPEN);
        }

        $events = $user->isOrganizer()
            ? Event::query()
                ->where('organizer_id', $user->id)
                ->where('proposal_status', Event::STATUS_APPROVED)
                ->orderBy('title')
                ->get()
            : collect();

        return view('volunteers.index', [
            'opportunities' => $query->paginate(15),
            'events' => $events,
        ]);
    }

    public function create(Event $event): View
    {
        Gate::authorize('create', [VolunteerOpportunity::class, $event]);

        return view('volunteers.create', compact('event'));
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('create', [VolunteerOpportunity::class, $event]);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'slots_needed' => ['required', 'integer', 'min:1', 'max:500'],
        ]);

        $opportunity = $this->service->createOpportunity($event, $request->user(), $data);

        return redirect()->route('volunteers.show', $opportunity)->with('status', 'Volunteer opportunity created.');
    }

    public function show(VolunteerOpportunity $opportunity): View
    {
        Gate::authorize('view', $opportunity);
        $opportunity->load('event');

        $applications = Gate::allows('manage', $opportunity)
            ? $opportunity->applications()->with('user')->latest()->get()
            : collect();

        return view('volunteers.show', [
            'opportunity' => $opportunity,
            'applications' => $applications,
            'acceptedCount' => $opportunity->acceptedCount(),
            'myApplication' => $opportunity->applications()->where('user_id', auth()->id())->first(),
        ]);
    }

    public function apply(Request $request, VolunteerOpportunity $opportunity): RedirectResponse
    {
        Gate::authorize('apply', $opportunity);

        $data = $request->validate(['message' => ['nullable', 'string', 'max:2000']]);
        $this->service->apply($opportunity, $request->user(), $data['message'] ?? null);

        return redirect()->route('volunteers.show', $opportunity)->with('status', 'Application submitted.');
    }

    public function reviewApplication(Request $request, VolunteerApplication $application): RedirectResponse
    {
        $opportunity = $application->opportunity;
        Gate::authorize('manage', $opportunity);

        $data = $request->validate(['decision' => ['required', 'in:accepted,rejected']]);
        $this->service->review($application, $data['decision']);

        return redirect()->route('volunteers.show', $opportunity)->with('status', 'Application '.$data['decision'].'.');
    }

    public function close(VolunteerOpportunity $opportunity): RedirectResponse
    {
        Gate::authorize('manage', $opportunity);
        $this->service->close($opportunity);

        return redirect()->route('volunteers.show', $opportunity)->with('status', 'Recruitment closed.');
    }
}
