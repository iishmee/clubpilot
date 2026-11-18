<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\User;
use App\Models\VolunteerApplication;
use App\Models\VolunteerOpportunity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftSchedulingTest extends TestCase
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

    private function shift(VolunteerOpportunity $opportunity, string $start, string $end, int $capacity = 2): Shift
    {
        return Shift::query()->create([
            'volunteer_opportunity_id' => $opportunity->id,
            'starts_at' => $start,
            'ends_at' => $end,
            'capacity' => $capacity,
        ]);
    }

    public function test_only_the_owning_organizer_can_create_shifts(): void
    {
        $owner = User::factory()->create(['role' => 'organizer']);
        $other = User::factory()->create(['role' => 'organizer']);
        $member = User::factory()->create(['role' => 'member']);
        $opportunity = $this->opportunityFor($owner);
        $payload = ['starts_at' => '2027-03-10 10:00', 'ends_at' => '2027-03-10 12:00', 'capacity' => 2];

        $this->actingAs($member)->post(route('shifts.store', $opportunity), $payload)->assertForbidden();
        $this->actingAs($other)->post(route('shifts.store', $opportunity), $payload)->assertForbidden();
        $this->assertDatabaseCount('shifts', 0);

        $this->actingAs($owner)->post(route('shifts.store', $opportunity), $payload)->assertRedirect();
        $this->assertDatabaseCount('shifts', 1);
    }

    public function test_shift_must_end_after_it_starts(): void
    {
        $owner = User::factory()->create(['role' => 'organizer']);
        $opportunity = $this->opportunityFor($owner);

        $this->actingAs($owner)->post(route('shifts.store', $opportunity), [
            'starts_at' => '2027-03-10 12:00', 'ends_at' => '2027-03-10 10:00', 'capacity' => 2,
        ])->assertSessionHasErrors('ends_at');
    }

    public function test_only_accepted_volunteers_can_be_assigned(): void
    {
        $owner = User::factory()->create(['role' => 'organizer']);
        $opportunity = $this->opportunityFor($owner);
        $shift = $this->shift($opportunity, '2027-03-10 10:00', '2027-03-10 12:00');
        $stranger = User::factory()->create(['role' => 'member']);
        $accepted = $this->acceptedVolunteer($opportunity);

        $this->actingAs($owner)->post(route('shifts.assign', $shift), ['user_id' => $stranger->id])
            ->assertSessionHasErrors('user_id');
        $this->actingAs($owner)->post(route('shifts.assign', $shift), ['user_id' => $accepted->id])
            ->assertRedirect();

        $this->assertDatabaseCount('shift_assignments', 1);
    }

    public function test_shift_capacity_is_enforced(): void
    {
        $owner = User::factory()->create(['role' => 'organizer']);
        $opportunity = $this->opportunityFor($owner);
        $shift = $this->shift($opportunity, '2027-03-10 10:00', '2027-03-10 12:00', 1);
        $first = $this->acceptedVolunteer($opportunity);
        $second = $this->acceptedVolunteer($opportunity);

        $this->actingAs($owner)->post(route('shifts.assign', $shift), ['user_id' => $first->id])->assertRedirect();
        $this->actingAs($owner)->post(route('shifts.assign', $shift), ['user_id' => $second->id])
            ->assertSessionHasErrors('user_id');

        $this->assertDatabaseCount('shift_assignments', 1);
    }

    public function test_overlapping_shifts_are_blocked_but_adjacent_shifts_are_allowed(): void
    {
        $owner = User::factory()->create(['role' => 'organizer']);
        $opportunity = $this->opportunityFor($owner);
        $a = $this->shift($opportunity, '2027-03-10 10:00', '2027-03-10 12:00');
        $b = $this->shift($opportunity, '2027-03-10 11:00', '2027-03-10 13:00');
        $c = $this->shift($opportunity, '2027-03-10 12:00', '2027-03-10 14:00');
        $volunteer = $this->acceptedVolunteer($opportunity);

        $this->actingAs($owner)->post(route('shifts.assign', $a), ['user_id' => $volunteer->id])->assertRedirect();
        $this->actingAs($owner)->post(route('shifts.assign', $b), ['user_id' => $volunteer->id])
            ->assertSessionHasErrors('user_id');
        $this->actingAs($owner)->post(route('shifts.assign', $c), ['user_id' => $volunteer->id])->assertRedirect();

        $this->assertDatabaseCount('shift_assignments', 2);
    }

    public function test_unassigning_and_member_schedule(): void
    {
        $owner = User::factory()->create(['role' => 'organizer']);
        $opportunity = $this->opportunityFor($owner);
        $shift = $this->shift($opportunity, '2027-03-10 10:00', '2027-03-10 12:00');
        $volunteer = $this->acceptedVolunteer($opportunity);
        $assignment = ShiftAssignment::query()->create(['shift_id' => $shift->id, 'user_id' => $volunteer->id]);

        $this->actingAs($volunteer)->get(route('shifts.mine'))
            ->assertOk()
            ->assertSee('Registration desk')
            ->assertSee('Mar 10, 2027');

        $this->actingAs($volunteer)->delete(route('shifts.unassign', $assignment))->assertForbidden();

        $this->actingAs($owner)->delete(route('shifts.unassign', $assignment))->assertRedirect();
        $this->assertDatabaseCount('shift_assignments', 0);
    }
}
