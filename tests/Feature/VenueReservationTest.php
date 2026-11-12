<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenueReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_approved_events_can_request_a_venue(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);
        $event = $this->event($organizer, Event::STATUS_PENDING_REVIEW);

        $this->actingAs($organizer)->get(route('events.reservations.create', $event))->assertForbidden();
        $this->actingAs($organizer)->post(route('events.reservations.store', $event), [
            'venue_id' => Venue::query()->create([
                'name' => 'Lecture Hall',
                'location' => 'North Campus',
                'capacity' => 100,
                'availability_status' => 'available',
            ])->id,
            'starts_at' => '2027-03-10 10:00',
            'ends_at' => '2027-03-10 12:00',
        ])->assertForbidden();

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_member_cannot_access_event_venue_or_reservation_management_routes(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $event = $this->event(User::factory()->create(['role' => 'organizer']), Event::STATUS_APPROVED);

        $this->actingAs($member)->get(route('events.index'))->assertForbidden();
        $this->actingAs($member)->get(route('events.create'))->assertForbidden();
        $this->actingAs($member)->get(route('events.reservations.create', $event))->assertForbidden();
        $this->actingAs($member)->get(route('venues.index'))->assertForbidden();
        $this->actingAs($member)->get(route('venues.create'))->assertForbidden();
        $this->actingAs($member)->get(route('reservations.index'))->assertForbidden();
        $this->actingAs($member)->get(route('events.reviews.index'))->assertForbidden();
        $this->actingAs($member)->get(route('reservations.reviews.index'))->assertForbidden();
    }

    public function test_dashboard_navigation_shows_only_the_current_roles_workflows(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($organizer)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Event proposals')
            ->assertSee('Venues')
            ->assertSee('Reservations')
            ->assertDontSee('Proposal review')
            ->assertDontSee('Reservation review');

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Event proposals')
            ->assertSee('Proposal review')
            ->assertSee('Reservation review');

        $this->actingAs($member)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Event proposals')
            ->assertDontSee('Reservation review');
    }

    public function test_organizer_cannot_add_venues_or_review_reservations(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);

        $this->actingAs($organizer)->get(route('venues.create'))->assertForbidden();
        $this->actingAs($organizer)->post(route('venues.store'), [
            'name' => 'Unauthorized Hall',
            'location' => 'Campus',
            'capacity' => 100,
            'availability_status' => 'available',
        ])->assertForbidden();
        $this->actingAs($organizer)->get(route('reservations.reviews.index'))->assertForbidden();
    }

    public function test_overlapping_active_requests_conflict_but_adjacent_slots_are_allowed(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);
        $event = $this->event($organizer, Event::STATUS_APPROVED);
        $venue = Venue::query()->create([
            'name' => 'Lecture Hall',
            'location' => 'North Campus',
            'capacity' => 100,
            'availability_status' => 'available',
        ]);

        $this->actingAs($organizer)->post(route('events.reservations.store', $event), [
            'venue_id' => $venue->id,
            'starts_at' => '2027-03-10 10:00',
            'ends_at' => '2027-03-10 11:00',
        ])->assertRedirect(route('events.show', $event));

        $this->actingAs($organizer)->from(route('events.reservations.create', $event))
            ->post(route('events.reservations.store', $event), [
                'venue_id' => $venue->id,
                'starts_at' => '2027-03-10 10:30',
                'ends_at' => '2027-03-10 11:30',
            ])
            ->assertSessionHasErrors('venue_id');

        $this->actingAs($organizer)->post(route('events.reservations.store', $event), [
            'venue_id' => $venue->id,
            'starts_at' => '2027-03-10 11:00',
            'ends_at' => '2027-03-10 12:00',
        ])->assertRedirect(route('events.show', $event));

        $this->assertDatabaseCount('reservations', 2);
    }

    public function test_incomplete_legacy_venues_are_not_offered_for_reservation(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);
        $event = $this->event($organizer, Event::STATUS_APPROVED);
        Venue::query()->create([
            'name' => 'Legacy room',
            'availability_status' => 'available',
        ]);
        Venue::query()->create([
            'name' => 'Configured hall',
            'location' => 'North Campus',
            'capacity' => 80,
            'availability_status' => 'available',
        ]);

        $this->actingAs($organizer)
            ->get(route('events.reservations.create', $event))
            ->assertOk()
            ->assertSee('Configured hall')
            ->assertDontSee('Legacy room');
    }

    public function test_admin_must_give_a_rejection_reason_and_can_review_venue_requests(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $event = $this->event($organizer, Event::STATUS_APPROVED);
        $venue = Venue::query()->create([
            'name' => 'Seminar Room',
            'location' => 'Library',
            'capacity' => 30,
            'availability_status' => 'available',
        ]);
        $reservation = Reservation::query()->create([
            'event_id' => $event->id,
            'venue_id' => $venue->id,
            'requester_id' => $organizer->id,
            'starts_at' => '2027-03-10 10:00',
            'ends_at' => '2027-03-10 11:00',
            'status' => Reservation::STATUS_PENDING,
        ]);

        $this->actingAs($admin)->post(route('reservations.review', $reservation), [
            'decision' => 'rejected',
        ])->assertSessionHasErrors('feedback');

        $this->actingAs($admin)->post(route('reservations.review', $reservation), [
            'decision' => 'rejected',
            'feedback' => 'The room is needed for an examination.',
        ])->assertRedirect();

        $reservation->refresh();
        $this->assertSame(Reservation::STATUS_REJECTED, $reservation->status);
        $this->assertSame($admin->id, $reservation->reviewer_id);
        $this->assertNotNull($reservation->reviewed_at);
        $this->assertSame('The room is needed for an examination.', $reservation->review_feedback);
    }

    public function test_organizer_event_approval_and_reservation_approval_work_end_to_end(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $venue = Venue::query()->create([
            'name' => 'Main Hall',
            'location' => 'Central Campus',
            'capacity' => 250,
            'availability_status' => 'available',
        ]);

        $this->actingAs($organizer)->post(route('events.store'), [
            'club_name' => 'Engineering Club',
            'title' => 'Project Showcase',
            'description' => 'Students demonstrate projects.',
            'objectives' => 'Share project work.',
            'requirements' => 'Projector.',
            'starts_at' => '2027-04-10 10:00',
            'ends_at' => '2027-04-10 12:00',
        ])->assertRedirect();
        $event = Event::query()->firstOrFail();

        $this->actingAs($organizer)->post(route('events.submit', $event))->assertRedirect();
        $this->assertSame(Event::STATUS_PENDING_REVIEW, $event->fresh()->proposal_status);

        $this->actingAs($admin)->get(route('events.reviews.index'))
            ->assertOk()
            ->assertSee('Project Showcase');
        $this->actingAs($admin)->post(route('events.review', $event), [
            'decision' => 'approved',
        ])->assertRedirect();
        $this->assertSame(Event::STATUS_APPROVED, $event->fresh()->proposal_status);

        $this->actingAs($organizer)->get(route('events.reservations.create', $event))
            ->assertOk()
            ->assertSee('Main Hall');
        $this->actingAs($organizer)->post(route('events.reservations.store', $event), [
            'venue_id' => $venue->id,
            'starts_at' => '2027-04-10 10:00',
            'ends_at' => '2027-04-10 12:00',
        ])->assertRedirect(route('events.show', $event));
        $reservation = Reservation::query()->firstOrFail();
        $this->assertSame(Reservation::STATUS_PENDING, $reservation->status);

        $this->actingAs($admin)->get(route('reservations.reviews.index'))
            ->assertOk()
            ->assertSee('Project Showcase');
        $this->actingAs($admin)->post(route('reservations.review', $reservation), [
            'decision' => 'approved',
        ])->assertRedirect();

        $this->assertSame(Reservation::STATUS_APPROVED, $reservation->fresh()->status);
        $this->actingAs($organizer)->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Main Hall')
            ->assertSee('Approved');
    }

    private function event(User $organizer, string $status): Event
    {
        return Event::query()->create([
            'organizer_id' => $organizer->id,
            'club_name' => 'Science Club',
            'title' => 'Science Fair',
            'description' => 'A student event.',
            'objectives' => 'Share science.',
            'starts_at' => '2027-03-10 10:00',
            'ends_at' => '2027-03-10 12:00',
            'proposal_status' => $status,
        ]);
    }
}
