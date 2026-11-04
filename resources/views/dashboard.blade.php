<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f7fb">
    <title>Dashboard · ClubPilot</title>
    <link rel="stylesheet" href="{{ asset('frontend/login.css') }}">
    <script src="{{ asset('frontend/dashboard.js') }}" defer></script>
</head>
<body class="dashboard-page">
    <main class="dashboard">
        <header class="dashboard-header">
            <a class="brand" href="{{ route('dashboard') }}" aria-label="ClubPilot dashboard">
                <span class="brand-mark" aria-hidden="true">C</span>
                <span>ClubPilot</span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="logout-button" type="submit">Sign out</button>
            </form>
        </header>

        <section class="dashboard-welcome">
            <p class="eyebrow">YOUR CLUB, IN SYNC</p>
            <h1>Welcome, {{ auth()->user()->name }}.</h1>
            <p>You are signed in. Your account is authenticated by Laravel and stored in the database.</p>
        </section>

        <section class="connection-card" aria-live="polite">
            <span id="database-indicator" class="database-indicator checking" aria-hidden="true"></span>
            <div>
                <p class="eyebrow">DATABASE CONNECTION</p>
                <h2 id="database-status">Checking connection…</h2>
                <p id="database-detail">Verifying the backend API and database.</p>
            </div>
        </section>

        <p class="dashboard-footer">Frontend → Laravel backend → Database</p>
    </main>
</body>
</html>
