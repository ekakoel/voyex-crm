<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php($appTitle = trim((string) ($companyName ?? config('app.name', 'VOYEX CRM'))))
    @php($loginTagline = trim((string) ($companyTagline ?? 'Smart Travel CRM Platform')))
    @php($loginFooterSuffix = trim((string) ($companyFooterNote ?? '')) ?: 'All rights reserved.')
    <title>Set New Password | {{ $appTitle }}</title>
    @if (!empty($companyFaviconUrl))
        <link rel="icon" type="{{ $companyFaviconMime ?? 'image/x-icon' }}" href="{{ $companyFaviconUrl }}">
        <link rel="shortcut icon" type="{{ $companyFaviconMime ?? 'image/x-icon' }}" href="{{ $companyFaviconUrl }}">
    @endif
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    />
    <link rel="stylesheet" href="{{ asset('assets/css/auth.css') }}">
    <style>
        :root {
            --auth-primary: {{ $authPrimaryColor ?? '#2563eb' }};
            --auth-primary-hover: {{ $authPrimaryHoverColor ?? '#1e40af' }};
            --auth-bg-from: {{ $authBackgroundFromColor ?? '#f5f7fb' }};
            --auth-bg-to: {{ $authBackgroundToColor ?? '#eaf1ff' }};
            --auth-card-bg: {{ $authCardBackgroundColor ?? '#ffffff' }};
            --auth-card-border: {{ $authCardBorderColor ?? '#d7d7d7' }};
        }
    </style>
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-left">
        <div class="auth-brand">
            @if (!empty($companyLogoUrl))
                <img src="{{ $companyLogoUrl }}" alt="{{ $appTitle }} logo" class="auth-brand-logo">
            @else
                <span class="auth-brand-dot" aria-hidden="true"></span>
            @endif
            <div>
                <strong>{{ $appTitle }}</strong>
            </div>
        </div>
        <h2>Create New Password</h2>
        <p class="auth-brand-tagline">{{ $loginTagline }}</p>
        <p class="auth-brand-message">
            Use a strong password to keep your account secure and easy to recover in the future.
        </p>
        <img src="{{ asset('assets/images/login.png') }}" class="illustration" alt="CRM Illustration">
    </div>

    <div class="auth-right">
        <div class="auth-card">
            <h3>Set New Password</h3>
            <p class="auth-subtitle">Please confirm your email and enter your new password.</p>

            @if ($errors->any())
                <div class="auth-alert auth-alert-error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('password.store') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div class="form-group">
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email', $request->email) }}"
                        placeholder="Email Address"
                        required
                        autocomplete="username"
                    >
                    @error('email')
                        <p class="auth-error-text">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group password-wrapper">
                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="New Password"
                        required
                        autocomplete="new-password"
                    >
                    <i class="fa fa-eye toggle-password" data-password-target="password" aria-hidden="true"></i>
                    @error('password')
                        <p class="auth-error-text">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group password-wrapper">
                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        placeholder="Confirm New Password"
                        required
                        autocomplete="new-password"
                    >
                    <i class="fa fa-eye toggle-password" data-password-target="password_confirmation" aria-hidden="true"></i>
                    @error('password_confirmation')
                        <p class="auth-error-text">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn-primary">Update Password</button>
            </form>

            <p style="margin-top:14px;text-align:right;">
                <a href="{{ route('login') }}" class="auth-inline-link">Back to Sign In</a>
            </p>

            <p class="footer-text">{{ $appTitle }} &copy; {{ now()->year }}. {{ $loginFooterSuffix }}</p>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.toggle-password').forEach(function (toggle) {
        toggle.addEventListener('click', function () {
            const targetId = this.getAttribute('data-password-target');
            const input = document.getElementById(targetId);
            if (!input) {
                return;
            }

            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    });
</script>
</body>
</html>

