<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php($appTitle = trim((string) ($companyName ?? config('app.name', 'VOYEX CRM'))))
    @php($loginTagline = trim((string) ($companyTagline ?? 'Smart Travel CRM Platform')))
    @php($loginFooterSuffix = trim((string) ($companyFooterNote ?? '')) ?: 'All rights reserved.')
    <title>{{ __('ui.auth.titles.forgot_password_page') }} | {{ $appTitle }}</title>
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
        <h2>{{ __('ui.auth.branding.reset_password') }}</h2>
        <p class="auth-brand-tagline">{{ $loginTagline }}</p>
        <p class="auth-brand-message">
            {{ __('ui.auth.branding.forgot_message') }}
        </p>
        <ol class="auth-context-list auth-context-list--steps">
            <li>{{ __('ui.auth.branding.forgot_step_1') }}</li>
            <li>{{ __('ui.auth.branding.forgot_step_2') }}</li>
            <li>{{ __('ui.auth.branding.forgot_step_3') }}</li>
        </ol>
    </div>

    <div class="auth-right">
        <div class="auth-card">
            <h3>{{ __('ui.auth.titles.forgot_password_page') }}</h3>
            <p class="auth-subtitle">{{ __('ui.auth.subtitles.forgot_password') }}</p>

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
                        placeholder="{{ __('ui.auth.forms.email_address') }}"
                        required
                        autofocus
                        autocomplete="username"
                    >
                </div>
                @error('email')
                    <p class="auth-error-text">{{ $message }}</p>
                @enderror

                <button type="submit" class="btn-primary" data-auth-submit-btn data-loading-text="{{ __('ui.auth.forms.sending_link') }}">
                    <span class="auth-btn-spinner" aria-hidden="true"></span>
                    <span data-auth-btn-label>{{ __('ui.auth.forms.send_reset_link') }}</span>
                </button>
            </form>

            <p style="margin-top:14px;text-align:right;">
                <a href="{{ route('login') }}" class="auth-inline-link">{{ __('ui.auth.forms.back_to_sign_in') }}</a>
            </p>

            <p class="footer-text">{{ $appTitle }} &copy; {{ now()->year }}. {{ $loginFooterSuffix }}</p>
        </div>
    </div>
</div>
<div class="auth-page-spinner" data-auth-page-spinner aria-hidden="true">
    <div class="auth-page-spinner__inner">
        <div class="auth-page-spinner__ring" aria-hidden="true"></div>
        <div class="auth-page-spinner__text">{{ __('ui.auth.spinner.processing') }}</div>
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
                    label.textContent = button.getAttribute('data-loading-text') || @json(__('ui.auth.forms.loading'));
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
