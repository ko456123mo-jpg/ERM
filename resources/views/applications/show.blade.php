@extends('layouts.app')

@section('content')
<div class="app-layout">
  <!-- Sidebar Navigation -->
  <aside class="sidebar">
    <div class="sidebar-brand">
      <div style="width: 180px; height: 50px;">
        <svg viewBox="0 0 550 160" width="100%" height="100%" class="erm-custom-type-logo">
          <g transform="translate(45, 15)">
            <g class="letter-e">
              <path class="erm-type-segment e-spine" d="M 10 10 L 35 10 L 35 125 L 10 125 Z" />
              <path class="erm-type-segment e-top" d="M 38 10 L 115 10 L 100 35 L 38 35 Z" />
              <path class="erm-type-segment e-mid" d="M 38 55 L 95 55 L 85 78 L 38 78 Z" />
              <path class="erm-type-segment e-bot" d="M 38 100 L 120 100 L 105 125 L 38 125 Z" />
            </g>
            <g class="letter-r" transform="translate(155, 0)">
              <path class="erm-type-segment r-spine" d="M 10 10 L 35 10 L 35 125 L 10 125 Z" />
              <path class="erm-type-segment r-loop" d="M 38 10 L 105 10 C 135 10, 145 28, 145 46 C 145 64, 130 75, 100 75 L 38 75 L 38 52 L 95 52 C 108 52, 118 46, 118 36 C 118 26, 108 24, 92 24 L 38 24 Z" />
              <path class="erm-type-segment r-leg" d="M 68 76 L 102 76 L 142 125 L 112 125 L 78 88 Z" />
            </g>
            <g class="letter-m" transform="translate(325, 0)">
              <path class="erm-type-segment m-left" d="M 10 10 L 32 10 L 32 125 L 10 125 Z" />
              <path class="erm-type-segment m-diag1" d="M 35 10 L 62 10 L 82 85 L 60 92 Z" />
              <path class="erm-type-segment m-diag2" d="M 82 85 L 102 10 L 129 10 L 104 92 Z" />
              <path class="erm-type-segment m-right" d="M 132 10 L 155 10 L 155 125 L 132 125 Z" />
            </g>
          </g>
        </svg>
      </div>
      <p data-i18n="platform_tag">Cybersecurity Platform</p>
    </div>

    <ul class="nav-list">
      <li><a href="{{ route('dashboard') }}" class="nav-item"><svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg> <span data-i18n="dashboard">Dashboard</span></a></li>
      <li><a href="{{ route('applications.index') }}" class="nav-item active"><svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg> <span data-i18n="apps">Applications</span></a></li>
      @if(auth()->user()->hasRole('ADMIN'))
        <li><a href="{{ route('dashboard') }}#users" class="nav-item"><svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg> <span data-i18n="users">Users</span></a></li>
      @endif
      <li>
        <form action="{{ route('logout') }}" method="POST">
          @csrf
          <button type="submit" class="nav-item logout" style="background: none; border: none; width: 100%;">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg> <span data-i18n="logout">Logout</span>
          </button>
        </form>
      </li>
    </ul>
  </aside>

  <!-- Main Content Body -->
  <main class="main-content">
    <header class="top-bar">
      <div>
        <a href="{{ route('applications.index') }}" style="color: var(--purple-light); font-size: 0.8125rem; text-decoration: none; font-weight: 600;">&larr; Back to Applications</a>
        <h1 style="margin-top: 0.25rem;">{{ $application->name }}</h1>
      </div>
      
      <div class="top-bar-controls">
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

        <div class="user-badge">
          <span style="font-weight: 600;">{{ auth()->user()->username }}</span>
          <span class="role-tag {{ auth()->user()->hasRole('ADMIN') ? 'role-admin' : 'role-analyst' }}">
            {{ auth()->user()->role->name }}
          </span>
        </div>
      </div>
    </header>

    @if (session('success'))
      <div style="background: rgba(34, 197, 94, 0.18); border: 1px solid rgba(34, 197, 94, 0.5); color: #4ade80; padding: 0.875rem 1.25rem; border-radius: 0.75rem; font-size: 0.875rem; margin-bottom: 1.5rem;">
        {{ session('success') }}
      </div>
    @endif

    @php $analysis = $application->latestAnalysis; @endphp

    <!-- ANALYSIS STATUS HEADER BANNER -->
    @if ($analysis && $analysis->status === 'completed')
      <div style="background: rgba(34, 197, 94, 0.12); border: 1px solid rgba(34, 197, 94, 0.4); padding: 1.25rem 1.5rem; border-radius: 1rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
        <div>
          <span class="role-tag" style="background: rgba(34, 197, 94, 0.25); color: #86efac; border: 1px solid rgba(34, 197, 94, 0.5); font-size: 0.75rem;">MobSF Analysis Completed</span>
          <p style="color: #e2e8f0; font-size: 0.875rem; margin-top: 0.5rem;">
            Authoritative MobSF static security scan completed cleanly. Risk Score: <strong>{{ $analysis->score }}/100</strong> &bull; Level: <strong>{{ $analysis->risk_level }}</strong>
          </p>
        </div>
        @if(auth()->user()->hasPermission('analysis.start'))
          <form action="{{ route('applications.reanalyze', $application->id) }}" method="POST">
            @csrf
            <button type="submit" class="btn-action">Re-Run MobSF Scan</button>
          </form>
        @endif
      </div>
    @elseif ($analysis && $analysis->status === 'failed')
      <div style="background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.4); padding: 1.25rem 1.5rem; border-radius: 1rem; margin-bottom: 2rem;">
        <span class="role-tag" style="background: rgba(239, 68, 68, 0.25); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.5); font-size: 0.75rem;">MobSF Analysis Failed / Engine Unavailable</span>
        <p style="color: #fca5a5; font-size: 0.875rem; margin-top: 0.5rem; font-family: var(--font-tech);">
          ERROR: {{ $analysis->error_message ?? 'MobSF engine unreachable at configured URL.' }}
        </p>
        <p style="color: var(--text-muted); font-size: 0.8125rem; margin-top: 0.5rem;">
          No fake findings or unverified risk scores are generated when MobSF is unavailable. Ensure MobSF service is running on the FastAPI host.
        </p>
        @if(auth()->user()->hasPermission('analysis.start'))
          <div style="margin-top: 1rem;">
            <form action="{{ route('applications.reanalyze', $application->id) }}" method="POST" style="display: inline-block;">
              @csrf
              <button type="submit" class="btn-action">Retry Analysis</button>
            </form>
          </div>
        @endif
      </div>
    @else
      <div style="background: rgba(234, 179, 8, 0.12); border: 1px solid rgba(234, 179, 8, 0.4); padding: 1.25rem 1.5rem; border-radius: 1rem; margin-bottom: 2rem;">
        <span class="role-tag" style="background: rgba(234, 179, 8, 0.25); color: #fef08a; border: 1px solid rgba(234, 179, 8, 0.5); font-size: 0.75rem;">Scan Queued</span>
        <p style="color: #fef08a; font-size: 0.875rem; margin-top: 0.5rem;">
          Application binary uploaded. Awaiting execution by the FastAPI security engine.
        </p>
      </div>
    @endif

    <!-- APPLICATION METADATA CARDS -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem; margin-bottom: 2rem;">
      <div class="stat-card">
        <p>Package Name</p>
        <h3 style="font-size: 1.1rem; font-family: var(--font-tech); color: var(--purple-light);">
          {{ $application->package_name ?? 'N/A' }}
        </h3>
      </div>
      <div class="stat-card">
        <p>Version Name</p>
        <h3 style="font-size: 1.1rem; font-family: var(--font-tech);">
          {{ $application->version_name ?? 'N/A' }}
        </h3>
      </div>
      <div class="stat-card">
        <p>File Size</p>
        <h3 style="font-size: 1.1rem; font-family: var(--font-tech);">
          {{ number_format($application->file_size / 1024 / 1024, 2) }} MB
        </h3>
      </div>
      <div class="stat-card">
        <p>SHA-256 Checksum</p>
        <h3 style="font-size: 0.75rem; font-family: var(--font-tech); color: var(--purple-light); word-break: break-all;" title="{{ $application->sha256 }}">
          {{ $application->sha256 }}
        </h3>
      </div>
    </div>

    @if ($analysis && $analysis->status === 'completed')
      <!-- SECURITY FINDINGS TABLE -->
      <div class="table-container" style="margin-bottom: 2rem;">
        <div class="table-header">
          <h3>MobSF Security Findings & Vulnerabilities</h3>
          <span class="role-tag" style="background: rgba(168, 85, 247, 0.2); color: var(--purple-light); border: 1px solid var(--border-accent);">
            FINDINGS: {{ count($analysis->findings_json ?? []) }}
          </span>
        </div>
        <table>
          <thead>
            <tr>
              <th>Title / Issue</th>
              <th>Severity</th>
              <th>OWASP Category</th>
              <th>Description</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($analysis->findings_json ?? [] as $finding)
              <tr>
                <td style="font-weight: 600;">{{ $finding['title'] ?? 'Security Concern' }}</td>
                <td>
                  <span class="role-tag" style="
                    @if(($finding['severity'] ?? '') === 'high' || ($finding['severity'] ?? '') === 'critical') background: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.4);
                    @elseif(($finding['severity'] ?? '') === 'warning' || ($finding['severity'] ?? '') === 'medium') background: rgba(234, 179, 8, 0.2); color: #fef08a; border: 1px solid rgba(234, 179, 8, 0.4);
                    @else background: rgba(34, 197, 94, 0.2); color: #86efac; border: 1px solid rgba(34, 197, 94, 0.4); @endif
                  ">
                    {{ strtoupper($finding['severity'] ?? 'INFO') }}
                  </span>
                </td>
                <td style="font-family: var(--font-tech); font-size: 0.75rem; color: var(--purple-light);">
                  {{ $finding['owasp'] ?? 'M1: Improper Credential Usage' }}
                </td>
                <td style="font-size: 0.8125rem; color: #cbd5e1;">
                  {{ $finding['description'] ?? 'Potential security risk identified during binary disassembly.' }}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                  No critical security vulnerabilities flagged by MobSF.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <!-- PERMISSIONS BREAKDOWN -->
      <div class="table-container" style="margin-bottom: 2rem;">
        <div class="table-header">
          <h3>Android Manifest Permissions</h3>
          <span style="font-size: 0.75rem; color: var(--purple-light); font-weight: 700;">
            DECLARED: {{ count($analysis->permissions_json ?? []) }}
          </span>
        </div>
        <table>
          <thead>
            <tr>
              <th>Permission Name</th>
              <th>Status / Level</th>
              <th>Info</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($analysis->permissions_json ?? [] as $permKey => $permVal)
              @php 
                $pName = is_array($permVal) ? ($permVal['name'] ?? $permKey) : $permVal;
                $pStatus = is_array($permVal) ? ($permVal['status'] ?? 'dangerous') : 'normal';
              @endphp
              <tr>
                <td style="font-family: var(--font-tech); font-size: 0.8125rem; font-weight: 600;">{{ $pName }}</td>
                <td>
                  <span class="role-tag" style="
                    @if($pStatus === 'dangerous') background: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.4);
                    @else background: rgba(148, 163, 184, 0.15); color: #cbd5e1; border: 1px solid rgba(148, 163, 184, 0.3); @endif
                  ">
                    {{ strtoupper($pStatus) }}
                  </span>
                </td>
                <td style="font-size: 0.8125rem; color: var(--text-muted);">
                  Declared in AndroidManifest.xml
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                  No Android permissions declared in manifest.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    @endif
  </main>
</div>
@endsection
