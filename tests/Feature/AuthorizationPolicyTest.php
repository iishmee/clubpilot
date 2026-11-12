<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthorizationPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_policy_limits_proposals_to_their_organizer_and_admin_reviewers(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);
        $otherOrganizer = User::factory()->create(['role' => 'organizer']);
        $member = User::factory()->create(['role' => 'member']);
        $admin = User::factory()->create(['role' => 'admin']);
        $draft = $this->event($organizer, Event::STATUS_DRAFT);
        $pending = $this->event($organizer, Event::STATUS_PENDING_REVIEW);
        $revision = $this->event($organizer, Event::STATUS_REVISION_REQUESTED);
        $approved = $this->event($organizer, Event::STATUS_APPROVED);

        $this->assertTrue(Gate::forUser($organizer)->allows('viewAny', Event::class));
        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', Event::class));
        $this->assertFalse(Gate::forUser($member)->allows('viewAny', Event::class));
        $this->assertTrue(Gate::forUser($organizer)->allows('create', Event::class));
        $this->assertFalse(Gate::forUser($member)->allows('create', Event::class));

        $this->assertTrue(Gate::forUser($organizer)->allows('view', $draft));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $draft));
        $this->assertFalse(Gate::forUser($otherOrganizer)->allows('view', $draft));
        $this->assertTrue(Gate::forUser($organizer)->allows('update', $draft));
        $this->assertFalse(Gate::forUser($organizer)->allows('update', $pending));
        $this->assertTrue(Gate::forUser($organizer)->allows('submit', $revision));
        $this->assertFalse(Gate::forUser($organizer)->allows('submit', $approved));

        $this->assertTrue(Gate::forUser($admin)->allows('review', Event::class));
        $this->assertFalse(Gate::forUser($organizer)->allows('review', Event::class));
    }

    public function test_reservation_policy_requires_approved_owned_event_and_limits_review_to_admins(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);
        $otherOrganizer = User::factory()->create(['role' => 'organizer']);
        $member = User::factory()->create(['role' => 'member']);
        $admin = User::factory()->create(['role' => 'admin']);
        $approvedEvent = $this->event($organizer, Event::STATUS_APPROVED);
        $pendingEvent = $this->event($organizer, Event::STATUS_PENDING_REVIEW);
        $otherEvent = $this->event($otherOrganizer, Event::STATUS_APPROVED);
        $pendingReservation = $this->reservation($approvedEvent, $organizer, Reservation::STATUS_PENDING);
        $approvedReservation = $this->reservation($approvedEvent, $organizer, Reservation::STATUS_APPROVED);

        $this->assertTrue(Gate::forUser($organizer)->allows('viewAny', Reservation::class));
        $this->assertFalse(Gate::forUser($member)->allows('viewAny', Reservation::class));
        $this->assertTrue(Gate::forUser($organizer)->allows('create', [Reservation::class, $approvedEvent]));
        $this->assertFalse(Gate::forUser($organizer)->allows('create', [Reservation::class, $pendingEvent]));
        $this->assertFalse(Gate::forUser($otherOrganizer)->allows('create', [Reservation::class, $approvedEvent]));
        $this->assertFalse(Gate::forUser($member)->allows('create', [Reservation::class, $approvedEvent]));

        $this->assertTrue(Gate::forUser($organizer)->allows('view', $pendingReservation));
        $this->assertFalse(Gate::forUser($otherOrganizer)->allows('view', $pendingReservation));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $pendingReservation));
        $this->assertTrue(Gate::forUser($admin)->allows('reviewAny', Reservation::class));
        $this->assertFalse(Gate::forUser($organizer)->allows('reviewAny', Reservation::class));
        $this->assertTrue(Gate::forUser($admin)->allows('review', $pendingReservation));
        $this->assertFalse(Gate::forUser($organizer)->allows('review', $pendingReservation));
        $this->assertFalse(Gate::forUser($admin)->allows('review', $approvedReservation));
        $this->assertFalse(Gate::forUser($organizer)->allows('create', [Reservation::class, $otherEvent]));
    }

    public function test_venue_policy_allows_organizers_to_view_and_only_admins_to_add_venues(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);
        $member = User::factory()->create(['role' => 'member']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertTrue(Gate::forUser($organizer)->allows('viewAny', Venue::class));
        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', Venue::class));
        $this->assertFalse(Gate::forUser($member)->allows('viewAny', Venue::class));
        $this->assertTrue(Gate::forUser($admin)->allows('create', Venue::class));
        $this->assertFalse(Gate::forUser($organizer)->allows('create', Venue::class));
        $this->assertFalse(Gate::forUser($member)->allows('create', Venue::class));
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

    private function reservation(Event $event, User $requester, string $status): Reservation
    {
        $venue = Venue::query()->create([
            'name' => fake()->unique()->word(),
            'location' => 'North Campus',
            'capacity' => 100,
            'availability_status' => 'available',
        ]);

        return Reservation::query()->create([
            'event_id' => $event->id,
            'venue_id' => $venue->id,
            'requester_id' => $requester->id,
            'starts_at' => '2027-03-10 10:00',
            'ends_at' => '2027-03-10 11:00',
            'status' => $status,
        ]);
    }
}
