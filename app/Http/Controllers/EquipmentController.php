<?php

namespace App\Http\Controllers;

use App\Models\Club;
use App\Models\Equipment;
use App\Models\Membership;
use App\Services\EquipmentService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EquipmentController extends Controller
{
    protected EquipmentService $equipmentService;

    public function __construct(EquipmentService $equipmentService)
    {
        $this->equipmentService = $equipmentService;
    }

    /**
     * Display equipment inventory.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Equipment::class);

        $user = $request->user();

        $status = $request->query('status', 'active');

        $query = Equipment::with('club')->latest();

        if ($user->isUniversityAdmin()) {

            // Administrators can view all equipment.

        } elseif ($user->isStudent()) {

            $clubIds = $user->memberships()
                ->where('status', Membership::STATUS_ACTIVE)
                ->pluck('club_id');

            $query->whereIn('club_id', $clubIds)
                ->where('is_active', true);

        } else {

            $clubIds = $user->clubRoles()
                ->where('role', 'officer')
                ->pluck('club_id');

            $query->whereIn('club_id', $clubIds);
        }

        if (!$user->isStudent()) {

            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            } else {
                $status = 'all';
            }

        } else {
            $status = 'active';
        }

        $equipments = $query->paginate(15)->withQueryString();

        return view('equipment.index', compact(
            'equipments',
            'status'
        ));
    }

    /**
     * Display equipment creation form.
     */
    public function create(Request $request): View
    {
        $user = $request->user();

        if ($user->isUniversityAdmin()) {

            $clubs = Club::orderBy('name')->get();

        } else {

            $clubIds = $user->clubRoles()
                ->where('role', 'officer')
                ->pluck('club_id');

            $clubs = Club::whereIn('id', $clubIds)
                ->orderBy('name')
                ->get();
        }

        abort_if(
            $clubs->isEmpty(),
            403,
            'You are not authorized to add equipment.'
        );

        return view('equipment.create', compact('clubs'));
    }

    /**
     * Store new equipment.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'club_id' => ['required', 'integer', 'exists:clubs,id'],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'quantity' => ['required', 'integer', 'min:1'],
            'item_condition' => ['required', 'string', 'max:50'],
        ]);

        $club = Club::findOrFail($validated['club_id']);

        $this->authorize('create', [Equipment::class, $club]);

        $equipment = $this->equipmentService->create(
            $club,
            $validated
        );

        return redirect()
            ->route('equipment.show', $equipment)
            ->with('success', 'Equipment added successfully.');
    }

    /**
     * Display individual equipment details.
     */
    public function show(Equipment $equipment): View
    {
        $this->authorize('view', $equipment);

        $equipment->load('club');

        return view('equipment.show', compact('equipment'));
    }

    /**
     * Display equipment editing form.
     */
    public function edit(Equipment $equipment): View
    {
        $this->authorize('update', $equipment);

        return view('equipment.edit', compact('equipment'));
    }

    /**
     * Update equipment information.
     */
    public function update(
        Request $request,
        Equipment $equipment
    ): RedirectResponse {

        $this->authorize('update', $equipment);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'quantity' => ['required', 'integer', 'min:1'],
            'item_condition' => ['required', 'string', 'max:50'],
        ]);

        $this->equipmentService->update(
            $equipment,
            $validated
        );

        return redirect()
            ->route('equipment.show', $equipment)
            ->with('success', 'Equipment updated successfully.');
    }

    /**
     * Deactivate equipment.
     */
    public function deactivate(Equipment $equipment): RedirectResponse
    {
        $this->authorize('deactivate', $equipment);

        $this->equipmentService->deactivate($equipment);

        return redirect()
            ->route('equipment.index')
            ->with('success', 'Equipment deactivated successfully.');
    }

    /**
     * Reactivate equipment.
     */
    public function activate(Equipment $equipment): RedirectResponse
    {
        $this->authorize('activate', $equipment);

        $this->equipmentService->activate($equipment);

        return redirect()
            ->route('equipment.index', ['status' => 'all'])
            ->with('success', 'Equipment reactivated successfully.');
    }
}
