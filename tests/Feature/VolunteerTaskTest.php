<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Models\VolunteerApplication;
use App\Models\VolunteerOpportunity;
use App\Models\VolunteerTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VolunteerTaskTest extends TestCase
{
    use RefreshDatabase;

    private function opportunityFor(User $organizer): VolunteerOpportunity
    {
        $event = Event::query()->create([
            'organizer_id' => $organizer->id,
            'club_name' => 'Tech Club',
            'title' => 'Hackathon',
            'description' => 'Desc',
            'objectives' => 'Obj',
            'starts_at' => '2027-03-10 08:00',
            'ends_at' => '2027-03-10 20:00',
            'proposal_status' => Event::STATUS_APPROVED,
        ]);

        return VolunteerOpportunity::query()->create([
            'event_id' => $event->id,
            'title' => 'Registration desk',
            'slots_needed' => 5,
            'status' => 'open',
        ]);
    }

    private function acceptedVolunteer(VolunteerOpportunity $opportunity): User
    {
        $user = User::factory()->create(['role' => 'member']);
        VolunteerApplication::query()->create([
            'volunteer_opportunity_id' => $opportunity->id,
            'user_id' => $user->id,
            'status' => 'accepted',
        ]);

        return $user;
    }

    private function task(VolunteerOpportunity $opportunity, ?User $assignee = null): VolunteerTask
    {
        return VolunteerTask::query()->create([
            'volunteer_opportunity_id' => $opportunity->id,
            'title' => 'Print name badges',
            'assigned_to' => $assignee?->id,
            'status' => 'todo',
        ]);
    }

    public function test_only_the_owning_organizer_can_create_tasks(): void
    {
        $owner = User::factory()->create(['role' => 'organizer']);
        $other = User::factory()->create(['role' => 'organizer']);
        $member = User::factory()->create(['role' => 'member']);
        $opportunity = $this->opportunityFor($owner);

        $this->actingAs($member)->post(route('tasks.store', $opportunity), ['title' => 'Badges'])->assertForbidden();
        $this->actingAs($other)->post(route('tasks.store', $opportunity), ['title' => 'Badges'])->assertForbidden();
        $this->assertDatabaseCount('volunteer_tasks', 0);

        $this->actingAs($owner)->post(route('tasks.store', $opportunity), ['title' => 'Badges'])->assertRedirect();
        $this->assertDatabaseCount('volunteer_tasks', 1);
    }

    public function test_task_title_is_required(): void
    {
        $owner = User::factory()->create(['role' => 'organizer']);
        $opportunity = $this->opportunityFor($owner);

        $this->actingAs($owner)->post(route('tasks.store', $opportunity), ['title' => ''])
            ->assertSessionHasErrors('title');
    }

    public function test_tasks_can_only_be_assigned_to_accepted_volunteers(): void
    {
        $owner = User::factory()->create(['role' => 'organizer']);
        $opportunity = $this->opportunityFor($owner);
        $accepted = $this->acceptedVolunteer($opportunity);
        $stranger = User::factory()->create(['role' => 'member']);

        $this->actingAs($owner)->post(route('tasks.store', $opportunity), [
            'title' => 'Badges', 'assigned_to' => $stranger->id,
        ])->assertSessionHasErrors('assigned_to');
        $this->assertDatabaseCount('volunteer_tasks', 0);

        $this->actingAs($owner)->post(route('tasks.store', $opportunity), [
            'title' => 'Badges', 'assigned_to' => $accepted->id,
        ])->assertRedirect();
        $this->assertDatabaseHas('volunteer_tasks', ['title' => 'Badges', 'assigned_to' => $accepted->id]);

        $task = VolunteerTask::query()->firstOrFail();
        $this->actingAs($owner)->post(route('tasks.assign', $task), ['assigned_to' => $stranger->id])
            ->assertSessionHasErrors('assigned_to');
    }

    public function test_assignee_can_update_status_but_others_cannot(): void
    {
        $owner = User::factory()->create(['role' => 'organizer']);
        $opportunity = $this->opportunityFor($owner);
        $assignee = $this->acceptedVolunteer($opportunity);
        $otherVolunteer = $this->acceptedVolunteer($opportunity);
        $task = $this->task($opportunity, $assignee);

        $this->actingAs($otherVolunteer)->post(route('tasks.status', $task), ['status' => 'done'])->assertForbidden();
        $this->actingAs($assignee)->post(route('tasks.status', $task), ['status' => 'finished'])
            ->assertSessionHasErrors('status');
        $this->actingAs($assignee)->post(route('tasks.status', $task), ['status' => 'in_progress'])->assertRedirect();

        $this->assertSame('in_progress', $task->fresh()->status);
    }

    public function test_member_cannot_reassign_or_delete_tasks(): void
    {
        $owner = User::factory()->create(['role' => 'organizer']);
        $opportunity = $this->opportunityFor($owner);
        $assignee = $this->acceptedVolunteer($opportunity);
        $task = $this->task($opportunity, $assignee);

        $this->actingAs($assignee)->post(route('tasks.assign', $task), ['assigned_to' => null])->assertForbidden();
        $this->actingAs($assignee)->delete(route('tasks.destroy', $task))->assertForbidden();

        $this->actingAs($owner)->delete(route('tasks.destroy', $task))->assertRedirect();
        $this->assertDatabaseCount('volunteer_tasks', 0);
    }

    public function test_volunteer_sees_only_their_own_tasks(): void
    {
        $owner = User::factory()->create(['role' => 'organizer']);
        $opportunity = $this->opportunityFor($owner);
        $mine = $this->acceptedVolunteer($opportunity);
        $other = $this->acceptedVolunteer($opportunity);
        $this->task($opportunity, $mine);
        VolunteerTask::query()->create([
            'volunteer_opportunity_id' => $opportunity->id,
            'title' => 'Set up projector',
            'assigned_to' => $other->id,
            'status' => 'todo',
        ]);

        $this->actingAs($mine)->get(route('tasks.mine'))
            ->assertOk()
            ->assertSee('Print name badges')
            ->assertDontSee('Set up projector');
    }
}
