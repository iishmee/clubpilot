<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventProposalTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_can_submit_a_proposal_and_admin_can_request_revisions_then_approve(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($organizer)->post(route('events.store'), [
            'club_name' => 'Robotics Club',
            'title' => 'Build Night',
            'description' => 'A club workshop.',
            'objectives' => 'Introduce members to robotics.',
            'requirements' => 'Workshop room and kits.',
            'starts_at' => '2027-03-10 10:00',
            'ends_at' => '2027-03-10 12:00',
            'organizer_id' => $admin->id,
            'proposal_status' => Event::STATUS_APPROVED,
            'review_feedback' => 'Forged feedback.',
        ])->assertRedirect();

        $event = Event::query()->firstOrFail();
        $this->assertSame(Event::STATUS_DRAFT, $event->proposal_status);
        $this->assertSame($organizer->id, $event->organizer_id);
        $this->assertNull($event->review_feedback);

        $this->actingAs($organizer)->post(route('events.submit', $event))->assertRedirect();
        $this->assertSame(Event::STATUS_PENDING_REVIEW, $event->fresh()->proposal_status);

        $this->actingAs($admin)->post(route('events.review', $event), [
            'decision' => 'revision_requested',
        ])->assertSessionHasErrors('feedback');
        $this->assertSame(Event::STATUS_PENDING_REVIEW, $event->fresh()->proposal_status);

        $this->actingAs($admin)->post(route('events.review', $event), [
            'decision' => 'revision_requested',
            'feedback' => 'Please provide a safety plan.',
        ])->assertRedirect(route('events.show', $event));

        $this->assertSame(Event::STATUS_REVISION_REQUESTED, $event->fresh()->proposal_status);
        $this->assertDatabaseHas('event_reviews', [
            'event_id' => $event->id,
            'reviewer_id' => $admin->id,
            'decision' => 'revision_requested',
            'feedback' => 'Please provide a safety plan.',
        ]);
        $this->actingAs($organizer)->get(route('events.show', $event))->assertSee('Please provide a safety plan.');

        $this->actingAs($organizer)->put(route('events.update', $event), [
            'club_name' => 'Robotics Club',
            'title' => 'Build Night',
            'description' => 'A club workshop with a safety briefing.',
            'objectives' => 'Introduce members to robotics.',
            'requirements' => 'Workshop room, kits, and safety plan.',
            'starts_at' => '2027-03-10 10:00',
            'ends_at' => '2027-03-10 12:00',
        ])->assertRedirect();

        $this->actingAs($organizer)->post(route('events.submit', $event))->assertRedirect();
        $this->actingAs($admin)->post(route('events.review', $event), [
            'decision' => 'approved',
        ])->assertRedirect();

        $this->assertSame(Event::STATUS_APPROVED, $event->fresh()->proposal_status);
        $this->assertCount(2, EventReview::query()->where('event_id', $event->id)->get());

        $rejectedEvent = Event::query()->create([
            'organizer_id' => $organizer->id,
            'club_name' => 'Robotics Club',
            'title' => 'Outdoor Demo',
            'description' => 'An outdoor demo.',
            'objectives' => 'Demonstrate projects.',
            'starts_at' => '2027-03-11 10:00',
            'ends_at' => '2027-03-11 12:00',
            'proposal_status' => Event::STATUS_DRAFT,
        ]);
        $this->actingAs($organizer)->post(route('events.submit', $rejectedEvent))->assertRedirect();
        $this->actingAs($admin)->post(route('events.review', $rejectedEvent), [
            'decision' => 'rejected',
        ])->assertSessionHasErrors('feedback');
        $this->actingAs($admin)->post(route('events.review', $rejectedEvent), [
            'decision' => 'rejected',
            'feedback' => 'The venue plan is not suitable.',
        ])->assertRedirect();

        $this->assertSame(Event::STATUS_REJECTED, $rejectedEvent->fresh()->proposal_status);
        $this->assertDatabaseHas('event_reviews', [
            'event_id' => $rejectedEvent->id,
            'decision' => 'rejected',
            'feedback' => 'The venue plan is not suitable.',
        ]);
    }

    public function test_organizer_cannot_manage_another_organizers_proposal_and_end_must_follow_start(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);
        $otherOrganizer = User::factory()->create(['role' => 'organizer']);

        $this->actingAs($organizer)->post(route('events.store'), [
            'club_name' => 'Arts Club',
            'title' => 'Gallery Night',
            'description' => 'A showcase.',
            'objectives' => 'Celebrate student art.',
            'starts_at' => '2027-03-10 12:00',
            'ends_at' => '2027-03-10 11:00',
        ])->assertSessionHasErrors('ends_at');

        $event = Event::query()->create([
            'organizer_id' => $organizer->id,
            'club_name' => 'Arts Club',
            'title' => 'Gallery Night',
            'description' => 'A showcase.',
            'objectives' => 'Celebrate student art.',
            'starts_at' => '2027-03-10 10:00',
            'ends_at' => '2027-03-10 12:00',
            'proposal_status' => Event::STATUS_DRAFT,
        ]);

        $this->actingAs($otherOrganizer)->get(route('events.show', $event))->assertForbidden();
        $this->actingAs($otherOrganizer)->put(route('events.update', $event), [
            'club_name' => 'Arts Club',
            'title' => 'Changed',
            'description' => 'Changed.',
            'objectives' => 'Changed.',
            'starts_at' => '2027-03-10 10:00',
            'ends_at' => '2027-03-10 12:00',
        ])->assertForbidden();
    }

    public function test_admin_can_view_a_legacy_event_without_proposal_details(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $legacyEvent = Event::query()->create(['proposal_status' => Event::STATUS_DRAFT]);

        $this->actingAs($admin)
            ->get(route('events.show', $legacyEvent))
            ->assertOk()
            ->assertSee('Untitled legacy event')
            ->assertSee('Not scheduled');
    }
}
