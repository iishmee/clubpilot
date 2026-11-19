<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f7fb">

    <title>Dashboard · ClubPilot</title>

    <link rel="stylesheet" href="{{ asset('frontend/login.css') }}">
    <script src="{{ asset('frontend/dashboard.js') }}" defer></script>

    <style>
        .dashboard-navigation {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin: 24px 0 32px;
        }

        .dashboard-navigation a {
            display: inline-block;
            padding: 11px 18px;
            border: 1px solid #dce3f0;
            border-radius: 10px;
            background: #ffffff;
            color: #4563eb;
            text-decoration: none;
            font-weight: 600;
        }

        .dashboard-navigation a.primary {
            background: #4563eb;
            color: #ffffff;
            border-color: #4563eb;
        }

        .dashboard-navigation a:hover {
            opacity: 0.85;
        }
    </style>
</head>

<body class="dashboard-page">

    <main class="dashboard">

        <header class="dashboard-header">

            <a class="brand"
               href="{{ route('dashboard') }}"
               aria-label="ClubPilot dashboard">

                <span class="brand-mark" aria-hidden="true">C</span>
                <span>ClubPilot</span>

            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button class="logout-button" type="submit">
                    Sign out
                </button>
            </form>

        </header>


        <!-- Main Navigation -->

        <nav class="dashboard-navigation" aria-label="Main navigation">

            @if(auth()->user()->isUniversityAdmin())

                <!-- Administrator Navigation -->

                <a href="{{ route('club-proposals.index') }}">
                    Review Club Proposals
                </a>

                <a href="{{ route('clubs.directory') }}">
                    Club Directory
                </a>

                <a href="{{ route('memberships.index') }}">
                    Membership Management
                </a>

                <a href="{{ route('equipment.index') }}">
                    Equipment Inventory
                </a>

                <a href="{{ route('loans.index') }}">
                    Loan Management
                </a>

            @else

                <!-- Student Navigation -->

                <a href="{{ route('club-proposals.index') }}">
                    My Club Proposals
                </a>

                <a class="primary"
                   href="{{ route('club-proposals.create') }}">
                    Submit a Club Proposal
                </a>

                <a href="{{ route('clubs.directory') }}">
                    Club Directory
                </a>

                <a href="{{ route('memberships.create') }}">
                    Apply for Membership
                </a>

                <a href="{{ route('equipment.index') }}">
                    Equipment Inventory
                </a>

                <a href="{{ route('loans.create') }}">
                    Borrow Equipment
                </a>

            @endif

        </nav>


        <!-- Welcome Section -->

        <section class="dashboard-welcome">

            <p class="eyebrow">YOUR CLUB, IN SYNC</p>

            <h1>
                Welcome, {{ auth()->user()->name }}.
            </h1>

            <p>
                You are signed in. Your account is authenticated
                by Laravel and stored in the database.
            </p>

        </section>


        <!-- Database Connection Status -->

        <section class="connection-card" aria-live="polite">

            <span id="database-indicator"
                  class="database-indicator checking"
                  aria-hidden="true"></span>

            <div>

                <p class="eyebrow">DATABASE CONNECTION</p>

                <h2 id="database-status">
                    Checking connection…
                </h2>

                <p id="database-detail">
                    Verifying the backend API and database.
                </p>

            </div>

        </section>


        <!-- Dashboard Footer -->

        <p class="dashboard-footer">
            Frontend → Laravel backend → Database
        </p>

    </main>

</body>

</html>



