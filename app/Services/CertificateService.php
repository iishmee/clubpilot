<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\ServiceEntry;
use App\Models\User;
use App\Models\VolunteerApplication;
use App\Models\VolunteerOpportunity;
use App\Support\SimplePdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CertificateService
{
    public function __construct(private ServiceHoursService $serviceHours) {}

    /**
     * Volunteers of this organizer's events who have approved hours but no certificate yet.
     * Each row has user, opportunity (with event) and total_hours.
     *
     * @return Collection<int, ServiceEntry>
     */
    public function eligibleFor(User $organizer): Collection
    {
        $rows = ServiceEntry::query()
            ->selectRaw('user_id, volunteer_opportunity_id, SUM(hours) as total_hours')
            ->where('status', ServiceEntry::STATUS_APPROVED)
            ->whereHas('opportunity.event', fn ($q) => $q->where('organizer_id', $organizer->id))
            ->groupBy('user_id', 'volunteer_opportunity_id')
            ->havingRaw('SUM(hours) > 0')
            ->with(['user', 'opportunity.event'])
            ->get();

        $issued = Certificate::query()
            ->get(['user_id', 'volunteer_opportunity_id'])
            ->map(fn (Certificate $certificate) => $certificate->user_id.'-'.$certificate->volunteer_opportunity_id)
            ->all();

        return $rows
            ->reject(fn (ServiceEntry $row) => in_array($row->user_id.'-'.$row->volunteer_opportunity_id, $issued, true))
            ->values();
    }

    public function issue(VolunteerOpportunity $opportunity, User $volunteer, User $issuer): Certificate
    {
        Gate::forUser($issuer)->authorize('issue', [Certificate::class, $opportunity]);

        return DB::transaction(function () use ($opportunity, $volunteer, $issuer): Certificate {
            // Lock the opportunity so a double click cannot create two certificates.
            $locked = VolunteerOpportunity::query()->lockForUpdate()->findOrFail($opportunity->id);

            $accepted = VolunteerApplication::query()
                ->where('volunteer_opportunity_id', $locked->id)
                ->where('user_id', $volunteer->id)
                ->where('status', VolunteerApplication::STATUS_ACCEPTED)
                ->exists();

            if (! $accepted) {
                throw ValidationException::withMessages([
                    'user_id' => 'This person was not accepted as a volunteer for this opportunity.',
                ]);
            }

            $alreadyIssued = Certificate::query()
                ->where('volunteer_opportunity_id', $locked->id)
                ->where('user_id', $volunteer->id)
                ->exists();

            if ($alreadyIssued) {
                throw ValidationException::withMessages([
                    'user_id' => 'A certificate has already been issued to this volunteer for this opportunity.',
                ]);
            }

            $hours = $this->serviceHours->approvedHours($volunteer, $locked);

            if ($hours <= 0) {
                throw ValidationException::withMessages([
                    'user_id' => 'This volunteer has no approved service hours yet. Approve their logged hours first.',
                ]);
            }

            return Certificate::query()->create([
                'user_id' => $volunteer->id,
                'volunteer_opportunity_id' => $locked->id,
                'issued_by' => $issuer->id,
                'certificate_number' => $this->newNumber(),
                'hours' => $hours,
                'issued_at' => now(),
            ]);
        });
    }

    /** Draw the certificate and return the PDF file contents. */
    public function renderPdf(Certificate $certificate): string
    {
        $certificate->loadMissing(['user', 'issuer', 'opportunity.event']);

        $opportunity = $certificate->opportunity;
        $event = $opportunity?->event;

        $navy = [0.10, 0.22, 0.45];
        $grey = [0.40, 0.40, 0.40];
        $ink = [0.10, 0.10, 0.10];

        return (new SimplePdf)
            ->rect(24, 24, 794, 547, 3, $navy)
            ->rect(34, 34, 774, 527, 1, $navy)
            ->centeredText('CLUBPILOT', 510, 14, 'bold', $grey)
            ->centeredText('Certificate of Participation', 455, 42, 'bold', $navy)
            ->line(300, 435, 542, 435, 1.5, $navy)
            ->centeredText('This certificate is proudly presented to', 395, 16, 'italic', $grey)
            ->centeredText((string) $certificate->user?->name, 340, 38, 'bold', $ink, 700, 16)
            ->centeredText('in recognition of volunteer service as', 295, 16, 'italic', $grey)
            ->centeredText((string) ($opportunity?->title ?? 'Volunteer'), 262, 26, 'bold', $navy, 700, 14)
            ->centeredText('at '.($event?->title ?? 'the event'), 230, 18, 'regular', $ink, 700, 12)
            ->centeredText('organized by '.($event?->club_name ?? 'the club'), 205, 14, 'regular', $grey)
            ->centeredText('Verified service hours: '.number_format((float) $certificate->hours, 2), 165, 18, 'bold', $ink)
            ->line(320, 120, 522, 120, 1, $grey)
            ->centeredText(($certificate->issuer?->name ?? 'Event organizer').', Event Organizer', 102, 13, 'regular', $ink)
            ->centeredText('Issued on '.$certificate->issued_at?->format('F j, Y'), 80, 12, 'regular', $grey)
            ->centeredText('Certificate No. '.$certificate->certificate_number, 55, 11, 'regular', $grey)
            ->output();
    }

    private function newNumber(): string
    {
        do {
            $number = 'CP-'.now()->format('Y').'-'.strtoupper(Str::random(8));
        } while (Certificate::query()->where('certificate_number', $number)->exists());

        return $number;
    }
}
