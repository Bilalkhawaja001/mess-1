@extends('layouts.auth')

@section('title', 'Login - Admin Mess')

@section('auth_visual')
    <img src="{{ asset('branding/admin-mess-login-logo.png') }}" alt="Admin Mess" class="login-brand-image">

<a href="https://nodesky.pk"
   target="_blank"
   rel="noopener noreferrer"
   class="nodesky-top-left-brand">
    <img src="{{ asset('images/nodesky-logo.png') }}"
         alt="NodeSky Technologies">
</a>

<style>
.nodesky-top-left-brand {
    position: absolute;
    top: 24px;
    left: 26px;
    z-index: 9999;
    display: block;
}

.nodesky-top-left-brand img {
    width: 125px;
    height: auto;
    display: block;
}

@media (max-width: 768px) {
    .nodesky-top-left-brand {
        top: 24px;
        left: 18px;
    }

    .nodesky-top-left-brand img {
        width: 95px;
    }
}
</style>

@endsection

@push('styles')
<style>
    .login-brand-image { display: block; width: min(100%, 470px); height: auto; object-fit: contain; }
    .login-header { margin-bottom: 2rem; }
    .login-kicker { margin-bottom: .65rem; color: #ff5a1f; font-size: .72rem; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
    .login-title { margin: 0 0 .55rem; color: #0f172a; font-size: clamp(1.7rem, 3vw, 2.15rem); font-weight: 800; letter-spacing: -.035em; }
    .login-subtitle { margin: 0; color: #64748b; font-size: .9rem; line-height: 1.6; }
    .login-form { --bs-gutter-y: 1.15rem; }
    .login-form .form-label { margin-bottom: .45rem; color: #334155; font-size: .8rem; font-weight: 700; }
    .login-form .form-control { min-height: 48px; padding: .72rem .9rem; border: 1px solid #dfe6ef; border-radius: 11px; color: #0f172a; background: #fff; box-shadow: none; font-size: .9rem; }
    .login-form .form-control::placeholder { color: #94a3b8; }
    .login-form .form-control:focus { border-color: #ff6b35; box-shadow: 0 0 0 3px rgba(255, 107, 53, .13); }
    .login-password-wrap { position: relative; }
    .login-password-wrap .form-control { padding-right: 46px; }
    .login-password-toggle { position: absolute; top: 50%; right: 10px; width: 32px; height: 32px; padding: 0; transform: translateY(-50%); display: inline-grid; place-items: center; border: 0; border-radius: 8px; color: #64748b; background: transparent; }
    .login-password-toggle:hover { color: #0f172a; background: #f1f5f9; }
    .login-options { display: flex; align-items: center; justify-content: space-between; gap: 14px; }
    .login-options .form-check-label { color: #475569; font-size: .8rem; }
    .login-options .form-check-input:checked { border-color: #ff6b35; background-color: #ff6b35; }
    .login-recovery-link { color: #0f3a73; font-size: .8rem; font-weight: 700; text-decoration: none; }
    .login-recovery-link:hover { color: #ff5a1f; }
    .login-submit { min-height: 48px; border: 0; border-radius: 11px; color: #fff; background: #0f3a73; font-size: .9rem; font-weight: 800; box-shadow: 0 10px 22px rgba(15, 58, 115, .18); }
    .login-submit:hover, .login-submit:focus { color: #fff; background: #0a2c59; }
    .login-footer { margin-top: 2rem; text-align: center; }
    .login-footer-links { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px 15px; margin-bottom: .8rem; font-size: .74rem; }
    .login-footer-links a { color: #64748b; text-decoration: none; }
    .login-footer-links a:hover { color: #ff5a1f; }
    .login-powered { margin: 0; color: #94a3b8; font-size: .72rem; }
    .login-powered strong { color: #475569; }
    @media (max-width: 900px) { .login-brand-image { width: min(100%, 290px); } .login-header { margin-bottom: 1.5rem; } }
    @media (max-width: 575.98px) { .login-options { align-items: flex-start; } .login-footer { margin-top: 1.5rem; } }
</style>
@endpush

@section('auth_content')
<div class="login-header">
    <div class="login-kicker">Admin Mess Portal</div>
    <h1 class="login-title">Welcome back</h1>
    <p class="login-subtitle">Sign in to continue to the mess management system.</p>
</div>

@include('partials.flash')

<form method="POST" action="{{ route('login.attempt') }}" class="row login-form">
    @csrf
    <div class="col-12">
        <label for="usernameInput" class="form-label">Username</label>
        <input type="text" name="username" id="usernameInput" value="{{ old('username') }}" class="form-control" placeholder="Enter your username" autocomplete="username" required autofocus>
    </div>
    <div class="col-12">
        <label for="passwordInput" class="form-label">Password</label>
        <div class="login-password-wrap">
            <input type="password" name="password" id="passwordInput" class="form-control" placeholder="Enter your password" autocomplete="current-password" required>
            <button type="button" class="login-password-toggle" id="passwordToggle" aria-label="Show password">
                <i class="bi bi-eye" id="passwordToggleIcon" aria-hidden="true"></i>
            </button>
        </div>
    </div>
    <div class="col-12 login-options">
        <div class="form-check mb-0">
            <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
            <label class="form-check-label" for="remember">Remember me</label>
        </div>
        <a href="{{ route('password-reset.request.form') }}" class="login-recovery-link">Forgot password?</a>
    </div>
    @if(config('turnstile.enabled'))
    <div class="col-12">
        <div class="cf-turnstile" data-sitekey="{{ config('turnstile.site_key') }}" data-theme="light"></div>
        @error('turnstile')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
    @endif
    <div class="col-12"><button class="btn login-submit w-100" type="submit">Sign in</button></div>
</form>

<footer class="login-footer">
    <nav class="login-footer-links" aria-label="Legal links">
        <a href="{{ route('privacy') }}">Privacy Policy</a>
        <a href="{{ route('data-deletion') }}">Data Deletion</a>
        <a href="{{ url('/terms-and-conditions') }}"
           style="margin-left:12px;">
            Terms &amp; Conditions
        </a>
    
        <a href="{{ url('/refund-and-cancellation-policy') }}">
            Refund &amp; Cancellation
        </a>
        <a href="{{ url('/business-information') }}">
            Business &amp; Payment Info
        </a>

    </nav>
    <p class="login-powered">Powered by <strong>NodeSky Technologies</strong></p>
</footer>
@endsection

@push('scripts')
@if(config('turnstile.enabled'))
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endif
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const passwordInput = document.getElementById('passwordInput');
        const passwordToggle = document.getElementById('passwordToggle');
        const passwordToggleIcon = document.getElementById('passwordToggleIcon');
        if (!passwordInput || !passwordToggle || !passwordToggleIcon) return;
        passwordToggle.addEventListener('click', () => {
            const showPassword = passwordInput.type === 'password';
            passwordInput.type = showPassword ? 'text' : 'password';
            passwordToggle.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
            passwordToggleIcon.className = showPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    });
</script>
@endpush
