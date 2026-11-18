<?php

namespace App\Services;

use App\Models\VolunteerApplication;
use App\Models\VolunteerOpportunity;
use App\Models\VolunteerTask;
use Illuminate\Validation\ValidationException;

class VolunteerTaskService
{
    /**
     * @param  array{title: string, description?: ?string, assigned_to?: ?int, due_at?: ?string}  $data
     */
    public function create(VolunteerOpportunity $opportunity, array $data): VolunteerTask
    {
        $assignee = $data['assigned_to'] ?? null;
        $this->ensureAcceptedVolunteer($opportunity, $assignee);

        return VolunteerTask::query()->create([
            'volunteer_opportunity_id' => $opportunity->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'assigned_to' => $assignee,
            'due_at' => $data['due_at'] ?? null,
            'status' => VolunteerTask::STATUS_TODO,
        ]);
    }

    public function assign(VolunteerTask $task, ?int $userId): VolunteerTask
    {
        $this->ensureAcceptedVolunteer($task->opportunity, $userId);
        $task->update(['assigned_to' => $userId]);

        return $task;
    }

    public function updateStatus(VolunteerTask $task, string $status): VolunteerTask
    {
        $task->update(['status' => $status]);

        return $task;
    }

    public function delete(VolunteerTask $task): void
    {
        $task->delete();
    }

    /** A task can only go to a volunteer who was accepted for this opportunity (or to nobody). */
    private function ensureAcceptedVolunteer(VolunteerOpportunity $opportunity, ?int $userId): void
    {
        if ($userId === null) {
            return;
        }

        $isAccepted = VolunteerApplication::query()
            ->where('volunteer_opportunity_id', $opportunity->id)
            ->where('user_id', $userId)
            ->where('status', VolunteerApplication::STATUS_ACCEPTED)
            ->exists();

        if (! $isAccepted) {
            throw ValidationException::withMessages([
                'assigned_to' => 'Tasks can only be assigned to accepted volunteers of this opportunity.',
            ]);
        }
    }
}
