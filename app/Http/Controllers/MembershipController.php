<?php

namespace App\Http\Controllers;

use App\Models\Club;
use App\Models\Membership;
use App\Models\User;
use App\Services\MembershipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MembershipController extends Controller
{
    public function __construct(
        private MembershipService $membershipService
    ) {
    }

    private function authenticatedUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 401);

        return $user;
    }

    public function index(Request $request)
    {
        $actor = $this->authenticatedUser();

        // Students cannot manage memberships.
        if ($actor->isStudent()) {
            return redirect()->route('memberships.create');
        }

        $this->authorize('viewAny', Membership::class);

        $statuses = [
            Membership::STATUS_PENDING,
            Membership::STATUS_INVITED,
            Membership::STATUS_ACTIVE,
            Membership::STATUS_INACTIVE,
            Membership::STATUS_GRADUATED,
            Membership::STATUS_REJECTED,
        ];

        $status = $request->query('status', 'all');

        $query = Membership::with([
            'user',
            'club',
            'invitedBy',
        ])->latest();

        if (in_array($status, $statuses, true)) {
            $query->where('status', $status);
        } else {
            $status = 'all';
        }

        $memberships = $query->paginate(15)->withQueryString();

        // Membership statistics.
        $statistics = [
            'total' => Membership::count(),

            'pending' => Membership::where(
                'status',
                Membership::STATUS_PENDING
            )->count(),

            'invited' => Membership::where(
                'status',
                Membership::STATUS_INVITED
            )->count(),

            'active' => Membership::where(
                'status',
                Membership::STATUS_ACTIVE
            )->count(),

            'inactive' => Membership::where(
                'status',
                Membership::STATUS_INACTIVE
            )->count(),

            'graduated' => Membership::where(
                'status',
                Membership::STATUS_GRADUATED
            )->count(),

            'rejected' => Membership::where(
                'status',
                Membership::STATUS_REJECTED
            )->count(),
        ];

        // Compatibility with existing Blade variables.
        $stats = $statistics;

        // All recognised clubs are available to the university administrator.
        $managedClubs = Club::query()
            ->orderBy('name')
            ->get();

        // Keep the existing $clubs variable too.
        $clubs = $managedClubs;

        $students = User::query()
            ->where('system_role', User::ROLE_STUDENT)
            ->orderBy('name')
            ->get();

        return view('memberships.index', compact(
            'memberships',
            'statistics',
            'stats',
            'status',
            'clubs',
            'managedClubs',
            'students'
        ));
    }

    public function create()
    {
        $actor = $this->authenticatedUser();

        if ($actor->isUniversityAdmin()) {
            return redirect()->route('memberships.index');
        }

        $this->authorize('create', Membership::class);

        $clubs = Club::orderBy('name')->get();

        return view('memberships.create', compact('clubs'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Membership::class);

        $validated = $request->validate([
            'club_id' => 'required|integer|exists:clubs,id',
            'student_id' => 'required|string|max:50',
            'department' => 'required|string|max:150',
            'semester' => 'required|string|max:50',
            'application_message' => 'nullable|string|max:2000',
        ]);

        $this->membershipService->submitApplication(
            $this->authenticatedUser(),
            $validated
        );

        return redirect()
            ->route('memberships.create')
            ->with(
                'success',
                'Your membership application has been submitted successfully.'
            );
    }

    public function show(Membership $membership)
    {
        $this->authorize('view', $membership);

        $membership->load([
            'user',
            'club',
            'invitedBy',
        ]);

        return view('memberships.show', compact('membership'));
    }

    public function invite(Request $request, Club $club)
    {
        $this->authorize('invite', [Membership::class, $club]);

        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $student = User::where(
            'system_role',
            User::ROLE_STUDENT
        )->findOrFail($validated['user_id']);

        $this->membershipService->inviteStudent(
            $club,
            $student,
            $this->authenticatedUser()
        );

        return redirect()
            ->route('memberships.index')
            ->with('success', 'Student invitation created successfully.');
    }

    public function acceptInvitation(Request $request, Membership $membership)
    {
        $this->authorize('acceptInvitation', $membership);

        $this->membershipService->changeStatus(
            $membership,
            $this->authenticatedUser(),
            Membership::STATUS_ACTIVE,
            'Invitation accepted.'
        );

        return redirect()
            ->route('memberships.create')
            ->with('success', 'Your club invitation has been accepted.');
    }

    public function approve(Request $request, Membership $membership)
    {
        $this->authorize('approve', $membership);

        $this->membershipService->changeStatus(
            $membership,
            $this->authenticatedUser(),
            Membership::STATUS_ACTIVE,
            'Membership application approved.'
        );

        return redirect()
            ->route('memberships.show', $membership)
            ->with('success', 'Membership application approved successfully.');
    }

    public function reject(Request $request, Membership $membership)
    {
        $this->authorize('reject', $membership);

        $validated = $request->validate([
            'reason' => 'required|string|max:2000',
        ]);

        $this->membershipService->changeStatus(
            $membership,
            $this->authenticatedUser(),
            Membership::STATUS_REJECTED,
            $validated['reason']
        );

        return redirect()
            ->route('memberships.show', $membership)
            ->with('success', 'Membership application rejected.');
    }

    public function deactivate(Request $request, Membership $membership)
    {
        $this->authorize('deactivate', $membership);

        $this->membershipService->changeStatus(
            $membership,
            $this->authenticatedUser(),
            Membership::STATUS_INACTIVE,
            'Membership deactivated by administrator.'
        );

        return redirect()
            ->route('memberships.show', $membership)
            ->with('success', 'Membership deactivated successfully.');
    }

    public function graduate(Request $request, Membership $membership)
    {
        $this->authorize('graduate', $membership);

        $this->membershipService->changeStatus(
            $membership,
            $this->authenticatedUser(),
            Membership::STATUS_GRADUATED,
            'Member marked as graduated.'
        );

        return redirect()
            ->route('memberships.show', $membership)
            ->with('success', 'Member marked as graduated.');
    }

    public function reactivate(Request $request, Membership $membership)
    {
        $this->authorize('reactivate', $membership);

        $this->membershipService->changeStatus(
            $membership,
            $this->authenticatedUser(),
            Membership::STATUS_ACTIVE,
            'Membership reactivated by administrator.'
        );

        return redirect()
            ->route('memberships.show', $membership)
            ->with('success', 'Membership reactivated successfully.');
    }
}
