<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class VenueController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Venue::class);

        $venues = Venue::query()->orderBy('name')->paginate(15);

        return view('venues.index', compact('venues'));
    }

    public function create(): View
    {
        Gate::authorize('create', Venue::class);

        return view('venues.create');
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Venue::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:venues,name'],
            'location' => ['required', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100000'],
            'availability_status' => ['required', 'in:available,unavailable'],
        ]);

        Venue::query()->create($data);

        return redirect()->route('venues.index')->with('status', 'Venue added.');
    }
}
