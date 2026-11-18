<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\User;
use App\Models\VolunteerApplication;
use App\Models\VolunteerOpportunity;
use App\Services\ShiftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ShiftController extends Controller
{
    public function __construct(private ShiftService $service) {}

    public function index(VolunteerOpportunity $opportunity): View
    {
        Gate::authorize('viewAny', [Shift::class, $opportunity]);
        $opportunity->load('event');

        $shifts = Shift::query()
            ->where('volunteer_opportunity_id', $opportunity->id)
            ->with('assignments.user')
            ->orderBy('starts_at')
            ->get();

        $volunteers = User::query()
            ->whereIn('id', VolunteerApplication::query()
                ->where('volunteer_opportunity_id', $opportunity->id)
                ->where('status', VolunteerApplication::STATUS_ACCEPTED)
                ->select('user_id'))
            ->orderBy('name')
            ->get();

        return view('shifts.index', [
            'opportunity' => $opportunity,
            'shifts' => $shifts,
            'volunteers' => $volunteers,
            'canManage' => Gate::allows('create', [Shift::class, $opportunity]),
        ]);
    }

    public function store(Request $request, VolunteerOpportunity $opportunity): RedirectResponse
    {
        Gate::authorize('create', [Shift::class, $opportunity]);

        $data = $request->validate([
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'capacity' => ['required', 'integer', 'min:1', 'max:500'],
        ]);

        $this->service->createShift($opportunity, $data);

        return redirect()->route('shifts.index', $opportunity)->with('status', 'Shift created.');
    }

    public function assign(Request $request, Shift $shift): RedirectResponse
    {
        Gate::authorize('manage', $shift);

        $data = $request->validate(['user_id' => ['required', 'integer', 'exists:users,id']]);
        $this->service->assign($shift, (int) $data['user_id']);

        return redirect()->route('shifts.index', $shift->volunteer_opportunity_id)->with('status', 'Volunteer assigned to shift.');
    }

    public function unassign(ShiftAssignment $assignment): RedirectResponse
    {
        $shift = $assignment->shift;
        Gate::authorize('manage', $shift);

        $this->service->unassign($assignment);

        return redirect()->route('shifts.index', $shift->volunteer_opportunity_id)->with('status', 'Volunteer removed from shift.');
    }

    public function destroy(Shift $shift): RedirectResponse
    {
        Gate::authorize('manage', $shift);

        $this->service->deleteShift($shift);

        return redirect()->route('shifts.index', $shift->volunteer_opportunity_id)->with('status', 'Shift deleted.');
    }

    /** A volunteer's own schedule. */
    public function mine(): View
    {
        abort_unless(auth()->user()->role === 'member', 403);

        $assignments = ShiftAssignment::query()
            ->where('user_id', auth()->id())
            ->with('shift.opportunity.event')
            ->get()
            ->sortBy(fn ($assignment) => $assignment->shift->starts_at);

        return view('shifts.mine', compact('assignments'));
    }
}
