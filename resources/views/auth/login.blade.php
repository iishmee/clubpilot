<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f7fb">
    <title>Sign in · ClubPilot</title>
    <link rel="stylesheet" href="{{ asset('frontend/login.css') }}">
    <script src="{{ asset('frontend/login.js') }}" defer></script>
</head>
<body>
    <main class="auth-layout">
        <section class="brand-panel">
            <a class="brand" href="{{ route('login') }}" aria-label="ClubPilot sign in">
                <span class="brand-mark" aria-hidden="true">C</span>
                <span>ClubPilot</span>
            </a>
            <div class="brand-message">
                <p class="eyebrow">YOUR CLUB, IN SYNC</p>
                <h1>Make good things happen, together.</h1>
                <p>One place to bring your club's people, plans, and events together.</p>
            </div>
            <p class="panel-footer">A simpler way to run your club.</p>
        </section>

        <section class="form-panel">
            <div class="login-card">
                <p class="eyebrow">WELCOME BACK</p>
                <h2>Sign in to ClubPilot</h2>
                <p class="form-intro">Enter your account details to continue.</p>

                @if (session('status'))
                    <p class="notice" role="status">{{ session('status') }}</p>
                @endif

                @if ($errors->has('email') && ! old('email'))
                    <p class="error-summary" role="alert">{{ $errors->first('email') }}</p>
                @endif

                <form method="POST" action="{{ route('login.store') }}">
                    @csrf

                    <label for="email">Email address</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        autocomplete="username"
                        placeholder="you@example.com"
                        required
                        autofocus
                        aria-describedby="{{ $errors->has('email') ? 'email-error' : '' }}"
                    >
                    @error('email')
                        <span class="field-error" id="email-error">{{ $message }}</span>
                    @enderror

                    <div class="password-label">
                        <label for="password">Password</label>
                        <button class="text-button" type="button" id="toggle-password" aria-pressed="false">Show</button>
                    </div>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        placeholder="Enter your password"
                        required
                    >
                    @error('password')
                        <span class="field-error">{{ $message }}</span>
                    @enderror

                    <label class="remember">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                        <span>Remember me</span>
                    </label>

                    <button class="submit-button" type="submit">Sign in <span aria-hidden="true">→</span></button>
                </form>

                <p class="demo-hint">
                    Demo account: <strong>test@example.com</strong>
                    <span>Password: <strong>password</strong></span>
                </p>
            </div>
            <p class="copyright">© {{ date('Y') }} ClubPilot</p>
        </section>
    </main>
</body>
</html>
