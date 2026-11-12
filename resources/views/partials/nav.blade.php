<nav class="navbar navbar-expand-lg bg-body-tertiary">
    <div class="container">
        <a class="navbar-brand" href="/">ClubPilot</a>
        @auth
            <div class="navbar-nav ms-auto">
                @if (auth()->user()->isOrganizer() || auth()->user()->isAdmin())
                    <a class="nav-link" href="{{ route('events.index') }}">Event proposals</a>
                    <a class="nav-link" href="{{ route('venues.index') }}">Venues</a>
                    <a class="nav-link" href="{{ route('reservations.index') }}">Reservations</a>
                @endif
                @if (auth()->user()->isAdmin())
                    <a class="nav-link" href="{{ route('events.reviews.index') }}">Proposal review</a>
                    <a class="nav-link" href="{{ route('reservations.reviews.index') }}">Venue request review</a>
                @endif
                <a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a>
            </div>
        @endauth
    </div>
</nav>
