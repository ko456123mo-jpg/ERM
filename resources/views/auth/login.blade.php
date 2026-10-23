@extends('layouts.app')

@section('content')
<div class="login-container-split">
  
  <!-- LEFT COLUMN: HERO BRAND CANVAS -->
  <div class="hero-brand-canvas">
    
    <!-- DOMINANT ANIMATED GEOMETRIC ERM TYPOGRAPHY LOGO -->
    <div class="hero-logo-box">
      <svg viewBox="0 0 550 200" width="100%" height="100%" class="erm-custom-type-logo">
        <defs>
          <filter id="purple-glow-heavy" x="-30%" y="-30%" width="160%" height="160%">
            <feGaussianBlur stdDeviation="6" result="blur" />
            <feComposite in="SourceGraphic" in2="blur" operator="over" />
          </filter>
        </defs>

        <!-- Technical Circuit Guide Line -->
        <line x1="40" y1="170" x2="510" y2="170" stroke="rgba(168, 85, 247, 0.35)" stroke-dasharray="4 4" stroke-width="1.2" />
        <circle cx="40" cy="170" r="3.5" fill="#a855f7" />
        <circle cx="510" cy="170" r="3.5" fill="#a855f7" />

        <!-- TECHNICAL PARTICLES & DOTS -->
        <circle cx="90" cy="25" r="2.5" fill="#c084fc" class="tech-dot dot-1" />
        <circle cx="230" cy="20" r="3" fill="#a855f7" class="tech-dot dot-2" />
        <circle cx="390" cy="22" r="2" fill="#e9d5ff" class="tech-dot dot-3" />
        <circle cx="480" cy="140" r="3" fill="#9333ea" class="tech-dot dot-4" />

        <!-- CUSTOM GEOMETRIC ERM TYPOGRAPHY GROUP -->
        <g transform="translate(45, 25)">

          <!-- LETTER E: Spine + 3 Horizontal Bars (Never reads as F) -->
          <g class="letter-e">
            <path class="erm-type-segment e-spine" d="M 10 10 L 35 10 L 35 125 L 10 125 Z" />
            <path class="erm-type-segment e-top" d="M 38 10 L 115 10 L 100 35 L 38 35 Z" />
            <path class="erm-type-segment e-mid" d="M 38 55 L 95 55 L 85 78 L 38 78 Z" />
            <path class="erm-type-segment e-bot" d="M 38 100 L 120 100 L 105 125 L 38 125 Z" />
          </g>

          <!-- LETTER R: Spine + Upper Cut Loop + Diagonal Kick Leg -->
          <g class="letter-r" transform="translate(155, 0)">
            <path class="erm-type-segment r-spine" d="M 10 10 L 35 10 L 35 125 L 10 125 Z" />
            <path class="erm-type-segment r-loop" d="M 38 10 L 105 10 C 135 10, 145 28, 145 46 C 145 64, 130 75, 100 75 L 38 75 L 38 52 L 95 52 C 108 52, 118 46, 118 36 C 118 26, 108 24, 92 24 L 38 24 Z" />
            <path class="erm-type-segment r-leg" d="M 68 76 L 102 76 L 142 125 L 112 125 L 78 88 Z" />
          </g>

          <!-- LETTER M: Left Stem + Central Double Chevron + Right Stem -->
          <g class="letter-m" transform="translate(325, 0)">
            <path class="erm-type-segment m-left" d="M 10 10 L 32 10 L 32 125 L 10 125 Z" />
            <path class="erm-type-segment m-diag1" d="M 35 10 L 62 10 L 82 85 L 60 92 Z" />
            <path class="erm-type-segment m-diag2" d="M 82 85 L 102 10 L 129 10 L 104 92 Z" />
            <path class="erm-type-segment m-right" d="M 132 10 L 155 10 L 155 125 L 132 125 Z" />
          </g>

        </g>
      </svg>
    </div>

    <!-- BRAND TYPOGRAPHY & DESCRIPTIONS -->
    <h1 class="hero-title" data-i18n="platform_name">Engineering Reverse Mobile</h1>
    <h2 class="hero-subtitle" data-i18n="platform_desc">Enterprise Mobile Security Analysis Platform</h2>
    <p class="hero-description" data-i18n="platform_summary">
      On-Premise Android Reverse Engineering, Dynamic Instrumenting & Static Binary Security Platform.
    </p>

    <!-- TECHNICAL TELEMETRY CARDS -->
    <div class="telemetry-grid">
      <div class="telemetry-card">
        <span data-i18n="t_node">CORE ENGINE</span>
        <p>v1.4.0 &bull; ACTIVE</p>
      </div>
      <div class="telemetry-card">
        <span data-i18n="t_sec">SECURITY LEVEL</span>
        <p>ARGON2ID HARDENED</p>
      </div>
      <div class="telemetry-card">
        <span data-i18n="t_arch">ARCHITECTURE</span>
        <p>ANDROID REVERSE</p>
      </div>
      <div class="telemetry-card">
        <span data-i18n="t_status">ENVIRONMENT</span>
        <p>ON-PREMISE CORE</p>
      </div>
    </div>

  </div>

  <!-- RIGHT COLUMN: ENTERPRISE LOGIN CARD -->
  <div class="card-login-enterprise">

    <!-- TOP HEADER CONTROLS -->
    <div class="card-top-controls">
      <div class="card-title-group">
        <h2 data-i18n="auth_portal">AUTHENTICATION PORTAL</h2>
        <p data-i18n="auth_sub">Sign in with your enterprise security credentials</p>
      </div>

      <div style="display: flex; gap: 0.75rem; align-items: center;">
        <button id="audioToggleBtn" class="audio-btn" title="Toggle Welcome Audio">
          <svg id="audioIcon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
            <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
            <path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path>
          </svg>
        </button>

        <select id="langSelect" class="lang-selector">
          <option value="en">English (LTR)</option>
          <option value="ar">العربية (RTL)</option>
        </select>
      </div>
    </div>

    @if ($errors->any())
      <div class="error-alert">
        {{ $errors->first() }}
      </div>
    @endif

    <form id="loginForm" action="{{ route('login') }}" method="POST">
      @csrf
      <div class="form-group">
        <label for="username" data-i18n="username">Username</label>
        <div class="input-wrapper">
          <input type="text" id="username" name="username" value="{{ old('username') }}" placeholder="Enter your username" required autofocus />
          <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
        </div>
      </div>

      <div class="form-group">
        <label for="password" data-i18n="password">Password</label>
        <div class="input-wrapper">
          <input type="password" id="password" name="password" placeholder="Enter your password" required />
          <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
          </svg>
          <button type="button" class="toggle-password" id="toggleBtn">
            <svg id="eyeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
              <circle cx="12" cy="12" r="3"></circle>
            </svg>
          </button>
        </div>
      </div>

      <div class="remember-row">
        <input type="checkbox" id="remember" name="remember" class="checkbox-custom" />
        <label for="remember" class="remember-label" data-i18n="remember">Remember me</label>
      </div>

      <button type="submit" id="submitBtn" class="btn-submit" data-i18n="signin">Sign In</button>
    </form>

    <div class="notice">
      <p data-i18n="authorized_only">Authorized personnel only • Restricted Enterprise Access</p>
    </div>

  </div>
</div>
@endsection

@section('scripts')
<script>
  const toggleBtn = document.getElementById('toggleBtn');
  const passwordInput = document.getElementById('password');
  const eyeIcon = document.getElementById('eyeIcon');
  let visible = false;

  if (toggleBtn) {
    toggleBtn.addEventListener('click', () => {
      visible = !visible;
      passwordInput.type = visible ? 'text' : 'password';
      eyeIcon.innerHTML = visible 
        ? `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>`
        : `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>`;
    });
  }

  // Trigger welcome voice ONCE if session flag set
  document.getElementById("loginForm").addEventListener("submit", () => {
    sessionStorage.setItem("play_welcome_voice", "true");
  });
</script>
@endsection
