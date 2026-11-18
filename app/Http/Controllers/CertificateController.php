<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\User;
use App\Models\VolunteerOpportunity;
use App\Services\CertificateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CertificateController extends Controller
{
    public function __construct(private CertificateService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Certificate::class);
        $user = auth()->user();

        $query = Certificate::query()
            ->with(['user', 'issuer', 'opportunity.event'])
            ->latest('issued_at')
            ->latest('id');

        // Admin: everything. Organizer: certificates for their own events. Member: only their own.
        if ($user->isOrganizer()) {
            $query->whereHas('opportunity.event', fn ($q) => $q->where('organizer_id', $user->id));
        } elseif (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        return view('certificates.index', [
            'certificates' => $query->paginate(15),
            'eligible' => $user->isOrganizer() ? $this->service->eligibleFor($user) : collect(),
            'isMember' => ! $user->isAdmin() && ! $user->isOrganizer(),
        ]);
    }

    public function store(Request $request, VolunteerOpportunity $opportunity): RedirectResponse
    {
        Gate::authorize('issue', [Certificate::class, $opportunity]);

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $volunteer = User::query()->findOrFail($data['user_id']);
        $this->service->issue($opportunity, $volunteer, $request->user());

        return redirect()->route('certificates.index')->with('status', 'Certificate issued to '.$volunteer->name.'.');
    }

    public function download(Certificate $certificate): Response
    {
        Gate::authorize('view', $certificate);

        return response($this->service->renderPdf($certificate), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="certificate-'.$certificate->certificate_number.'.pdf"',
        ]);
    }
}
