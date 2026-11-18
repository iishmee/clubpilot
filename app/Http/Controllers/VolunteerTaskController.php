<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VolunteerApplication;
use App\Models\VolunteerOpportunity;
use App\Models\VolunteerTask;
use App\Services\VolunteerTaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VolunteerTaskController extends Controller
{
    public function __construct(private VolunteerTaskService $service) {}

    public function index(VolunteerOpportunity $opportunity): View
    {
        Gate::authorize('viewAny', [VolunteerTask::class, $opportunity]);
        $opportunity->load('event');

        $tasks = VolunteerTask::query()
            ->where('volunteer_opportunity_id', $opportunity->id)
            ->with('assignee')
            ->latest()
            ->get();

        $volunteers = User::query()
            ->whereIn('id', VolunteerApplication::query()
                ->where('volunteer_opportunity_id', $opportunity->id)
                ->where('status', VolunteerApplication::STATUS_ACCEPTED)
                ->select('user_id'))
            ->orderBy('name')
            ->get();

        return view('tasks.index', [
            'opportunity' => $opportunity,
            'tasks' => $tasks,
            'volunteers' => $volunteers,
            'canManage' => Gate::allows('create', [VolunteerTask::class, $opportunity]),
        ]);
    }

    public function store(Request $request, VolunteerOpportunity $opportunity): RedirectResponse
    {
        Gate::authorize('create', [VolunteerTask::class, $opportunity]);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'due_at' => ['nullable', 'date'],
        ]);

        $this->service->create($opportunity, $data);

        return redirect()->route('tasks.index', $opportunity)->with('status', 'Task created.');
    }

    public function assign(Request $request, VolunteerTask $task): RedirectResponse
    {
        Gate::authorize('manage', $task);

        $data = $request->validate(['assigned_to' => ['nullable', 'integer', 'exists:users,id']]);
        $this->service->assign($task, isset($data['assigned_to']) ? (int) $data['assigned_to'] : null);

        return redirect()->route('tasks.index', $task->volunteer_opportunity_id)->with('status', 'Task assignment updated.');
    }

    public function updateStatus(Request $request, VolunteerTask $task): RedirectResponse
    {
        Gate::authorize('updateStatus', $task);

        $data = $request->validate(['status' => ['required', Rule::in(VolunteerTask::STATUSES)]]);
        $this->service->updateStatus($task, $data['status']);

        return back()->with('status', 'Task status updated.');
    }

    public function destroy(VolunteerTask $task): RedirectResponse
    {
        Gate::authorize('manage', $task);

        $this->service->delete($task);

        return redirect()->route('tasks.index', $task->volunteer_opportunity_id)->with('status', 'Task deleted.');
    }

    /** A volunteer's own tasks. */
    public function mine(): View
    {
        abort_unless(auth()->user()->role === 'member', 403);

        $tasks = VolunteerTask::query()
            ->where('assigned_to', auth()->id())
            ->with('opportunity.event')
            ->latest()
            ->get();

        return view('tasks.mine', compact('tasks'));
    }
}
