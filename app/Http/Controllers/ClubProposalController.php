<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewClubProposalRequest;
use App\Http\Requests\StoreClubProposalRequest;
use App\Models\Club;
use App\Models\ClubProposal;
use App\Services\ClubProposalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ClubProposalController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ClubProposal::class);

        $query = ClubProposal::query()
            ->with('student')
            ->latest();

        if (! $request->user()->isUniversityAdmin()) {
            $query->where('user_id', $request->user()->getKey());
        }

        return view('club-proposals.index', [
            'proposals' => $query->paginate(12),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', ClubProposal::class);

        return view('club-proposals.create');
    }

    public function store(
        StoreClubProposalRequest $request,
        ClubProposalService $service,
    ): RedirectResponse {
        $this->authorize('create', ClubProposal::class);

        $proposal = $service->submit(
            $request->user(),
            $request->validated()
        );

        return redirect()
            ->route('club-proposals.show', $proposal)
            ->with('status', 'Your club proposal has been submitted for review.');
    }

    public function show(ClubProposal $clubProposal): View
    {
        $this->authorize('view', $clubProposal);

        $clubProposal->load([
            'student',
            'club',
            'auditEntries.actor',
        ]);

        return view('club-proposals.show', compact('clubProposal'));
    }

    public function approve(
        Request $request,
        ClubProposal $clubProposal,
        ClubProposalService $service,
    ): RedirectResponse {
        $this->authorize('approve', $clubProposal);

        $service->approve($clubProposal, $request->user());

        return redirect()
            ->route('club-proposals.show', $clubProposal)
            ->with(
                'status',
                'The proposal has been approved and the club is now in the club directory.'
            );
    }

    public function reject(
        ReviewClubProposalRequest $request,
        ClubProposal $clubProposal,
        ClubProposalService $service,
    ): RedirectResponse {
        $this->authorize('reject', $clubProposal);

        $service->reject(
            $clubProposal,
            $request->user(),
            $request->validated('reason')
        );

        return redirect()
            ->route('club-proposals.show', $clubProposal)
            ->with('status', 'The proposal has been rejected.');
    }

    public function directory(): View
    {
        $this->authorize('viewDirectory', ClubProposal::class);

        return view('club-proposals.directory', [
            'clubs' => Club::query()
                ->orderBy('name')
                ->paginate(12),
        ]);
    }

    public function downloadConstitution(ClubProposal $clubProposal)
    {
        $this->authorize('downloadConstitution', $clubProposal);

        $disk = Storage::disk('local');

        abort_unless(
            $clubProposal->constitution_path
                && $disk->exists($clubProposal->constitution_path),
            404
        );

        $path = $disk->path($clubProposal->constitution_path);

        return response()->download(
            $path,
            basename($clubProposal->constitution_path)
        );
    }
}

