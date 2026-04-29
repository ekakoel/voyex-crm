<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php($appTitle = trim((string) ($companyName ?? config('app.name', 'VOYEX CRM'))))
    @php($loginTagline = trim((string) ($companyTagline ?? 'Smart Travel CRM Platform')))
    @php($loginFooterSuffix = trim((string) ($companyFooterNote ?? '')) ?: 'All rights reserved.')
    <title>{{ ui_phrase('Forgot Password') }} | {{ $appTitle }}</title>
    @if (!empty($companyFaviconUrl))
        <link rel="icon" type="{{ $companyFaviconMime ?? 'image/x-icon' }}" href="{{ $companyFaviconUrl }}">
        <link rel="shortcut icon" type="{{ $companyFaviconMime ?? 'image/x-icon' }}" href="{{ $companyFaviconUrl }}">
    @endif
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
    @include('auth.partials.inline-auth-styles')
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-left auth-left--security">
        <div class="auth-brand">
            @if (!empty($companyLogoUrl))
                <img src="{{ $companyLogoUrl }}" alt="{{ $appTitle }} logo" class="auth-brand-logo" loading="lazy" decoding="async">
            @else
                <span class="auth-brand-dot" aria-hidden="true"></span>
            @endif
            <div>
                <strong>{{ $appTitle }}</strong>
            </div>
        </div>
        <h2>{{ ui_phrase('Reset Password') }}</h2>
        <p class="auth-brand-tagline">{{ $loginTagline }}</p>
        <p class="auth-brand-message">
            {{ ui_phrase('Enter your registered email address, and we will send a secure password reset link.') }}
        </p>
        <ol class="auth-context-list auth-context-list--steps">
            <li>{{ ui_phrase('Input your registered email address.') }}</li>
            <li>{{ ui_phrase('Open the reset link sent to your inbox.') }}</li>
            <li>{{ ui_phrase('Create a new password and sign in again securely.') }}</li>
        </ol>
    </div>

    <div class="auth-right">
        <div class="auth-card">
            <h3>{{ ui_phrase('Forgot Password') }}</h3>
            <p class="auth-subtitle">{{ ui_phrase('Use the same email address registered in the system.') }}</p>

            @if (session('status'))
                <div class="auth-alert auth-alert-success">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="auth-alert auth-alert-error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" data-auth-submit-form>
                @csrf
                <div class="form-group">
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="{{ ui_phrase('Email Address') }}"
                        required
                        autofocus
                        autocomplete="username"
                    >
                </div>
                @error('email')
                    <p class="auth-error-text">{{ $message }}</p>
                @enderror

                <button type="submit" class="btn-primary" data-auth-submit-btn data-loading-text="{{ ui_phrase('Sending Link...') }}">
                    <span class="auth-btn-spinner" aria-hidden="true"></span>
                    <span data-auth-btn-label>{{ ui_phrase('Send Reset Link') }}</span>
                </button>
            </form>

            <p style="margin-top:14px;text-align:right;">
                <a href="{{ route('login') }}" class="auth-inline-link">{{ ui_phrase('Back to Sign In') }}</a>
            </p>

            <p class="footer-text">{{ $appTitle }} &copy; {{ now()->year }}. {{ $loginFooterSuffix }}</p>
        </div>
    </div>
</div>
<div class="auth-page-spinner" data-auth-page-spinner aria-hidden="true">
    <div class="auth-page-spinner__inner">
        <div class="auth-page-spinner__ring" aria-hidden="true"></div>
        <div class="auth-page-spinner__text">{{ ui_phrase('Processing...') }}</div>
    </div>
</div>
<script>
    (() => {
        const form = document.querySelector('[data-auth-submit-form]');
        const spinner = document.querySelector('[data-auth-page-spinner]');
        if (!form) {
            return;
        }

        let submitting = false;

        form.addEventListener('submit', (event) => {
            if (submitting) {
                event.preventDefault();
                return;
            }
            submitting = true;

            const button = form.querySelector('[data-auth-submit-btn]');
            if (button) {
                button.disabled = true;
                button.classList.add('is-loading');

                const label = button.querySelector('[data-auth-btn-label]');
                if (label && !label.dataset.originalText) {
                    label.dataset.originalText = label.textContent || '';
                }
                if (label) {
                    label.textContent = button.getAttribute('data-loading-text') || @json(ui_phrase('Loading...'));
                }
            }

            if (spinner) {
                spinner.classList.add('is-visible');
                spinner.setAttribute('aria-hidden', 'false');
            }
        });

        window.addEventListener('pageshow', () => {
            submitting = false;
            if (spinner) {
                spinner.classList.remove('is-visible');
                spinner.setAttribute('aria-hidden', 'true');
            }
        });
    })();
</script>
</body>
</html>
