<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Event;
use App\Models\ServiceEntry;
use App\Models\User;
use App\Models\VolunteerApplication;
use App\Models\VolunteerOpportunity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateTest extends TestCase
{
    use RefreshDatabase;

    private function opportunity(User $organizer): VolunteerOpportunity
    {
        $event = Event::query()->create([
            'organizer_id' => $organizer->id,
            'club_name' => 'Tech Club',
            'title' => 'Hackathon',
            'description' => 'Desc',
            'objectives' => 'Obj',
            'starts_at' => '2027-03-10 10:00',
            'ends_at' => '2027-03-10 12:00',
            'proposal_status' => Event::STATUS_APPROVED,
        ]);

        return VolunteerOpportunity::query()->create([
            'event_id' => $event->id,
            'title' => 'Registration desk',
            'slots_needed' => 5,
            'status' => 'open',
        ]);
    }

    private function accept(VolunteerOpportunity $opportunity, User $member): void
    {
        VolunteerApplication::query()->create([
            'volunteer_opportunity_id' => $opportunity->id,
            'user_id' => $member->id,
            'status' => 'accepted',
        ]);
    }

    private function hours(VolunteerOpportunity $opportunity, User $member, float $hours, string $status = 'approved'): void
    {
        ServiceEntry::query()->create([
            'user_id' => $member->id,
            'volunteer_opportunity_id' => $opportunity->id,
            'hours' => $hours,
            'worked_on' => now()->subDay()->toDateString(),
            'status' => $status,
        ]);
    }

    /** An organizer, an accepted volunteer with 3.5 approved hours, and the opportunity. */
    private function scenario(): array
    {
        $organizer = User::factory()->create(['role' => 'organizer', 'name' => 'Olivia Organizer']);
        $member = User::factory()->create(['role' => 'member', 'name' => 'Jane Volunteer']);
        $opportunity = $this->opportunity($organizer);
        $this->accept($opportunity, $member);
        $this->hours($opportunity, $member, 2.0);
        $this->hours($opportunity, $member, 1.5);
        $this->hours($opportunity, $member, 9.0, 'pending'); // pending hours must not count

        return [$organizer, $member, $opportunity];
    }

    public function test_organizer_can_issue_a_certificate_with_the_approved_hours(): void
    {
        [$organizer, $member, $opportunity] = $this->scenario();

        $this->actingAs($organizer)
            ->post(route('certificates.store', $opportunity), ['user_id' => $member->id])
            ->assertRedirect(route('certificates.index'));

        $this->assertDatabaseCount('certificates', 1);
        $certificate = Certificate::query()->first();
        $this->assertSame($member->id, $certificate->user_id);
        $this->assertSame($organizer->id, $certificate->issued_by);
        $this->assertEquals(3.5, (float) $certificate->hours);
        $this->assertMatchesRegularExpression('/^CP-\d{4}-[A-Z0-9]{8}$/', $certificate->certificate_number);
    }

    public function test_cannot_issue_without_approved_hours_or_twice_or_to_a_non_volunteer(): void
    {
        $organizer = User::factory()->create(['role' => 'organizer']);
        $member = User::factory()->create(['role' => 'member']);
        $stranger = User::factory()->create(['role' => 'member']);
        $opportunity = $this->opportunity($organizer);
        $this->accept($opportunity, $member);
        $this->hours($opportunity, $member, 4.0, 'pending');
        $this->hours($opportunity, $stranger, 4.0); // approved hours, but never accepted

        // Only pending hours -> not eligible yet.
        $this->actingAs($organizer)
            ->post(route('certificates.store', $opportunity), ['user_id' => $member->id])
            ->assertSessionHasErrors('user_id');

        // Not accepted for this opportunity.
        $this->actingAs($organizer)
            ->post(route('certificates.store', $opportunity), ['user_id' => $stranger->id])
            ->assertSessionHasErrors('user_id');

        $this->assertDatabaseCount('certificates', 0);

        // Once hours are approved it works, but only once.
        ServiceEntry::query()->where('user_id', $member->id)->update(['status' => 'approved']);

        $this->actingAs($organizer)
            ->post(route('certificates.store', $opportunity), ['user_id' => $member->id])
            ->assertRedirect(route('certificates.index'));
        $this->actingAs($organizer)
            ->post(route('certificates.store', $opportunity), ['user_id' => $member->id])
            ->assertSessionHasErrors('user_id');

        $this->assertDatabaseCount('certificates', 1);
    }

    public function test_only_the_events_organizer_can_issue(): void
    {
        [, $member, $opportunity] = $this->scenario();
        $otherOrganizer = User::factory()->create(['role' => 'organizer']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($otherOrganizer)->post(route('certificates.store', $opportunity), ['user_id' => $member->id])->assertForbidden();
        $this->actingAs($admin)->post(route('certificates.store', $opportunity), ['user_id' => $member->id])->assertForbidden();
        $this->actingAs($member)->post(route('certificates.store', $opportunity), ['user_id' => $member->id])->assertForbidden();

        $this->assertDatabaseCount('certificates', 0);
    }

    public function test_the_volunteer_can_download_a_real_pdf(): void
    {
        [$organizer, $member, $opportunity] = $this->scenario();
        $this->actingAs($organizer)->post(route('certificates.store', $opportunity), ['user_id' => $member->id]);
        $certificate = Certificate::query()->firstOrFail();

        $response = $this->actingAs($member)->get(route('certificates.download', $certificate));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        $pdf = $response->getContent();
        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        $this->assertStringContainsString('%%EOF', $pdf);
        $this->assertStringContainsString('Jane Volunteer', $pdf);
        $this->assertStringContainsString('Registration desk', $pdf);
        $this->assertStringContainsString('3.50', $pdf);
        $this->assertStringContainsString($certificate->certificate_number, $pdf);
    }

    public function test_only_the_owner_the_events_organizer_and_admin_can_download(): void
    {
        [$organizer, $member, $opportunity] = $this->scenario();
        $this->actingAs($organizer)->post(route('certificates.store', $opportunity), ['user_id' => $member->id]);
        $certificate = Certificate::query()->firstOrFail();

        $otherMember = User::factory()->create(['role' => 'member']);
        $otherOrganizer = User::factory()->create(['role' => 'organizer']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($otherMember)->get(route('certificates.download', $certificate))->assertForbidden();
        $this->actingAs($otherOrganizer)->get(route('certificates.download', $certificate))->assertForbidden();
        $this->actingAs($organizer)->get(route('certificates.download', $certificate))->assertOk();
        $this->actingAs($admin)->get(route('certificates.download', $certificate))->assertOk();
    }

    public function test_certificate_pages_show_the_right_things_to_each_role(): void
    {
        [$organizer, $member, $opportunity] = $this->scenario();
        $otherMember = User::factory()->create(['role' => 'member', 'name' => 'Other Person']);

        // Before issuing: the organizer sees the volunteer in the "ready to issue" list.
        $this->actingAs($organizer)->get(route('certificates.index'))
            ->assertOk()
            ->assertSee('Jane Volunteer')
            ->assertSee('Issue certificate');

        $this->actingAs($organizer)->post(route('certificates.store', $opportunity), ['user_id' => $member->id]);
        $certificate = Certificate::query()->firstOrFail();

        // After issuing: the button is gone and the certificate is listed.
        $this->actingAs($organizer)->get(route('certificates.index'))
            ->assertOk()
            ->assertSee($certificate->certificate_number)
            ->assertDontSee('Issue certificate');

        $this->actingAs($member)->get(route('certificates.index'))
            ->assertOk()
            ->assertSee($certificate->certificate_number)
            ->assertSee('Download PDF');

        $this->actingAs($otherMember)->get(route('certificates.index'))
            ->assertOk()
            ->assertDontSee($certificate->certificate_number);
    }
}
