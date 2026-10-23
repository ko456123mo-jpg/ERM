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

    <ul class="nav-list" id="sidebarNav">
      <li><a href="javascript:void(0)" onclick="switchTab('dashboard')" class="nav-item active" id="nav-dashboard"><svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg> <span data-i18n="dashboard">Dashboard</span></a></li>
      <li id="tab-nav-users"><a href="javascript:void(0)" onclick="switchTab('users')" class="nav-item" id="nav-users"><svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg> <span data-i18n="users">Users</span></a></li>
      <li id="tab-nav-apps"><a href="{{ route('applications.index') }}" class="nav-item" id="nav-apps"><svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg> <span data-i18n="apps">Applications</span></a></li>
      <li id="tab-nav-reports"><a href="javascript:void(0)" onclick="switchTab('reports')" class="nav-item" id="nav-reports"><svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg> <span data-i18n="reports">Reports</span></a></li>
      <li id="tab-nav-tools"><a href="javascript:void(0)" onclick="switchTab('tools')" class="nav-item" id="nav-tools"><svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg> <span>Tools</span></a></li>
      <li id="tab-nav-ai"><a href="javascript:void(0)" onclick="switchTab('ai')" class="nav-item" id="nav-ai"><svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"></rect><circle cx="12" cy="5" r="2"></circle><path d="M12 7v4"></path><line x1="8" y1="16" x2="8" y2="16"></line><line x1="16" y1="16" x2="16" y2="16"></line></svg> <span>AI Assistant</span></a></li>
      <li id="tab-nav-roles"><a href="javascript:void(0)" onclick="switchTab('roles')" class="nav-item" id="nav-roles"><svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg> <span data-i18n="roles">Roles & Permissions</span></a></li>
      <li id="tab-nav-audit"><a href="javascript:void(0)" onclick="switchTab('audit')" class="nav-item" id="nav-audit"><svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="M16 13H8"></path><path d="M16 17H8"></path><path d="M10 9H8"></path></svg> <span data-i18n="audit">Audit Logs</span></a></li>
      <li id="tab-nav-settings"><a href="javascript:void(0)" onclick="switchTab('settings')" class="nav-item" id="nav-settings"><svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg> <span data-i18n="settings">Settings</span></a></li>
      <li id="tab-nav-profile"><a href="javascript:void(0)" onclick="switchTab('profile')" class="nav-item" id="nav-profile"><svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg> <span>Profile</span></a></li>
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
      <h1 id="pageTitle" data-i18n="dashboard">Dashboard</h1>

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
          <span style="font-weight: 600;" id="userBadgeName">{{ auth()->user()->username }}</span>
          <span class="role-tag role-admin" id="userBadgeRole">{{ auth()->user()->role?->name ?? 'USER' }}</span>
        </div>
      </div>
    </header>

    <!-- Global Success Notification Banner -->
    <div id="flashSuccess" class="hidden" style="background: rgba(34, 197, 94, 0.18); border: 1px solid rgba(34, 197, 94, 0.5); color: #4ade80; padding: 0.875rem 1.25rem; border-radius: 0.75rem; font-size: 0.875rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
      <span id="flashSuccessMsg">Action completed successfully.</span>
      <button onclick="hideFlash()" style="background: none; border: none; color: #4ade80; cursor: pointer; font-weight: bold; font-size: 1.1rem; line-height: 1;">&times;</button>
    </div>

    <!-- Stats Grid -->
    <section id="statsSection" class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
      <div class="stat-card">
        <p data-i18n="stat_users">Total Users</p>
        <h3 id="statUsers">0</h3>
      </div>
      <div class="stat-card">
        <p data-i18n="stat_analysts">Active Analysts</p>
        <h3 id="statAnalysts">0</h3>
      </div>
      <div class="stat-card">
        <p>Applications</p>
        <h3 id="statApps">0</h3>
      </div>
      <div class="stat-card">
        <p>Completed Scans</p>
        <h3 id="statScans">0</h3>
      </div>
      <div class="stat-card">
        <p>High Risk</p>
        <h3 id="statHighRisk" style="color: #f87171;">0</h3>
      </div>
      <div class="stat-card">
        <p>Engine Status</p>
        <h3 style="font-size: 0.85rem;" id="statEngine"><span style="color: #facc15;">Checking...</span></h3>
      </div>
    </section>

    <!-- Recent Activities -->
    <section id="recentActivitiesSection" class="table-container" style="margin-bottom: 1.5rem;">
      <div class="table-header"><h3>Recent Activities</h3></div>
      <div id="recentActivitiesBody" style="padding: 1rem; font-size: 0.8125rem; color: var(--text-muted);">
        Loading...
      </div>
    </section>

    <!-- User Management Section -->
    <section id="usersSection" class="table-container">
      <div class="table-header" style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; justify-content: space-between;">
        <h3 data-i18n="user_management">User Management</h3>
        
        <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
          <input type="text" id="userSearchInput" placeholder="Search users..." onkeyup="filterUsers()" style="height: 2.25rem; width: 200px; font-size: 0.8125rem;" />
          <select id="userRoleFilter" onchange="filterUsers()" style="height: 2.25rem; width: 160px; font-size: 0.8125rem;">
            <option value="">All Roles</option>
          </select>
          <button class="btn-action" id="btnCreateUser" onclick="openCreateModal()" data-i18n="create_user">+ Create User</button>
        </div>
      </div>
      <table>
        <thead>
          <tr>
            <th data-i18n="th_id">ID</th>
            <th data-i18n="th_username">Username</th>
            <th data-i18n="th_email">Email</th>
            <th data-i18n="th_role">Role</th>
            <th data-i18n="th_status">Status</th>
            <th data-i18n="th_actions">Actions</th>
          </tr>
        </thead>
        <tbody id="usersTableBody">
          <!-- Dynamically populated via API -->
        </tbody>
      </table>
    </section>

    <!-- Roles & Permissions Management Section -->
    <section id="rolesSection" class="table-container hidden">
      <div class="table-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3>Roles & Permissions Management</h3>
        <button class="btn-action" id="btnCreateRole" onclick="openRoleModal()">+ Create Role</button>
      </div>
      <div style="padding: 1.25rem;">
        <div id="rolesListGrid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.25rem;">
          <!-- Dynamically populated via API -->
        </div>
      </div>
    </section>

    <!-- Audit Logs Section -->
    <section id="auditSection" class="table-container hidden">
      <div class="table-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3>Security Audit Logs</h3>
        <input type="text" id="auditSearchInput" placeholder="Filter audit logs..." onkeyup="fetchAuditLogs()" style="height: 2.25rem; width: 220px; font-size: 0.8125rem;" />
      </div>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>User</th>
            <th>Action</th>
            <th>Target</th>
            <th>IP Address</th>
            <th>Timestamp</th>
          </tr>
        </thead>
        <tbody id="auditTableBody">
          <!-- Dynamically populated via API -->
        </tbody>
      </table>
    </section>

    <!-- Reports Section -->
    <section id="reportsSection" class="table-container hidden">
      <div class="table-header" style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; justify-content: space-between;">
        <h3>Security Reports</h3>
        <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
          <input type="text" id="reportSearchInput" placeholder="Search reports..." onkeyup="fetchReports()" style="height: 2.25rem; width: 200px; font-size: 0.8125rem;" />
          <select id="reportRiskFilter" onchange="fetchReports()" style="height: 2.25rem; width: 140px; font-size: 0.8125rem;">
            <option value="">All Risk Levels</option>
            <option value="Critical">Critical</option>
            <option value="High">High</option>
            <option value="Medium">Medium</option>
            <option value="Low">Low</option>
          </select>
        </div>
      </div>
      <table>
        <thead><tr><th>ID</th><th>Application</th><th>Engine</th><th>Risk Level</th><th>Score</th><th>Findings</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody id="reportsTableBody"></tbody>
      </table>
    </section>

    <!-- Tools Section -->
    <section id="toolsSection" class="table-container hidden">
      <div class="table-header"><h3>Security Tools</h3></div>
      <div id="toolsGrid" style="padding: 1.25rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.25rem;">Loading...</div>
    </section>

    <!-- AI Section -->
    <section id="aiSection" class="table-container hidden">
      <div class="table-header"><h3>AI Security Assistant</h3></div>
      <div style="padding: 1.25rem;">
        <div class="form-group">
          <label>Select Application for AI Analysis</label>
          <select id="aiAppSelect" style="width: 100%;"><option value="">-- Select Application --</option></select>
        </div>
        <button class="btn-action" id="btnRunAi" onclick="runAiAnalysis()" style="margin-bottom: 1.5rem;">Generate AI Security Analysis</button>
        <div id="aiResultContainer" class="hidden">
          <div id="aiResultContent" style="background: rgba(8,9,14,0.9); border: 1px solid var(--border-color); border-radius: 0.75rem; padding: 1.25rem;"></div>
        </div>
      </div>
    </section>

    <!-- Settings Section -->
    <section id="settingsSection" class="table-container hidden">
      <div class="table-header"><h3>Platform Settings</h3></div>
      <div style="padding: 1.25rem;">
        <div id="settingsError" class="error-alert hidden" style="margin-bottom: 1rem;"></div>
        <form id="settingsForm">
          <div class="form-group"><label>Platform Name</label><input type="text" id="setPlatformName" /></div>
          <div class="form-group"><label>Engine URL</label><input type="text" id="setEngineUrl" /></div>
          <div class="form-group"><label>Max Upload Size (MB)</label><input type="number" id="setMaxUpload" /></div>
          <div class="form-group"><label>Session Timeout (minutes)</label><input type="number" id="setSessionTimeout" /></div>
          <div class="form-group"><label>AI Model</label><input type="text" id="setAiModel" /></div>
          <button type="submit" class="btn-action" id="btnSaveSettings">Save Settings</button>
        </form>
      </div>
    </section>

    <!-- Profile Section -->
    <section id="profileSection" class="table-container hidden">
      <div class="table-header"><h3>My Profile</h3></div>
      <div style="padding: 1.25rem;">
        <div id="profileError" class="error-alert hidden" style="margin-bottom: 1rem;"></div>
        <form id="profileForm">
          <div class="form-group"><label>Full Name</label><input type="text" id="profName" /></div>
          <div class="form-group"><label>Username</label><input type="text" id="profUsername" /></div>
          <div class="form-group"><label>Email</label><input type="email" id="profEmail" /></div>
          <button type="submit" class="btn-action" id="btnSaveProfile">Update Profile</button>
        </form>
        <hr style="border-color: var(--border-color); margin: 1.5rem 0;" />
        <h4 style="margin-bottom: 1rem;">Change Password</h4>
        <div id="passwordError" class="error-alert hidden" style="margin-bottom: 1rem;"></div>
        <form id="passwordForm">
          <div class="form-group"><label>Current Password</label><input type="password" id="profCurrentPass" required /></div>
          <div class="form-group"><label>New Password</label><input type="password" id="profNewPass" required /></div>
          <div class="form-group"><label>Confirm New Password</label><input type="password" id="profConfirmPass" required /></div>
          <button type="submit" class="btn-action">Change Password</button>
        </form>
      </div>
    </section>
  </main>
</div>

<!-- Create User Modal -->
<div id="createUserModal" class="modal-overlay hidden">
  <div class="modal-card">
    <h3 style="margin-bottom: 1.25rem;" data-i18n="create_new_user">Create New User</h3>
    <div id="modalError" class="error-alert hidden" style="margin-bottom: 1rem;"></div>

    <form id="createUserForm">
      <div class="form-group">
        <label data-i18n="username">Username</label>
        <input type="text" id="newUsername" placeholder="e.g. jdoe" required />
      </div>
      <div class="form-group">
        <label data-i18n="email">Email Address</label>
        <input type="email" id="newEmail" placeholder="e.g. jdoe@erm.local" required />
      </div>
      <div class="form-group">
        <label data-i18n="password">Password</label>
        <input type="password" id="newPassword" placeholder="Minimum 6 characters" required />
      </div>
      <div class="form-group">
        <label data-i18n="th_role">Role</label>
        <select id="newRole"></select>
      </div>
      <div style="display: flex; gap: 0.5rem; margin-top: 1.5rem;">
        <button type="submit" id="submitModalBtn" class="btn-action" style="flex: 1;" data-i18n="create_account">Create Account</button>
        <button type="button" class="btn-action btn-danger" onclick="closeCreateModal()" data-i18n="cancel">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="modal-overlay hidden">
  <div class="modal-card">
    <h3 style="margin-bottom: 1.25rem;">Edit User Account</h3>
    <div id="editUserError" class="error-alert hidden" style="margin-bottom: 1rem;"></div>

    <form id="editUserForm">
      <input type="hidden" id="editUserId" />
      <div class="form-group">
        <label data-i18n="username">Username</label>
        <input type="text" id="editUsername" required />
      </div>
      <div class="form-group">
        <label data-i18n="email">Email Address</label>
        <input type="email" id="editEmail" required />
      </div>
      <div class="form-group">
        <label data-i18n="th_role">Role</label>
        <select id="editRole"></select>
      </div>
      <div class="remember-row" style="margin-bottom: 1.5rem;">
        <input type="checkbox" id="editIsActive" class="checkbox-custom" />
        <label for="editIsActive" class="remember-label">Account Active Status</label>
      </div>
      <div style="display: flex; gap: 0.5rem;">
        <button type="submit" id="submitEditUserBtn" class="btn-action" style="flex: 1;">Save Changes</button>
        <button type="button" class="btn-action btn-danger" onclick="closeEditUserModal()" data-i18n="cancel">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Reset Password Modal -->
<div id="resetPasswordModal" class="modal-overlay hidden">
  <div class="modal-card">
    <h3 style="margin-bottom: 1.25rem;">Reset User Password</h3>
    <div id="resetPassError" class="error-alert hidden" style="margin-bottom: 1rem;"></div>

    <form id="resetPasswordForm">
      <input type="hidden" id="resetPassUserId" />
      <p style="font-size: 0.875rem; color: #94a3b8; margin-bottom: 1rem;">Reset password for user: <strong id="resetPassUsername" style="color: white;"></strong></p>
      <div class="form-group">
        <label data-i18n="password">New Password</label>
        <input type="password" id="resetNewPassword" placeholder="Minimum 6 characters" required />
      </div>
      <div style="display: flex; gap: 0.5rem; margin-top: 1.5rem;">
        <button type="submit" id="submitResetPassBtn" class="btn-action" style="flex: 1;">Reset Password</button>
        <button type="button" class="btn-action btn-danger" onclick="closeResetPasswordModal()" data-i18n="cancel">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Create / Edit Role Modal -->
<div id="roleModal" class="modal-overlay hidden">
  <div class="modal-card" style="max-width: 680px; max-height: 90vh; overflow-y: auto;">
    <h3 style="margin-bottom: 1.25rem;" id="roleModalTitle">Create Security Role</h3>
    <div id="roleModalError" class="error-alert hidden" style="margin-bottom: 1rem;"></div>

    <form id="roleForm">
      <input type="hidden" id="roleModalId" />
      <div class="form-group">
        <label>Role Name</label>
        <input type="text" id="roleName" placeholder="e.g. Security Analyst" required />
      </div>
      <div class="form-group">
        <label>Description</label>
        <input type="text" id="roleDescription" placeholder="Role responsibilities and access scope" />
      </div>

      <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; margin-bottom: 0.75rem;">
        <label style="margin: 0;">Permissions Matrix</label>
        <div style="display: flex; gap: 0.5rem;">
          <button type="button" class="btn-action" style="padding: 0.25rem 0.5rem; font-size: 0.7rem;" onclick="toggleAllPermissions(true)">Select All</button>
          <button type="button" class="btn-action btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.7rem;" onclick="toggleAllPermissions(false)">Deselect All</button>
        </div>
      </div>

      <!-- Permission Checkboxes Grouped by Category -->
      <div id="permissionsContainer" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; background: rgba(8, 9, 14, 0.9); padding: 1rem; border-radius: 0.75rem; border: 1px solid var(--border-color);">
        <!-- Dynamically rendered -->
      </div>

      <div style="display: flex; gap: 0.5rem; margin-top: 1.5rem;">
        <button type="submit" id="submitRoleBtn" class="btn-action" style="flex: 1;">Save Role & Permissions</button>
        <button type="button" class="btn-action btn-danger" onclick="closeRoleModal()" data-i18n="cancel">Cancel</button>
      </div>
    </form>
  </div>
</div>
@endsection

@section('scripts')
<script>
  let currentUserPermissions = [];
  let currentUserRoleLevel = 1;
  let currentUserIsSuperAdmin = false;
  let availableRoles = [];
  let availablePermissions = [];
  let allUsersData = [];

  document.addEventListener("DOMContentLoaded", async () => {
    await fetchAuthMe();
    await fetchRolesData();
    await fetchPermissionsData();
    fetchStats();
    if (hasPerm('users.view')) {
      fetchUsers();
    }
  });

  async function fetchAuthMe() {
    try {
      const res = await fetch("/api/auth/me", { headers: { "Accept": "application/json" } });
      if (res.ok) {
        const me = await res.json();
        currentUserPermissions = me.permissions || [];
        currentUserRoleLevel = me.role_level || (me.role ? me.role.level : 1) || 1;
        currentUserIsSuperAdmin = me.is_super_admin || false;

        document.getElementById("userBadgeName").innerText = me.username;
        document.getElementById("userBadgeRole").innerText = me.role.name;

        // Apply permission UI guards
        applyUiPermissionGuards();
      }
    } catch (e) {}
  }

  function hasPerm(perm) {
    if (currentUserIsSuperAdmin) return true;
    return currentUserPermissions.includes(perm);
  }

  function applyUiPermissionGuards() {
    if (!hasPerm('users.view')) {
      document.getElementById('tab-nav-users')?.classList.add('hidden');
      document.getElementById('usersSection')?.classList.add('hidden');
    }
    if (!hasPerm('roles.view')) {
      document.getElementById('tab-nav-roles')?.classList.add('hidden');
      document.getElementById('rolesSection')?.classList.add('hidden');
    }
    if (!hasPerm('audit_logs.view')) {
      document.getElementById('tab-nav-audit')?.classList.add('hidden');
      document.getElementById('auditSection')?.classList.add('hidden');
    }
    if (!hasPerm('settings.view')) {
      document.getElementById('tab-nav-settings')?.classList.add('hidden');
      document.getElementById('settingsSection')?.classList.add('hidden');
    }
    if (!hasPerm('reports.view')) {
      document.getElementById('tab-nav-reports')?.classList.add('hidden');
      document.getElementById('reportsSection')?.classList.add('hidden');
    }
    if (!hasPerm('tools.mobsf') && !hasPerm('tools.view')) {
      document.getElementById('tab-nav-tools')?.classList.add('hidden');
      document.getElementById('toolsSection')?.classList.add('hidden');
    }
    if (!hasPerm('ai.analysis')) {
      document.getElementById('tab-nav-ai')?.classList.add('hidden');
      document.getElementById('aiSection')?.classList.add('hidden');
    }

    if (!hasPerm('users.create')) document.getElementById('btnCreateUser')?.classList.add('hidden');
    if (!hasPerm('roles.create')) document.getElementById('btnCreateRole')?.classList.add('hidden');
    if (!hasPerm('ai.analysis')) document.getElementById('btnRunAi')?.classList.add('hidden');
  }

  async function fetchRolesData() {
    try {
      const res = await fetch("/api/roles", { headers: { "Accept": "application/json" } });
      if (res.ok) {
        availableRoles = await res.json();
        populateRoleSelects();
        renderRolesGrid();
      }
    } catch (e) {}
  }

  async function fetchPermissionsData() {
    try {
      const res = await fetch("/api/permissions", { headers: { "Accept": "application/json" } });
      if (res.ok) {
        availablePermissions = await res.json();
      }
    } catch (e) {}
  }

  function populateRoleSelects() {
    const roleSelects = [
      { el: document.getElementById('newRole'), isFilter: false },
      { el: document.getElementById('editRole'), isFilter: false },
      { el: document.getElementById('userRoleFilter'), isFilter: true }
    ];
    
    roleSelects.forEach(({ el: select, isFilter }) => {
      if (!select) return;
      select.innerHTML = isFilter ? '<option value="">All Roles</option>' : '';
      
      // Filter assignable roles: non-super-admins cannot assign roles >= their own level
      const rolesToDisplay = isFilter
        ? availableRoles
        : availableRoles.filter(r => currentUserIsSuperAdmin || r.level < currentUserRoleLevel);

      rolesToDisplay.forEach(r => {
        const opt = document.createElement('option');
        opt.value = r.id;
        opt.innerText = r.name;
        select.appendChild(opt);
      });
    });
  }

  function switchTab(tab) {
    // Check permission before switching tab
    if (tab === 'users' && !hasPerm('users.view')) return;
    if (tab === 'roles' && !hasPerm('roles.view')) return;
    if (tab === 'audit' && !hasPerm('audit_logs.view')) return;
    if (tab === 'settings' && !hasPerm('settings.view')) return;
    if (tab === 'reports' && !hasPerm('reports.view')) return;
    if (tab === 'tools' && !hasPerm('tools.mobsf') && !hasPerm('tools.view')) return;
    if (tab === 'ai' && !hasPerm('ai.analysis')) return;

    document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
    
    const targetNav = document.getElementById(`nav-${tab}`);
    if (targetNav) targetNav.classList.add('active');

    const allSections = ['statsSection','recentActivitiesSection','usersSection','rolesSection','auditSection','reportsSection','toolsSection','aiSection','settingsSection','profileSection'];
    allSections.forEach(id => document.getElementById(id)?.classList.add('hidden'));

    const titleEl = document.getElementById("pageTitle");
    const t = translations[currentLang] || {};

    if (tab === 'dashboard') {
      titleEl.innerText = t.dashboard || "Dashboard";
      document.getElementById('statsSection').classList.remove('hidden');
      document.getElementById('recentActivitiesSection').classList.remove('hidden');
      if (hasPerm('users.view')) {
        document.getElementById('usersSection').classList.remove('hidden');
      }
    } else if (tab === 'users') {
      titleEl.innerText = t.users || "Users";
      document.getElementById('usersSection').classList.remove('hidden');
      fetchUsers();
    } else if (tab === 'roles') {
      titleEl.innerText = t.roles || "Roles & Permissions";
      document.getElementById('rolesSection').classList.remove('hidden');
      fetchRolesData();
    } else if (tab === 'audit') {
      titleEl.innerText = t.audit || "Audit Logs";
      document.getElementById('auditSection').classList.remove('hidden');
      fetchAuditLogs();
    } else if (tab === 'reports') {
      titleEl.innerText = 'Security Reports';
      document.getElementById('reportsSection').classList.remove('hidden');
      fetchReports();
    } else if (tab === 'tools') {
      titleEl.innerText = 'Security Tools';
      document.getElementById('toolsSection').classList.remove('hidden');
      fetchToolsStatus();
    } else if (tab === 'ai') {
      titleEl.innerText = 'AI Security Assistant';
      document.getElementById('aiSection').classList.remove('hidden');
      loadAiAppSelect();
    } else if (tab === 'settings') {
      titleEl.innerText = t.settings || 'Settings';
      document.getElementById('settingsSection').classList.remove('hidden');
      loadSettings();
    } else if (tab === 'profile') {
      titleEl.innerText = 'My Profile';
      document.getElementById('profileSection').classList.remove('hidden');
      loadProfile();
    }
  }

  function showFlash(msg) {
    const el = document.getElementById("flashSuccess");
    document.getElementById("flashSuccessMsg").innerText = msg;
    el.classList.remove("hidden");
    setTimeout(() => el.classList.add("hidden"), 5000);
  }

  function hideFlash() {
    document.getElementById("flashSuccess").classList.add("hidden");
  }

  async function fetchStats() {
    try {
      const res = await fetch("/api/dashboard", { headers: { "Accept": "application/json" } });
      if (res.ok) {
        const stats = await res.json();
        const statsGrid = document.getElementById("statsSection");
        const actSection = document.getElementById("recentActivitiesSection");
        const actBody = document.getElementById("recentActivitiesBody");

        // --- 1. SUPER ADMIN DASHBOARD CARDS ---
        if (stats.dashboard_type === 'super_admin') {
          const eng = stats.engine_health || {};
          const engHtml = eng.online
            ? `<span style="color:#4ade80;">ONLINE</span> <span style="font-size:0.7rem;color:#94a3b8;">${eng.latency_ms}ms</span>`
            : `<span style="color:#facc15;">STANDBY</span>`;

          statsGrid.innerHTML = `
            <div class="stat-card"><p>Total Users</p><h3>${stats.total_users}</h3></div>
            <div class="stat-card"><p>Active Users</p><h3 style="color:#4ade80;">${stats.active_users}</h3></div>
            <div class="stat-card"><p>Disabled Users</p><h3 style="color:#f87171;">${stats.disabled_users}</h3></div>
            <div class="stat-card"><p>Administrators</p><h3 style="color:#c084fc;">${stats.admin_users}</h3></div>
            <div class="stat-card"><p>Security Analysts</p><h3 style="color:#38bdf8;">${stats.analyst_users}</h3></div>
            <div class="stat-card"><p>Applications</p><h3>${stats.total_applications}</h3></div>
            <div class="stat-card"><p>Completed Scans</p><h3>${stats.completed_analyses}</h3></div>
            <div class="stat-card"><p>High / Critical Risk</p><h3 style="color:#f87171;">${stats.high_risk_findings}</h3></div>
            <div class="stat-card"><p>Audit Log Entries</p><h3>${stats.audit_count}</h3></div>
            <div class="stat-card"><p>Engine Health</p><h3 style="font-size:0.85rem;">${engHtml}</h3></div>
          `;

          const acts = stats.recent_activities || [];
          actSection.querySelector('.table-header h3').innerText = "Global Security Audit & Telemetry";
          if (acts.length === 0) {
            actBody.innerHTML = '<p style="color:#94a3b8;">No recent activities logged.</p>';
          } else {
            actBody.innerHTML = acts.map(a => `
              <div style="display:flex;justify-content:space-between;padding:0.6rem 0;border-bottom:1px solid rgba(255,255,255,0.04);">
                <div>
                  <span style="color:#c084fc;font-weight:600;">${a.username}</span> &rarr;
                  <span style="color:#f1f5f9;">${a.action}</span>
                  <span style="color:#94a3b8;">${a.target || ''}</span>
                  <span style="color:#64748b;font-size:0.75rem;margin-left:0.5rem;">[${a.ip_address}]</span>
                </div>
                <span style="font-size:0.75rem;color:#64748b;">${a.created_at}</span>
              </div>
            `).join('');
          }
        }
        // --- 2. ADMIN DASHBOARD CARDS ---
        else if (stats.dashboard_type === 'admin') {
          const eng = stats.engine_health || {};
          const engHtml = eng.online
            ? `<span style="color:#4ade80;">ONLINE</span> <span style="font-size:0.7rem;color:#94a3b8;">${eng.latency_ms}ms</span>`
            : `<span style="color:#facc15;">STANDBY</span>`;

          statsGrid.innerHTML = `
            <div class="stat-card"><p>Managed Users</p><h3>${stats.managed_users}</h3></div>
            <div class="stat-card"><p>Active Users</p><h3 style="color:#4ade80;">${stats.active_users}</h3></div>
            <div class="stat-card"><p>Security Analysts</p><h3 style="color:#38bdf8;">${stats.analyst_users}</h3></div>
            <div class="stat-card"><p>Applications</p><h3>${stats.total_applications}</h3></div>
            <div class="stat-card"><p>Completed Scans</p><h3>${stats.completed_analyses}</h3></div>
            <div class="stat-card"><p>Security Reports</p><h3>${stats.total_reports}</h3></div>
            <div class="stat-card"><p>High Risk Findings</p><h3 style="color:#f87171;">${stats.high_risk_findings}</h3></div>
            <div class="stat-card"><p>Engine Health</p><h3 style="font-size:0.85rem;">${engHtml}</h3></div>
          `;

          const acts = stats.recent_activities || [];
          actSection.querySelector('.table-header h3').innerText = "Recent Administrative Activity";
          if (acts.length === 0) {
            actBody.innerHTML = '<p style="color:#94a3b8;">No recent activities.</p>';
          } else {
            actBody.innerHTML = acts.map(a => `
              <div style="display:flex;justify-content:space-between;padding:0.6rem 0;border-bottom:1px solid rgba(255,255,255,0.04);">
                <div>
                  <span style="color:#c084fc;font-weight:600;">${a.username}</span> &rarr;
                  <span style="color:#f1f5f9;">${a.action}</span>
                  <span style="color:#94a3b8;">${a.target || ''}</span>
                </div>
                <span style="font-size:0.75rem;color:#64748b;">${a.created_at}</span>
              </div>
            `).join('');
          }
        }
        // --- 3. SECURITY ANALYST DASHBOARD CARDS ---
        else if (stats.dashboard_type === 'analyst') {
          const eng = stats.engine_health || {};
          const engHtml = eng.online
            ? `<span style="color:#4ade80;">ONLINE</span> <span style="font-size:0.7rem;color:#94a3b8;">${eng.latency_ms}ms</span>`
            : `<span style="color:#facc15;">STANDBY</span>`;

          const rb = stats.risk_breakdown || {};

          statsGrid.innerHTML = `
            <div class="stat-card"><p>Applications</p><h3>${stats.total_applications}</h3></div>
            <div class="stat-card"><p>Completed Analyses</p><h3 style="color:#4ade80;">${stats.completed_analyses}</h3></div>
            <div class="stat-card"><p>Active Scans</p><h3 style="color:#facc15;">${stats.active_scans}</h3></div>
            <div class="stat-card"><p>Total Reports</p><h3>${stats.total_reports}</h3></div>
            <div class="stat-card"><p>Critical Risk</p><h3 style="color:#ef4444;">${rb.critical || 0}</h3></div>
            <div class="stat-card"><p>High Risk</p><h3 style="color:#f97316;">${rb.high || 0}</h3></div>
            <div class="stat-card"><p>Medium Risk</p><h3 style="color:#eab308;">${rb.medium || 0}</h3></div>
            <div class="stat-card"><p>Engine Health</p><h3 style="font-size:0.85rem;">${engHtml}</h3></div>
          `;

          const scans = stats.recent_scans || [];
          actSection.querySelector('.table-header h3').innerText = "Recent Security Vulnerability Scans";
          if (scans.length === 0) {
            actBody.innerHTML = '<p style="color:#94a3b8;">No completed scans found.</p>';
          } else {
            actBody.innerHTML = scans.map(s => `
              <div style="display:flex;justify-content:space-between;align-items:center;padding:0.6rem 0;border-bottom:1px solid rgba(255,255,255,0.04);">
                <div>
                  <span style="color:#f1f5f9;font-weight:600;">${s.app_name}</span>
                  <span class="role-tag" style="margin-left:0.5rem;font-size:0.7rem;background:rgba(239,68,68,0.2);color:#fca5a5;">${s.risk_level} (${s.score}/100)</span>
                </div>
                <span style="font-size:0.75rem;color:#64748b;">${s.created_at}</span>
              </div>
            `).join('');
          }
        }
        // --- 4. STANDARD USER DASHBOARD CARDS ---
        else {
          statsGrid.innerHTML = `
            <div class="stat-card"><p>My Applications</p><h3>${stats.my_applications || 0}</h3></div>
            <div class="stat-card"><p>My Completed Scans</p><h3 style="color:#4ade80;">${stats.my_completed_analyses || 0}</h3></div>
            <div class="stat-card"><p>My Security Reports</p><h3 style="color:#c084fc;">${stats.my_reports || 0}</h3></div>
          `;

          const myAnalyses = stats.my_recent_analyses || [];
          actSection.querySelector('.table-header h3').innerText = "My Recent Analyses & Activity";
          if (myAnalyses.length === 0) {
            actBody.innerHTML = '<p style="color:#94a3b8;">No applications uploaded under this account.</p>';
          } else {
            actBody.innerHTML = myAnalyses.map(a => `
              <div style="display:flex;justify-content:space-between;align-items:center;padding:0.6rem 0;border-bottom:1px solid rgba(255,255,255,0.04);">
                <div>
                  <span style="color:#f1f5f9;font-weight:600;">${a.app_name}</span>
                  <span class="role-tag" style="margin-left:0.5rem;font-size:0.7rem;">${a.status}</span>
                </div>
                <span style="font-size:0.75rem;color:#64748b;">${a.created_at}</span>
              </div>
            `).join('');
          }
        }
      }
    } catch (e) {}
  }

  async function fetchUsers() {
    if (!hasPerm('users.view')) return;
    try {
      const res = await fetch("/api/users", { headers: { "Accept": "application/json" } });
      if (res.ok) {
        allUsersData = await res.json();
        renderUsersTable(allUsersData);
      }
    } catch (e) {}
  }

  function filterUsers() {
    const search = document.getElementById('userSearchInput').value.toLowerCase().trim();
    const roleId = document.getElementById('userRoleFilter').value;

    const filtered = allUsersData.filter(u => {
      const matchSearch = u.username.toLowerCase().includes(search) || u.email.toLowerCase().includes(search);
      const matchRole = !roleId || u.role_id == roleId;
      return matchSearch && matchRole;
    });

    renderUsersTable(filtered);
  }

  function renderUsersTable(users) {
    const t = translations[currentLang] || {};
    const currentUserId = {{ auth()->id() }};
    const tbody = document.getElementById("usersTableBody");

    if (users.length === 0) {
      tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:#94a3b8;">No users found</td></tr>`;
      return;
    }

    tbody.innerHTML = users.map(u => {
      const isSelf = u.id === currentUserId;
      const canManage = currentUserIsSuperAdmin || (u.role.level < currentUserRoleLevel);

      return `
        <tr>
          <td>#${u.id}</td>
          <td style="font-weight: 600;">${u.username}</td>
          <td>${u.email}</td>
          <td><span class="role-tag role-admin">${u.role.name}</span></td>
          <td><span style="color: ${u.is_active ? '#4ade80' : '#f87171'}; font-weight: 600;">${u.is_active ? (t.active||'Active') : (t.disabled||'Disabled')}</span></td>
          <td>
            <div style="display:flex; gap:0.35rem; align-items:center;">
              ${hasPerm('users.edit') && canManage ? `<button class="btn-action" style="padding: 0.3rem 0.6rem; font-size: 0.7rem;" onclick="openEditUserModal(${u.id})">Edit</button>` : ''}
              ${(hasPerm('users.reset_password') || hasPerm('users.change_password')) && canManage ? `<button class="btn-action" style="padding: 0.3rem 0.6rem; font-size: 0.7rem; background:#475569;" onclick="openResetPasswordModal(${u.id}, '${u.username}')">Pass</button>` : ''}
              ${u.is_active && hasPerm('users.disable') && canManage && !isSelf ? `<button class="btn-action" style="padding: 0.3rem 0.6rem; font-size: 0.7rem; background:#d97706;" onclick="toggleUserStatus(${u.id})">Disable</button>` : ''}
              ${!u.is_active && hasPerm('users.enable') && canManage && !isSelf ? `<button class="btn-action" style="padding: 0.3rem 0.6rem; font-size: 0.7rem; background:#059669;" onclick="toggleUserStatus(${u.id})">Enable</button>` : ''}
              ${hasPerm('users.delete') && canManage && !isSelf ? `<button class="btn-action btn-danger" style="padding: 0.3rem 0.6rem; font-size: 0.7rem;" onclick="deleteUser(${u.id})">${t.cancel||'Delete'}</button>` : ''}
              ${isSelf ? `<span style="color:#64748b; font-size:0.75rem;">${t.current||'(Current)'}</span>` : ''}
              ${!canManage && !isSelf ? `<span style="color:#64748b; font-size:0.75rem;">Protected</span>` : ''}
            </div>
          </td>
        </tr>
      `;
    }).join("");
  }

  // --- Create User Modal ---
  function openCreateModal() {
    document.getElementById("modalError").classList.add("hidden");
    document.getElementById("createUserForm").reset();
    document.getElementById("createUserModal").classList.remove("hidden");
  }
  function closeCreateModal() {
    document.getElementById("createUserModal").classList.add("hidden");
  }

  document.getElementById("createUserForm").addEventListener("submit", async (e) => {
    e.preventDefault();
    const errBox = document.getElementById("modalError");
    const submitBtn = document.getElementById("submitModalBtn");
    errBox.classList.add("hidden");

    const username = document.getElementById("newUsername").value.trim();
    const email = document.getElementById("newEmail").value.trim();
    const password = document.getElementById("newPassword").value;
    const role_id = parseInt(document.getElementById("newRole").value);

    submitBtn.innerText = "Creating...";
    submitBtn.disabled = true;

    try {
      const res = await fetch("/api/users", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "Accept": "application/json",
          "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ username, email, password, role_id, is_active: true })
      });
      const data = await res.json();
      if (res.ok) {
        closeCreateModal();
        fetchUsers();
        fetchStats();
        showFlash(`User "${username}" created successfully!`);
      } else {
        errBox.innerText = data.detail || data.message || "Failed to create user.";
        errBox.classList.remove("hidden");
      }
    } catch (err) {
      errBox.innerText = err.message || "An error occurred.";
      errBox.classList.remove("hidden");
    } finally {
      submitBtn.innerText = "Create Account";
      submitBtn.disabled = false;
    }
  });

  // --- Edit User Modal ---
  function openEditUserModal(id) {
    const user = allUsersData.find(u => u.id === id);
    if (!user) return;

    document.getElementById("editUserError").classList.add("hidden");
    document.getElementById("editUserId").value = user.id;
    document.getElementById("editUsername").value = user.username;
    document.getElementById("editEmail").value = user.email;
    document.getElementById("editRole").value = user.role_id;
    document.getElementById("editIsActive").checked = user.is_active;

    document.getElementById("editUserModal").classList.remove("hidden");
  }

  function closeEditUserModal() {
    document.getElementById("editUserModal").classList.add("hidden");
  }

  document.getElementById("editUserForm").addEventListener("submit", async (e) => {
    e.preventDefault();
    const errBox = document.getElementById("editUserError");
    const submitBtn = document.getElementById("submitEditUserBtn");
    errBox.classList.add("hidden");

    const id = document.getElementById("editUserId").value;
    const username = document.getElementById("editUsername").value.trim();
    const email = document.getElementById("editEmail").value.trim();
    const role_id = parseInt(document.getElementById("editRole").value);
    const is_active = document.getElementById("editIsActive").checked;

    submitBtn.innerText = "Saving...";
    submitBtn.disabled = true;

    try {
      const res = await fetch(`/api/users/${id}`, {
        method: "PUT",
        headers: {
          "Content-Type": "application/json",
          "Accept": "application/json",
          "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ username, email, role_id, is_active })
      });
      const data = await res.json();
      if (res.ok) {
        closeEditUserModal();
        fetchUsers();
        showFlash(`User "${username}" updated successfully.`);
      } else {
        errBox.innerText = data.detail || data.message || "Failed to update user.";
        errBox.classList.remove("hidden");
      }
    } catch (err) {
      errBox.innerText = err.message;
      errBox.classList.remove("hidden");
    } finally {
      submitBtn.innerText = "Save Changes";
      submitBtn.disabled = false;
    }
  });

  // --- Reset Password Modal ---
  function openResetPasswordModal(id, username) {
    document.getElementById("resetPassError").classList.add("hidden");
    document.getElementById("resetPassUserId").value = id;
    document.getElementById("resetPassUsername").innerText = username;
    document.getElementById("resetNewPassword").value = "";
    document.getElementById("resetPasswordModal").classList.remove("hidden");
  }

  function closeResetPasswordModal() {
    document.getElementById("resetPasswordModal").classList.add("hidden");
  }

  document.getElementById("resetPasswordForm").addEventListener("submit", async (e) => {
    e.preventDefault();
    const errBox = document.getElementById("resetPassError");
    const submitBtn = document.getElementById("submitResetPassBtn");
    errBox.classList.add("hidden");

    const id = document.getElementById("resetPassUserId").value;
    const password = document.getElementById("resetNewPassword").value;

    submitBtn.innerText = "Resetting...";
    submitBtn.disabled = true;

    try {
      const res = await fetch(`/api/users/${id}/reset-password`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "Accept": "application/json",
          "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ password })
      });
      const data = await res.json();
      if (res.ok) {
        closeResetPasswordModal();
        showFlash(data.message || "Password reset successfully.");
      } else {
        errBox.innerText = data.detail || data.message || "Failed to reset password.";
        errBox.classList.remove("hidden");
      }
    } catch (err) {
      errBox.innerText = err.message;
      errBox.classList.remove("hidden");
    } finally {
      submitBtn.innerText = "Reset Password";
      submitBtn.disabled = false;
    }
  });

  async function toggleUserStatus(id) {
    try {
      const res = await fetch(`/api/users/${id}/toggle-status`, {
        method: "POST",
        headers: {
          "Accept": "application/json",
          "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
        }
      });
      const data = await res.json();
      if (res.ok) {
        fetchUsers();
        showFlash(data.message);
      } else {
        alert(data.detail || "Failed to change user status.");
      }
    } catch (e) { alert(e.message); }
  }

  async function deleteUser(id) {
    if (!confirm("Are you sure you want to delete this user?")) return;
    try {
      const res = await fetch(`/api/users/${id}`, {
        method: "DELETE",
        headers: {
          "Accept": "application/json",
          "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
        }
      });
      if (res.ok) {
        fetchUsers();
        fetchStats();
        showFlash("User account deleted successfully.");
      } else {
        const data = await res.json();
        alert(data.detail || "Failed to delete user");
      }
    } catch (e) { alert(e.message); }
  }

  // --- Roles & Permissions Management ---
  function renderRolesGrid() {
    const grid = document.getElementById("rolesListGrid");
    if (!grid) return;

    if (availableRoles.length === 0) {
      grid.innerHTML = `<p style="color:#94a3b8;">No roles found.</p>`;
      return;
    }

    grid.innerHTML = availableRoles.map(r => {
      const canEdit = r.can_edit ?? (currentUserIsSuperAdmin || r.level < currentUserRoleLevel);
      const canDelete = r.can_delete ?? (currentUserIsSuperAdmin && r.level < 3 && r.users_count === 0);

      return `
        <div style="background: rgba(10, 12, 18, 0.9); border: 1px solid var(--border-color); border-radius: 0.75rem; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
          <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
              <h4 style="font-size: 1.1rem; color: #f8fafc; font-weight: 700;">${r.name}</h4>
              <span class="role-tag role-admin">${r.users_count} Users</span>
            </div>
            <p style="font-size: 0.8125rem; color: #94a3b8; margin-bottom: 1rem;">${r.description || 'No description provided.'}</p>
            <div style="font-size: 0.75rem; color: var(--purple-light); font-weight: 600; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.35rem;">
              <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
              <span>${r.permissions.length} Permissions Granted</span>
            </div>
          </div>
          <div style="display: flex; gap: 0.5rem; margin-top: 1rem; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 0.75rem; align-items: center;">
            ${hasPerm('roles.edit') && canEdit ? `<button class="btn-action" style="flex:1; font-size:0.75rem;" onclick="openRoleModal(${r.id})">Edit Permissions</button>` : ''}
            ${hasPerm('roles.delete') && canDelete ? `<button class="btn-action btn-danger" style="padding:0.4rem 0.75rem; font-size:0.75rem;" onclick="deleteRole(${r.id})">Delete</button>` : ''}
            ${!canEdit ? `<span style="font-size:0.75rem; color:#64748b; font-weight:600;">System Protected Role</span>` : ''}
          </div>
        </div>
      `;
    }).join("");
  }

  function openRoleModal(roleId = null) {
    document.getElementById("roleModalError").classList.add("hidden");
    document.getElementById("roleForm").reset();

    const titleEl = document.getElementById("roleModalTitle");
    const idEl = document.getElementById("roleModalId");

    renderPermissionCheckboxes();

    if (roleId) {
      const role = availableRoles.find(r => r.id === roleId);
      if (!role) return;
      titleEl.innerText = `Edit Role: ${role.name}`;
      idEl.value = role.id;
      document.getElementById("roleName").value = role.name;
      document.getElementById("roleDescription").value = role.description || "";

      // Check assigned permission checkboxes
      const grantedIds = role.permission_ids || [];
      document.querySelectorAll(".perm-checkbox").forEach(cb => {
        cb.checked = grantedIds.includes(parseInt(cb.value));
      });
    } else {
      titleEl.innerText = "Create Security Role";
      idEl.value = "";
    }

    document.getElementById("roleModal").classList.remove("hidden");
  }

  function closeRoleModal() {
    document.getElementById("roleModal").classList.add("hidden");
  }

  function renderPermissionCheckboxes() {
    const container = document.getElementById("permissionsContainer");
    if (!container) return;

    if (availablePermissions.length === 0) {
      container.innerHTML = `<p style="color:#94a3b8; grid-column: 1/-1;">Loading permissions...</p>`;
      return;
    }

    container.innerHTML = availablePermissions.map(p => {
      const callerHas = currentUserIsSuperAdmin || p.caller_has || hasPerm(p.name);
      const disabledAttr = !callerHas ? 'disabled title="Your role cannot grant this permission"' : '';

      return `
        <div style="display: flex; align-items: flex-start; gap: 0.5rem; background: rgba(15, 18, 28, 0.7); padding: 0.5rem; border-radius: 0.5rem; border: 1px solid rgba(255,255,255,0.03); opacity: ${callerHas ? '1' : '0.45'};">
          <input type="checkbox" id="perm_${p.id}" value="${p.id}" class="perm-checkbox checkbox-custom" style="margin-top: 0.15rem;" ${disabledAttr} />
          <div>
            <label for="perm_${p.id}" style="font-size: 0.78125rem; font-weight: 700; color: #f1f5f9; cursor: ${callerHas ? 'pointer':'not-allowed'}; margin-bottom: 0.1rem;">${p.name}</label>
            <p style="font-size: 0.6875rem; color: #94a3b8; line-height: 1.2;">${p.description || ''}</p>
          </div>
        </div>
      `;
    }).join("");
  }

  function toggleAllPermissions(checked) {
    document.querySelectorAll(".perm-checkbox").forEach(cb => cb.checked = checked);
  }

  document.getElementById("roleForm").addEventListener("submit", async (e) => {
    e.preventDefault();
    const errBox = document.getElementById("roleModalError");
    const submitBtn = document.getElementById("submitRoleBtn");
    errBox.classList.add("hidden");

    const id = document.getElementById("roleModalId").value;
    const name = document.getElementById("roleName").value.trim();
    const description = document.getElementById("roleDescription").value.trim();

    const selectedPermissions = [];
    document.querySelectorAll(".perm-checkbox:checked").forEach(cb => {
      selectedPermissions.push(parseInt(cb.value));
    });

    submitBtn.innerText = "Saving...";
    submitBtn.disabled = true;

    const url = id ? `/api/roles/${id}` : "/api/roles";
    const method = id ? "PUT" : "POST";

    try {
      const res = await fetch(url, {
        method: method,
        headers: {
          "Content-Type": "application/json",
          "Accept": "application/json",
          "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ name, description, permissions: selectedPermissions })
      });

      const data = await res.json();

      if (res.ok) {
        closeRoleModal();
        await fetchRolesData();
        await fetchAuthMe(); // refresh current user permissions if editing own role
        showFlash(data.message || "Role updated successfully!");
      } else {
        errBox.innerText = data.detail || data.message || "Failed to save role.";
        errBox.classList.remove("hidden");
      }
    } catch (err) {
      errBox.innerText = err.message;
      errBox.classList.remove("hidden");
    } finally {
      submitBtn.innerText = "Save Role & Permissions";
      submitBtn.disabled = false;
    }
  });

  async function deleteRole(id) {
    if (!confirm("Are you sure you want to delete this role?")) return;
    try {
      const res = await fetch(`/api/roles/${id}`, {
        method: "DELETE",
        headers: {
          "Accept": "application/json",
          "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
        }
      });
      const data = await res.json();
      if (res.ok) {
        fetchRolesData();
        showFlash(data.message);
      } else {
        alert(data.detail || "Failed to delete role.");
      }
    } catch (e) { alert(e.message); }
  }

  // --- Audit Logs ---
  async function fetchAuditLogs() {
    try {
      const search = document.getElementById("auditSearchInput").value.trim();
      const url = search ? `/api/audit-logs?search=${encodeURIComponent(search)}` : "/api/audit-logs";
      const res = await fetch(url, { headers: { "Accept": "application/json" } });
      if (res.ok) {
        const logs = await res.json();
        const tbody = document.getElementById("auditTableBody");
        if (logs.length === 0) {
          tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:#94a3b8;">No audit records found</td></tr>`;
          return;
        }

        tbody.innerHTML = logs.map(l => `
          <tr>
            <td>#${l.id}</td>
            <td style="font-weight: 600; color: #c084fc;">${l.username}</td>
            <td><span style="font-weight: 700; color: #f1f5f9;">${l.action}</span></td>
            <td>${l.target || '-'}</td>
            <td style="font-family: var(--font-tech); font-size: 0.75rem;">${l.ip_address || '-'}</td>
            <td style="font-size: 0.75rem; color: #94a3b8;">${l.created_at}</td>
          </tr>
        `).join("");
      }
    } catch (e) {}
  }

  // --- Reports ---
  async function fetchReports() {
    try {
      const search = document.getElementById('reportSearchInput')?.value.trim() || '';
      const risk = document.getElementById('reportRiskFilter')?.value || '';
      let url = '/api/reports?';
      if (search) url += `search=${encodeURIComponent(search)}&`;
      if (risk) url += `risk_level=${encodeURIComponent(risk)}&`;
      const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
      if (res.ok) {
        const reports = await res.json();
        const tbody = document.getElementById('reportsTableBody');
        if (reports.length === 0) {
          tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;color:#94a3b8;">No reports found.</td></tr>`;
          return;
        }
        const riskColor = (r) => {
          if (r==='Critical'||r==='High') return 'color:#fca5a5;background:rgba(239,68,68,0.15);border:1px solid rgba(239,68,68,0.3)';
          if (r==='Medium') return 'color:#fef08a;background:rgba(234,179,8,0.15);border:1px solid rgba(234,179,8,0.3)';
          return 'color:#86efac;background:rgba(34,197,94,0.15);border:1px solid rgba(34,197,94,0.3)';
        };
        tbody.innerHTML = reports.map(r => `
          <tr>
            <td>#${r.id}</td>
            <td style="font-weight:600;">${r.application_name}</td>
            <td>${r.engine}</td>
            <td><span class="role-tag" style="${riskColor(r.risk_level)}">${r.risk_level}</span></td>
            <td>${r.score}/100</td>
            <td>${r.findings_count}</td>
            <td style="font-size:0.75rem;color:#94a3b8;">${r.created_at}</td>
            <td style="display:flex;gap:0.3rem;">
              ${hasPerm('reports.download') ? `<button class="btn-action" style="padding:0.3rem 0.5rem;font-size:0.7rem;" onclick="downloadReport(${r.id})">Download</button>` : ''}
              ${hasPerm('reports.delete') ? `<button class="btn-action btn-danger" style="padding:0.3rem 0.5rem;font-size:0.7rem;" onclick="deleteReport(${r.id})">Delete</button>` : ''}
            </td>
          </tr>
        `).join('');
      }
    } catch(e) {}
  }

  function downloadReport(id) {
    window.open(`/api/reports/${id}/download`, '_blank');
  }

  async function deleteReport(id) {
    if (!confirm('Delete this report permanently?')) return;
    try {
      const res = await fetch(`/api/reports/${id}`, {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
      });
      if (res.ok) { fetchReports(); showFlash('Report deleted.'); }
      else { const d = await res.json(); alert(d.detail || 'Failed to delete report.'); }
    } catch(e) { alert(e.message); }
  }

  // --- Tools ---
  async function fetchToolsStatus() {
    const grid = document.getElementById('toolsGrid');
    try {
      const res = await fetch('/api/tools/status', { headers: { 'Accept': 'application/json' } });
      if (res.ok) {
        const data = await res.json();
        grid.innerHTML = data.tools.map(t => `
          <div style="background:rgba(10,12,18,0.9);border:1px solid var(--border-color);border-radius:0.75rem;padding:1.25rem;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem;">
              <h4 style="font-size:1rem;color:#f8fafc;font-weight:700;">${t.name}</h4>
              <span class="role-tag" style="${t.status==='online' ? 'background:rgba(34,197,94,0.2);color:#86efac;border:1px solid rgba(34,197,94,0.4)' : 'background:rgba(234,179,8,0.15);color:#facc15;border:1px solid rgba(234,179,8,0.3)'}">
                ${t.status.toUpperCase()}${t.latency_ms ? ` (${t.latency_ms}ms)` : ''}
              </span>
            </div>
            <p style="font-size:0.8125rem;color:#94a3b8;margin-bottom:1rem;">${t.description}</p>
            <div style="display:flex;flex-wrap:wrap;gap:0.4rem;">
              ${t.capabilities.map(c => `<span style="font-size:0.6875rem;background:rgba(168,85,247,0.12);color:var(--purple-light);padding:0.2rem 0.5rem;border-radius:0.375rem;border:1px solid rgba(168,85,247,0.2);">${c}</span>`).join('')}
            </div>
          </div>
        `).join('');
      } else { grid.innerHTML = '<p style="color:#f87171;">Failed to load tools status.</p>'; }
    } catch(e) { grid.innerHTML = '<p style="color:#94a3b8;">Engine unavailable. Tools are in standby mode.</p>'; }
  }

  // --- AI ---
  async function loadAiAppSelect() {
    try {
      const res = await fetch('/applications', { headers: { 'Accept': 'application/json' } });
      if (res.ok) {
        const apps = await res.json();
        const sel = document.getElementById('aiAppSelect');
        sel.innerHTML = '<option value="">-- Select Application --</option>';
        apps.forEach(a => {
          sel.innerHTML += `<option value="${a.id}">${a.name} (${a.status})</option>`;
        });
      }
    } catch(e) {}
  }

  async function runAiAnalysis() {
    const appId = document.getElementById('aiAppSelect').value;
    if (!appId) { alert('Please select an application first.'); return; }
    const btn = document.getElementById('btnRunAi');
    btn.innerText = 'Analyzing...'; btn.disabled = true;
    try {
      const res = await fetch('/api/ai/analyze', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ application_id: parseInt(appId) })
      });
      const data = await res.json();
      const container = document.getElementById('aiResultContainer');
      const content = document.getElementById('aiResultContent');
      if (res.ok) {
        const d = data.data;
        const riskColor = d.risk_posture.includes('CRITICAL') ? '#fca5a5' : (d.risk_posture.includes('HIGH') ? '#f87171' : (d.risk_posture.includes('MODERATE') ? '#facc15' : '#4ade80'));
        let html = `<div style="margin-bottom:1rem;"><span class="role-tag" style="background:rgba(168,85,247,0.2);color:var(--purple-light);border:1px solid var(--border-accent);font-size:0.8rem;">AI SECURITY ASSESSMENT</span></div>`;
        html += `<p style="font-size:1.1rem;font-weight:700;color:${riskColor};margin-bottom:0.75rem;">Risk Posture: ${d.risk_posture}</p>`;
        html += `<p style="color:#cbd5e1;font-size:0.875rem;margin-bottom:1.25rem;">${d.summary_narrative}</p>`;
        // OWASP breakdown
        const owasp = d.owasp_breakdown || {};
        if (Object.keys(owasp).length > 0) {
          html += `<h4 style="color:var(--purple-light);margin-bottom:0.5rem;">OWASP Mobile Top 10</h4>`;
          html += Object.entries(owasp).map(([k,v]) => `<div style="display:flex;justify-content:space-between;padding:0.35rem 0;border-bottom:1px solid rgba(255,255,255,0.04);"><span style="color:#e2e8f0;">${k}</span><span style="color:#c084fc;font-weight:700;">${v} finding(s)</span></div>`).join('');
        }
        // Remediations
        if (d.remediations && d.remediations.length > 0) {
          html += `<h4 style="color:var(--purple-light);margin:1.25rem 0 0.5rem;">Remediation Suggestions</h4>`;
          html += d.remediations.map(r => `
            <div style="background:rgba(15,18,28,0.7);padding:0.75rem;border-radius:0.5rem;margin-bottom:0.5rem;border:1px solid rgba(255,255,255,0.04);">
              <div style="display:flex;justify-content:space-between;margin-bottom:0.35rem;"><strong style="color:#f1f5f9;">${r.issue}</strong><span class="role-tag" style="font-size:0.65rem;background:rgba(239,68,68,0.15);color:#fca5a5;border:1px solid rgba(239,68,68,0.3);">${r.severity}</span></div>
              <p style="font-size:0.8125rem;color:#94a3b8;">${r.recommendation}</p>
            </div>
          `).join('');
        }
        content.innerHTML = html;
        container.classList.remove('hidden');
      } else {
        content.innerHTML = `<p style="color:#f87171;">${data.message || 'AI analysis failed.'}</p>`;
        container.classList.remove('hidden');
      }
    } catch(e) { alert(e.message); }
    finally { btn.innerText = 'Generate AI Security Analysis'; btn.disabled = false; }
  }

  // --- Settings ---
  async function loadSettings() {
    try {
      const res = await fetch('/api/settings', { headers: { 'Accept': 'application/json' } });
      if (res.ok) {
        const s = await res.json();
        document.getElementById('setPlatformName').value = s.platform_name?.value || '';
        document.getElementById('setEngineUrl').value = s.engine_url?.value || '';
        document.getElementById('setMaxUpload').value = s.max_upload_size_mb?.value || '100';
        document.getElementById('setSessionTimeout').value = s.session_timeout_minutes?.value || '120';
        document.getElementById('setAiModel').value = s.ai_model?.value || 'gpt-4o';
      }
    } catch(e) {}
  }

  document.getElementById('settingsForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const errBox = document.getElementById('settingsError');
    errBox.classList.add('hidden');
    const btn = document.getElementById('btnSaveSettings');
    btn.innerText = 'Saving...'; btn.disabled = true;
    try {
      const body = {
        platform_name: document.getElementById('setPlatformName').value,
        engine_url: document.getElementById('setEngineUrl').value,
        max_upload_size_mb: document.getElementById('setMaxUpload').value,
        session_timeout_minutes: document.getElementById('setSessionTimeout').value,
        ai_model: document.getElementById('setAiModel').value,
      };
      const res = await fetch('/api/settings', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify(body)
      });
      if (res.ok) { showFlash('Settings saved successfully.'); }
      else { const d = await res.json(); errBox.innerText = d.detail || 'Failed to save settings.'; errBox.classList.remove('hidden'); }
    } catch(err) { errBox.innerText = err.message; errBox.classList.remove('hidden'); }
    finally { btn.innerText = 'Save Settings'; btn.disabled = false; }
  });

  // --- Profile ---
  async function loadProfile() {
    try {
      const res = await fetch('/api/profile', { headers: { 'Accept': 'application/json' } });
      if (res.ok) {
        const p = await res.json();
        document.getElementById('profName').value = p.name || '';
        document.getElementById('profUsername').value = p.username || '';
        document.getElementById('profEmail').value = p.email || '';
      }
    } catch(e) {}
  }

  document.getElementById('profileForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const errBox = document.getElementById('profileError');
    errBox.classList.add('hidden');
    const btn = document.getElementById('btnSaveProfile');
    btn.innerText = 'Saving...'; btn.disabled = true;
    try {
      const res = await fetch('/api/profile', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({
          name: document.getElementById('profName').value.trim(),
          username: document.getElementById('profUsername').value.trim(),
          email: document.getElementById('profEmail').value.trim(),
        })
      });
      const data = await res.json();
      if (res.ok) { showFlash(data.message || 'Profile updated.'); await fetchAuthMe(); }
      else { errBox.innerText = data.detail || 'Failed to update profile.'; errBox.classList.remove('hidden'); }
    } catch(err) { errBox.innerText = err.message; errBox.classList.remove('hidden'); }
    finally { btn.innerText = 'Update Profile'; btn.disabled = false; }
  });

  document.getElementById('passwordForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const errBox = document.getElementById('passwordError');
    errBox.classList.add('hidden');
    const newPass = document.getElementById('profNewPass').value;
    const confirmPass = document.getElementById('profConfirmPass').value;
    if (newPass !== confirmPass) { errBox.innerText = 'New password and confirmation do not match.'; errBox.classList.remove('hidden'); return; }
    try {
      const res = await fetch('/api/profile/password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({
          current_password: document.getElementById('profCurrentPass').value,
          new_password: newPass,
          new_password_confirmation: confirmPass,
        })
      });
      const data = await res.json();
      if (res.ok) { showFlash(data.message || 'Password changed.'); document.getElementById('passwordForm').reset(); }
      else { errBox.innerText = data.detail || 'Failed to change password.'; errBox.classList.remove('hidden'); }
    } catch(err) { errBox.innerText = err.message; errBox.classList.remove('hidden'); }
  });
</script>
@endsection
