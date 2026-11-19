<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\ClubProposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClubProposalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_submit_a_club_proposal(): void
    {
        Storage::fake('local');
        $student = $this->makeUser('student');

        $response = $this->actingAs($student)->post(route('club-proposals.store'), [
            'name' => 'Robotics Club',
            'details' => 'A university club for robotics activities.',
            'objectives' => 'Build and learn robotics projects.',
            'constitution' => UploadedFile::fake()->create('constitution.pdf', 100, 'application/pdf'),
        ]);

        $proposal = ClubProposal::query()->where('user_id', $student->id)->firstOrFail();

        $response->assertRedirect(route('club-proposals.show', $proposal));
        $this->assertSame(ClubProposal::STATUS_PENDING, $proposal->status);
        Storage::disk('local')->assertExists($proposal->constitution_path);
    }

    public function test_university_administrator_can_approve_a_pending_proposal(): void
    {
        $student = $this->makeUser('student');
        $administrator = $this->makeUser('university_admin');
        $proposal = $this->makeProposal($student);

        $response = $this->actingAs($administrator)
            ->post(route('club-proposals.approve', $proposal));

        $response->assertRedirect(route('club-proposals.show', $proposal));
        $this->assertDatabaseHas('club_proposals', [
            'id' => $proposal->id,
            'status' => ClubProposal::STATUS_APPROVED,
        ]);
        $this->assertDatabaseHas('clubs', [
            'club_proposal_id' => $proposal->id,
            'name' => $proposal->name,
        ]);
        $this->assertDatabaseHas('audit_entries', [
            'actor_id' => $administrator->id,
            'subject_type' => ClubProposal::class,
            'subject_id' => $proposal->id,
            'from_state' => ClubProposal::STATUS_PENDING,
            'to_state' => ClubProposal::STATUS_APPROVED,
        ]);
        $this->assertSame(1, Club::query()->count());
    }

    public function test_university_administrator_can_reject_a_pending_proposal_and_audit_reason(): void
    {
        $student = $this->makeUser('student');
        $administrator = $this->makeUser('university_admin');
        $proposal = $this->makeProposal($student);

        $this->actingAs($administrator)
            ->post(route('club-proposals.reject', $proposal), [
                'reason' => 'Please provide more detail about the club objectives.',
            ])
            ->assertRedirect(route('club-proposals.show', $proposal));

        $this->assertDatabaseHas('club_proposals', [
            'id' => $proposal->id,
            'status' => ClubProposal::STATUS_REJECTED,
        ]);
        $this->assertDatabaseHas('audit_entries', [
            'actor_id' => $administrator->id,
            'subject_type' => ClubProposal::class,
            'subject_id' => $proposal->id,
            'from_state' => ClubProposal::STATUS_PENDING,
            'to_state' => ClubProposal::STATUS_REJECTED,
            'reason' => 'Please provide more detail about the club objectives.',
        ]);
        $this->assertSame(0, Club::query()->count());
    }

    public function test_student_cannot_approve_a_proposal(): void
    {
        $student = $this->makeUser('student');
        $proposal = $this->makeProposal($student);

        $this->actingAs($student)
            ->post(route('club-proposals.approve', $proposal))
            ->assertForbidden();

        $this->assertSame(ClubProposal::STATUS_PENDING, $proposal->fresh()->status);
    }

    private function makeUser(string $role): User
    {
        $user = User::factory()->create();
        $user->system_role = $role;
        $user->save();

        return $user;
    }

    private function makeProposal(User $student): ClubProposal
    {
        return ClubProposal::query()->create([
            'user_id' => $student->id,
            'name' => 'Robotics Club',
            'details' => 'A university club for robotics activities.',
            'objectives' => 'Build and learn robotics projects.',
            'status' => ClubProposal::STATUS_PENDING,
        ]);
    }
}
