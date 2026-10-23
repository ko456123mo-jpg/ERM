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
      <h1 data-i18n="apps">Applications</h1>
      
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

    @if ($errors->any())
      <div class="error-alert">
        {{ $errors->first() }}
      </div>
    @endif

    <!-- Action Bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
      <p style="color: var(--text-muted); font-size: 0.875rem;">Manage uploaded Android APK binaries and execute MobSF static security scans.</p>
      @if(auth()->user()->hasPermission('applications.upload'))
        <button class="btn-action" onclick="openUploadModal()">+ Upload New APK</button>
      @endif
    </div>

    <!-- Applications Table Container -->
    <div class="table-container">
      <div class="table-header">
        <h3>{{ auth()->user()->isStandardUser() ? 'My Applications' : 'Uploaded Applications' }}</h3>
        <span style="font-size: 0.75rem; color: var(--purple-light); font-weight: 700; font-family: var(--font-tech);">
          TOTAL: {{ $applications->count() }}
        </span>
      </div>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Application Name</th>
            <th>Original Filename</th>
            <th>SHA-256 Hash</th>
            <th>File Size</th>
            <th>Status</th>
            <th>Latest Risk</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($applications as $app)
            @php $latest = $app->latestAnalysis; @endphp
            <tr>
              <td>#{{ $app->id }}</td>
              <td style="font-weight: 600;">
                <a href="{{ route('applications.show', $app->id) }}" style="color: #ffffff; text-decoration: none;">
                  {{ $app->name }}
                </a>
              </td>
              <td style="font-size: 0.8125rem; color: var(--text-muted);">{{ $app->original_filename }}</td>
              <td style="font-family: var(--font-tech); font-size: 0.75rem; color: var(--purple-light);" title="{{ $app->sha256 }}">
                {{ substr($app->sha256, 0, 16) }}...
              </td>
              <td>{{ number_format($app->file_size / 1024 / 1024, 2) }} MB</td>
              <td>
                @if ($app->status === 'completed')
                  <span class="role-tag" style="background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3);">Completed</span>
                @elseif ($app->status === 'failed')
                  <span class="role-tag" style="background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);">Failed</span>
                @elseif ($app->status === 'analyzing')
                  <span class="role-tag" style="background: rgba(234, 179, 8, 0.15); color: #facc15; border: 1px solid rgba(234, 179, 8, 0.3);">Analyzing...</span>
                @else
                  <span class="role-tag" style="background: rgba(148, 163, 184, 0.15); color: #cbd5e1; border: 1px solid rgba(148, 163, 184, 0.3);">Uploaded</span>
                @endif
              </td>
              <td>
                @if ($latest && $latest->status === 'completed')
                  <span class="role-tag" style="
                    @if($latest->risk_level === 'High' || $latest->risk_level === 'Critical') background: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.4);
                    @elseif($latest->risk_level === 'Medium') background: rgba(234, 179, 8, 0.2); color: #fef08a; border: 1px solid rgba(234, 179, 8, 0.4);
                    @else background: rgba(34, 197, 94, 0.2); color: #86efac; border: 1px solid rgba(34, 197, 94, 0.4); @endif
                  ">
                    {{ $latest->risk_level }} ({{ $latest->score }}/100)
                  </span>
                @elseif ($latest && $latest->status === 'failed')
                  <span style="font-size: 0.75rem; color: #f87171;" title="{{ $latest->error_message }}">MobSF Failed</span>
                @else
                  <span style="font-size: 0.75rem; color: var(--text-muted);">N/A</span>
                @endif
              </td>
              <td style="display: flex; gap: 0.4rem;">
                <a href="{{ route('applications.show', $app->id) }}" class="btn-action" style="text-decoration: none;">View Report</a>
                @if(auth()->user()->hasPermission('applications.delete'))
                  <form action="{{ route('applications.destroy', $app->id) }}" method="POST" onsubmit="return confirm('Delete this application permanently?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-action btn-danger">Delete</button>
                  </form>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">
                No Android APK applications uploaded yet. Click "+ Upload New APK" to begin static analysis.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </main>
</div>

<!-- Upload APK Drag & Drop Modal -->
<div id="uploadModal" class="modal-overlay hidden">
  <div class="modal-card" style="max-width: 520px;">
    <h3 style="margin-bottom: 0.5rem;">Upload APK for MobSF Analysis</h3>
    <p style="color: var(--text-muted); font-size: 0.8125rem; margin-bottom: 1.25rem;">
      Select or drag an Android APK file to calculate SHA-256 and execute MobSF static security scan.
    </p>

    <div id="modalUploadError" class="error-alert hidden"></div>

    <form id="uploadApkForm" action="{{ route('applications.store') }}" method="POST" enctype="multipart/form-data">
      @csrf
      <div class="form-group">
        <label>Application Custom Name (Optional)</label>
        <input type="text" id="appName" name="name" placeholder="e.g. Banking App Security Release" />
      </div>

      <div class="form-group">
        <label>Select APK File (.apk only, Max 100MB)</label>
        <div id="dropZone" style="border: 2px dashed var(--border-accent); border-radius: 0.75rem; padding: 2rem 1.5rem; text-align: center; background: rgba(9, 10, 15, 0.85); cursor: pointer; transition: all 0.2s;">
          <svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="var(--purple-light)" stroke-width="2" style="margin-bottom: 0.5rem;">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="17 8 12 3 7 8"></polyline>
            <line x1="12" y1="3" x2="12" y2="15"></line>
          </svg>
          <p id="dropFileName" style="font-weight: 600; font-size: 0.875rem;">Click to browse or drag & drop APK file here</p>
          <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">Validated by SHA-256 & stored in secure private storage</p>
          <input type="file" id="apkFileInput" name="apk_file" accept=".apk" required style="display: none;" />
        </div>
      </div>

      <div style="display: flex; gap: 0.5rem; margin-top: 1.5rem;">
        <button type="submit" id="submitUploadBtn" class="btn-submit" style="flex: 1;">Start MobSF Scan</button>
        <button type="button" class="btn-action btn-danger" onclick="closeUploadModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>
@endsection

@section('scripts')
<script>
  const dropZone = document.getElementById('dropZone');
  const fileInput = document.getElementById('apkFileInput');
  const dropFileName = document.getElementById('dropFileName');

  if (dropZone && fileInput) {
    dropZone.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', () => {
      if (fileInput.files.length > 0) {
        dropFileName.innerText = `Selected: ${fileInput.files[0].name} (${(fileInput.files[0].size / 1024 / 1024).toFixed(2)} MB)`;
        dropZone.style.borderColor = 'var(--purple-primary)';
      }
    });

    ['dragenter', 'dragover'].forEach(evtName => {
      dropZone.addEventListener(evtName, (e) => {
        e.preventDefault();
        dropZone.style.borderColor = 'var(--purple-light)';
        dropZone.style.background = 'rgba(168, 85, 247, 0.15)';
      });
    });

    ['dragleave', 'drop'].forEach(evtName => {
      dropZone.addEventListener(evtName, (e) => {
        e.preventDefault();
        dropZone.style.borderColor = 'var(--border-accent)';
        dropZone.style.background = 'rgba(9, 10, 15, 0.85)';
      });
    });

    dropZone.addEventListener('drop', (e) => {
      if (e.dataTransfer.files.length > 0) {
        fileInput.files = e.dataTransfer.files;
        dropFileName.innerText = `Selected: ${fileInput.files[0].name} (${(fileInput.files[0].size / 1024 / 1024).toFixed(2)} MB)`;
      }
    });
  }

  function openUploadModal() {
    document.getElementById("modalUploadError").classList.add("hidden");
    document.getElementById("uploadApkForm").reset();
    dropFileName.innerText = 'Click to browse or drag & drop APK file here';
    document.getElementById("uploadModal").classList.remove("hidden");
  }

  function closeUploadModal() {
    document.getElementById("uploadModal").classList.add("hidden");
  }

  document.getElementById("uploadApkForm")?.addEventListener("submit", function() {
    const btn = document.getElementById("submitUploadBtn");
    btn.innerText = "Processing & Scanning...";
    btn.disabled = true;
  });
</script>
@endsection
