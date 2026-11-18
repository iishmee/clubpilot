<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\ServiceEntry;
use App\Models\User;
use App\Models\VolunteerApplication;
use App\Models\VolunteerOpportunity;
use App\Services\ServiceHoursService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceHoursTest extends TestCase
{
    use RefreshDatabase;

    private function event(User $organizer): Event
    {
        return Event::query()->create([
            'organizer_id' => $organizer->id,
            'club_name' => 'Tech Club',
            'title' => 'Hackathon',
            'description' => 'Desc',
            'objectives' => 'Obj',
            'starts_at' => '2027-03-10 10:00',
            'ends_at' => '2027-03-10 12:00',
            'proposal_status' => Event::STATUS_APPROVED,
        ]);
    }

    private function opportunity(Event $event): VolunteerOpportunity
    {
        return VolunteerOpportunity::query()->create([
            'event_id' => $event->id,
            'title' => 'Registration desk',
            'slots_needed' => 5,
            'status' => 'open',
        ]);
    }

    private function application(VolunteerOpportunity $opportunity, User $member, string $status = 'accepted'): VolunteerApplication
    {
        return VolunteerApplication::query()->create([
            'volunteer_opportunity_id' => $opportunity->id,
            'user_id' => $member->id,
            'status' => $status,
        ]);
    }

    private function entry(VolunteerOpportunity $opportunity, User $member, float $hours = 2.0, string $status = 'pending', ?string $description = null): ServiceEntry
    {
        return ServiceEntry::query()->create([
            'user_id' => $member->id,
            'volunteer_opportunity_id' => $opportunity->id,
            'hours' => $hours,
            'worked_on' => now()->subDay()->toDateString(),
            'description' => $description,
            'status' => $status,
        ]);
    }

    public function test_accepted_volunteer_can_log_hours(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);
        $member = User::factory()->create(['role' => 'member']);
        $opportunity = $this->opportunity($this->event($organizer));
        $this->application($opportunity, $member);

        $this->actingAs($member)->post(route('service-hours.store'), [
            'volunteer_opportunity_id' => $opportunity->id,
            'hours' => 3.5,
            'worked_on' => now()->subDay()->toDateString(),
            'description' => 'Checked in guests',
        ])->assertRedirect(route('service-hours.index'));

        $this->assertDatabaseCount('service_entries', 1);
        $entry = ServiceEntry::query()->first();
        $this->assertSame($member->id, $entry->user_id);
        $this->assertSame('pending', $entry->status);
        $this->assertEquals(3.5, (float) $entry->hours);
    }

    public function test_member_who_was_not_accepted_cannot_log_hours(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);
        $member = User::factory()->create(['role' => 'member']);
        $stranger = User::factory()->create(['role' => 'member']);
        $opportunity = $this->opportunity($this->event($organizer));
        $this->application($opportunity, $member, 'pending');

        $payload = [
            'volunteer_opportunity_id' => $opportunity->id,
            'hours' => 2,
            'worked_on' => now()->subDay()->toDateString(),
        ];

        $this->actingAs($member)->post(route('service-hours.store'), $payload)->assertForbidden();
        $this->actingAs($stranger)->post(route('service-hours.store'), $payload)->assertForbidden();

        $this->assertDatabaseCount('service_entries', 0);
    }

    public function test_organizer_and_admin_cannot_log_hours(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $opportunity = $this->opportunity($this->event($organizer));

        $payload = [
            'volunteer_opportunity_id' => $opportunity->id,
            'hours' => 2,
            'worked_on' => now()->subDay()->toDateString(),
        ];

        $this->actingAs($organizer)->post(route('service-hours.store'), $payload)->assertForbidden();
        $this->actingAs($admin)->post(route('service-hours.store'), $payload)->assertForbidden();
        $this->actingAs($organizer)->get(route('service-hours.create'))->assertForbidden();
    }

    public function test_hours_and_date_are_validated(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);
        $member = User::factory()->create(['role' => 'member']);
        $opportunity = $this->opportunity($this->event($organizer));
        $this->application($opportunity, $member);

        $this->actingAs($member)->post(route('service-hours.store'), [
            'volunteer_opportunity_id' => $opportunity->id,
            'hours' => 0,
            'worked_on' => now()->subDay()->toDateString(),
        ])->assertSessionHasErrors('hours');

        $this->actingAs($member)->post(route('service-hours.store'), [
            'volunteer_opportunity_id' => $opportunity->id,
            'hours' => 25,
            'worked_on' => now()->subDay()->toDateString(),
        ])->assertSessionHasErrors('hours');

        $this->actingAs($member)->post(route('service-hours.store'), [
            'volunteer_opportunity_id' => $opportunity->id,
            'hours' => 2,
            'worked_on' => now()->addDays(5)->toDateString(),
        ])->assertSessionHasErrors('worked_on');

        $this->assertDatabaseCount('service_entries', 0);
    }

    public function test_organizer_can_approve_once_and_totals_count_only_approved_hours(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);
        $member = User::factory()->create(['role' => 'member']);
        $opportunity = $this->opportunity($this->event($organizer));
        $this->application($opportunity, $member);
        $first = $this->entry($opportunity, $member, 2.0);
        $second = $this->entry($opportunity, $member, 1.5);

        $this->actingAs($organizer)
            ->post(route('service-hours.review', $first), ['decision' => 'approved'])
            ->assertRedirect(route('service-hours.index'));

        $first->refresh();
        $this->assertSame('approved', $first->status);
        $this->assertSame($organizer->id, $first->reviewed_by);
        $this->assertNotNull($first->reviewed_at);

        // An entry that was already decided cannot be reviewed again.
        $this->actingAs($organizer)
            ->post(route('service-hours.review', $first), ['decision' => 'rejected', 'review_note' => 'Changed my mind'])
            ->assertStatus(409);
        $this->assertSame('approved', $first->fresh()->status);

        $this->assertEquals(2.0, app(ServiceHoursService::class)->approvedHours($member));
        $this->assertEquals(1.5, app(ServiceHoursService::class)->pendingHours($member));
        $this->assertSame('pending', $second->fresh()->status);
    }

    public function test_rejecting_hours_needs_a_reason(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);
        $member = User::factory()->create(['role' => 'member']);
        $opportunity = $this->opportunity($this->event($organizer));
        $entry = $this->entry($opportunity, $member);

        $this->actingAs($organizer)
            ->post(route('service-hours.review', $entry), ['decision' => 'rejected'])
            ->assertSessionHasErrors('review_note');
        $this->assertSame('pending', $entry->fresh()->status);

        $this->actingAs($organizer)
            ->post(route('service-hours.review', $entry), ['decision' => 'rejected', 'review_note' => 'Not on the roster'])
            ->assertRedirect();
        $this->assertSame('rejected', $entry->fresh()->status);
        $this->assertSame('Not on the roster', $entry->fresh()->review_note);
    }

    public function test_only_the_events_organizer_can_review(): void
    {
        $owner = User::factory()->create(['role' => 'organizer']);
        $otherOrganizer = User::factory()->create(['role' => 'organizer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);
        $opportunity = $this->opportunity($this->event($owner));
        $entry = $this->entry($opportunity, $member);

        $this->actingAs($otherOrganizer)->post(route('service-hours.review', $entry), ['decision' => 'approved'])->assertForbidden();
        $this->actingAs($admin)->post(route('service-hours.review', $entry), ['decision' => 'approved'])->assertForbidden();
        $this->actingAs($member)->post(route('service-hours.review', $entry), ['decision' => 'approved'])->assertForbidden();

        $this->assertSame('pending', $entry->fresh()->status);
    }

    public function test_each_role_sees_the_right_entries(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);
        $otherOrganizer = User::factory()->create(['role' => 'organizer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $memberA = User::factory()->create(['role' => 'member', 'name' => 'Alice Member']);
        $memberB = User::factory()->create(['role' => 'member', 'name' => 'Bob Member']);
        $opportunity = $this->opportunity($this->event($organizer));
        $otherOpportunity = $this->opportunity($this->event($otherOrganizer));

        $this->entry($opportunity, $memberA, 2.0, 'approved', 'ALICE-WORK');
        $this->entry($opportunity, $memberB, 1.0, 'pending', 'BOB-WORK');
        $this->entry($otherOpportunity, $memberB, 4.0, 'pending', 'ELSEWHERE-WORK');

        // A member only sees their own entries.
        $this->actingAs($memberA)->get(route('service-hours.index'))
            ->assertOk()
            ->assertSee('ALICE-WORK')
            ->assertDontSee('BOB-WORK')
            ->assertDontSee('ELSEWHERE-WORK');

        // An organizer sees hours logged for their own events only.
        $this->actingAs($organizer)->get(route('service-hours.index'))
            ->assertOk()
            ->assertSee('ALICE-WORK')
            ->assertSee('BOB-WORK')
            ->assertDontSee('ELSEWHERE-WORK');

        // An admin sees everything.
        $this->actingAs($admin)->get(route('service-hours.index'))
            ->assertOk()
            ->assertSee('ALICE-WORK')
            ->assertSee('BOB-WORK')
            ->assertSee('ELSEWHERE-WORK');
    }
}
