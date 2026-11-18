<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Models\VolunteerApplication;
use App\Models\VolunteerOpportunity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VolunteerRecruitmentTest extends TestCase
{
    use RefreshDatabase;

    private function event(User $organizer, string $status = Event::STATUS_APPROVED): Event
    {
        return Event::query()->create([
            'organizer_id' => $organizer->id,
            'club_name' => 'Tech Club',
            'title' => 'Hackathon',
            'description' => 'Desc',
            'objectives' => 'Obj',
            'starts_at' => '2027-03-10 10:00',
            'ends_at' => '2027-03-10 12:00',
            'proposal_status' => $status,
        ]);
    }

    private function opportunity(Event $event, int $slots = 1): VolunteerOpportunity
    {
        return VolunteerOpportunity::query()->create([
            'event_id' => $event->id,
            'title' => 'Registration desk',
            'slots_needed' => $slots,
            'status' => 'open',
        ]);
    }

    public function test_organizer_can_create_opportunity_only_for_approved_own_event(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);
        $approved = $this->event($organizer);
        $pending = $this->event($organizer, Event::STATUS_PENDING_REVIEW);

        $this->actingAs($organizer)->post(route('volunteers.store', $approved), [
            'title' => 'Ushers', 'slots_needed' => 3,
        ])->assertRedirect();
        $this->assertDatabaseCount('volunteer_opportunities', 1);

        $this->actingAs($organizer)->post(route('volunteers.store', $pending), [
            'title' => 'Ushers', 'slots_needed' => 3,
        ])->assertForbidden();
        $this->assertDatabaseCount('volunteer_opportunities', 1);
    }

    public function test_member_cannot_create_opportunity(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($member)->get(route('volunteers.create', $this->event($organizer)))->assertForbidden();
    }

    public function test_member_can_apply_once(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);
        $member = User::factory()->create(['role' => 'member']);
        $opportunity = $this->opportunity($this->event($organizer));

        $this->actingAs($member)->post(route('volunteers.apply', $opportunity), ['message' => 'Hi'])->assertRedirect();
        $this->actingAs($member)->post(route('volunteers.apply', $opportunity), ['message' => 'Again'])
            ->assertSessionHasErrors('message');

        $this->assertDatabaseCount('volunteer_applications', 1);
    }

    public function test_cannot_apply_to_closed_opportunity(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);
        $member = User::factory()->create(['role' => 'member']);
        $opportunity = $this->opportunity($this->event($organizer));
        $opportunity->update(['status' => 'closed']);

        $this->actingAs($member)->post(route('volunteers.apply', $opportunity))->assertForbidden();
    }

    public function test_organizer_cannot_accept_more_than_slots(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);
        $opportunity = $this->opportunity($this->event($organizer), 1);

        $apps = collect([1, 2])->map(fn () => VolunteerApplication::query()->create([
            'volunteer_opportunity_id' => $opportunity->id,
            'user_id' => User::factory()->create(['role' => 'member'])->id,
            'status' => 'pending',
        ]));

        $this->actingAs($organizer)->post(route('volunteers.applications.review', $apps[0]), ['decision' => 'accepted'])->assertRedirect();
        $this->actingAs($organizer)->post(route('volunteers.applications.review', $apps[1]), ['decision' => 'accepted'])
            ->assertSessionHasErrors('decision');

        $this->assertSame('accepted', $apps[0]->fresh()->status);
        $this->assertSame('pending', $apps[1]->fresh()->status);
    }

    public function test_other_organizer_and_member_cannot_review(): void
    {
        $owner = User::factory()->create(['role' => 'organizer']);
        $otherOrganizer = User::factory()->create(['role' => 'organizer']);
        $member = User::factory()->create(['role' => 'member']);
        $opportunity = $this->opportunity($this->event($owner));
        $application = VolunteerApplication::query()->create([
            'volunteer_opportunity_id' => $opportunity->id,
            'user_id' => $member->id,
            'status' => 'pending',
        ]);

        $this->actingAs($otherOrganizer)->post(route('volunteers.applications.review', $application), ['decision' => 'accepted'])->assertForbidden();
        $this->actingAs($member)->post(route('volunteers.applications.review', $application), ['decision' => 'accepted'])->assertForbidden();
    }
}
