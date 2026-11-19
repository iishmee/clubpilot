<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\Loan;
use App\Models\Membership;
use App\Models\User;
use App\Services\LoanService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Arr;

class LoanController extends Controller
{
    public function __construct(
        private LoanService $loanService
    ) {
    }

    /**
     * Get the authenticated user.
     */
    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        return $user;
    }

    /**
     * Display loans based on user permissions.
     */
    public function index(Request $request): View
    {
        $user = $this->authenticatedUser($request);

        $this->authorize('viewAny', Loan::class);

        $query = Loan::query()
            ->with([
                'equipment.club',
                'membership.user',
                'membership.club',
                'approvedBy',
                'issuedBy',
                'returnedTo',
                'rejectedBy',
            ])
            ->latest();

        // Students can see their own loans only.
        if ($user->isStudent()) {

            $query->whereHas('membership', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });

        } elseif (!$user->isUniversityAdmin()) {

            // Club officers can see loans from assigned clubs only.
            $clubIds = $user->clubRoles()
                ->where('role', 'officer')
                ->pluck('club_id');

            $query->whereHas('equipment', function ($q) use ($clubIds) {
                $q->whereIn('club_id', $clubIds);
            });
        }

        $statuses = [
            Loan::STATUS_PENDING,
            Loan::STATUS_APPROVED,
            Loan::STATUS_REJECTED,
            Loan::STATUS_ISSUED,
            Loan::STATUS_RETURNED,
            Loan::STATUS_OVERDUE,
            Loan::STATUS_CANCELLED,
        ];

        $status = $request->query('status', 'all');

        if (in_array($status, $statuses, true)) {
            $query->where('status', $status);
        } else {
            $status = 'all';
        }

        $loans = $query->paginate(15)->withQueryString();

        return view('loans.index', compact(
            'loans',
            'statuses',
            'status'
        ));
    }

    /**
     * Show the equipment borrowing form.
     */
    public function create(Request $request): View
    {
        $user = $this->authenticatedUser($request);

        // Only students can request equipment.
        abort_unless($user->isStudent(), 403);

        $memberships = Membership::query()
            ->with('club')
            ->where('user_id', $user->id)
            ->where('status', Membership::STATUS_ACTIVE)
            ->orderBy('club_id')
            ->get();

        $clubIds = $memberships->pluck('club_id');

        $equipments = Equipment::query()
            ->with('club')
            ->whereIn('club_id', $clubIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('loans.create', compact(
            'memberships',
            'equipments'
        ));
    }

    /**
     * Submit a new equipment loan request.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $this->authenticatedUser($request);

        $validated = $request->validate([
            'equipment_id' => [
                'required',
                'integer',
                'exists:equipment,id',
            ],

            'membership_id' => [
                'required',
                'integer',
                'exists:memberships,id',
            ],

            'requested_quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            'starts_at' => [
                'required',
                'date',
                'after_or_equal:now',
            ],

            'ends_at' => [
                'required',
                'date',
                'after:starts_at',
            ],

            'request_note' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $equipment = Equipment::findOrFail(
            $validated['equipment_id']
        );

        $membership = Membership::findOrFail(
            $validated['membership_id']
        );

        // Check student, membership and club permissions.
        $this->authorize('create', [
            Loan::class,
            $equipment,
            $membership,
        ]);

        $loan = $this->loanService->requestLoan(
            $equipment,
            $membership,
            $user,
            Arr::only($validated, [
                'requested_quantity',
                'starts_at',
                'ends_at',
                'request_note',
            ])
        );

        return redirect()
            ->route('loans.show', $loan)
            ->with('success', 'Equipment loan request submitted successfully.');
    }

    /**
     * Display a particular loan.
     */
    public function show(Loan $loan): View
    {
        $this->authorize('view', $loan);

        $loan->load([
            'equipment.club',
            'membership.user',
            'membership.club',
            'approvedBy',
            'issuedBy',
            'returnedTo',
            'rejectedBy',
        ]);

        return view('loans.show', compact('loan'));
    }

    /**
     * Approve a pending loan request.
     */
    public function approve(
        Request $request,
        Loan $loan
    ): RedirectResponse {

        $user = $this->authenticatedUser($request);

        $validated = $request->validate([
            'decision_note' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $this->authorize('approve', $loan);

        $this->loanService->approve(
            $loan,
            $user,
            $validated['decision_note'] ?? null
        );

        return redirect()
            ->route('loans.show', $loan)
            ->with('success', 'Loan request approved successfully.');
    }

    /**
     * Reject a pending loan request.
     */
    public function reject(
        Request $request,
        Loan $loan
    ): RedirectResponse {

        $user = $this->authenticatedUser($request);

        $validated = $request->validate([
            'decision_note' => [
                'required',
                'string',
                'max:2000',
            ],
        ]);

        $this->authorize('reject', $loan);

        $this->loanService->reject(
            $loan,
            $user,
            $validated['decision_note']
        );

        return redirect()
            ->route('loans.show', $loan)
            ->with('success', 'Loan request rejected.');
    }

    /**
     * Issue approved equipment to the student.
     */
    public function issue(
        Request $request,
        Loan $loan
    ): RedirectResponse {

        $user = $this->authenticatedUser($request);

        $this->authorize('issue', $loan);

        $this->loanService->issue(
            $loan,
            $user
        );

        return redirect()
            ->route('loans.show', $loan)
            ->with('success', 'Equipment issued successfully.');
    }

    /**
     * Record equipment return.
     */
    public function returnEquipment(
        Request $request,
        Loan $loan
    ): RedirectResponse {

        $user = $this->authenticatedUser($request);

        $this->authorize('returnEquipment', $loan);

        $this->loanService->returnEquipment(
            $loan,
            $user
        );

        return redirect()
            ->route('loans.show', $loan)
            ->with('success', 'Equipment return recorded successfully.');
    }

    /**
     * Cancel a loan request.
     */
    public function cancel(
        Request $request,
        Loan $loan
    ): RedirectResponse {

        $user = $this->authenticatedUser($request);

        $validated = $request->validate([
            'decision_note' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $this->authorize('cancel', $loan);

        $this->loanService->cancel(
            $loan,
            $user,
            $validated['decision_note'] ?? null
        );

        return redirect()
            ->route('loans.show', $loan)
            ->with('success', 'Loan request cancelled successfully.');
    }
}
