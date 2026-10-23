<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>ERM — Engineering Reverse Mobile Enterprise Platform</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    
    :root {
      --bg-base: #07080c;
      --bg-surface: #0e1017;
      --bg-card: rgba(14, 16, 25, 0.88);
      --border-color: rgba(45, 35, 75, 0.6);
      --border-accent: rgba(168, 85, 247, 0.45);
      --purple-primary: #a855f7;
      --purple-light: #c084fc;
      --purple-dark: #7e22ce;
      --purple-glow: rgba(168, 85, 247, 0.3);
      --text-main: #f1f5f9;
      --text-muted: #94a3b8;
      --font-tech: 'JetBrains Mono', monospace;
    }

    html, body {
      height: 100%;
      width: 100%;
      font-family: 'Inter', 'Cairo', -apple-system, sans-serif;
      background-color: var(--bg-base);
      color: var(--text-main);
      overflow-x: hidden;
      -webkit-font-smoothing: antialiased;
    }

    /* Subtle background grid & purple ambient glow */
    .bg-grid {
      position: fixed;
      inset: 0;
      background-image: 
        linear-gradient(to right, rgba(168, 85, 247, 0.035) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(168, 85, 247, 0.035) 1px, transparent 1px);
      background-size: 3.5rem 3.5rem;
      mask-image: radial-gradient(ellipse 80% 70% at 50% 50%, #000 65%, transparent 100%);
      pointer-events: none;
      z-index: 0;
    }

    .bg-glow {
      position: fixed;
      width: 800px;
      height: 600px;
      background: radial-gradient(circle, rgba(147, 51, 234, 0.12) 0%, rgba(14, 16, 23, 0.2) 60%, transparent 80%);
      filter: blur(120px);
      pointer-events: none;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      z-index: 0;
    }

    /* CUSTOM GEOMETRIC ERM TYPOGRAPHY LOGO STYLES */
    .erm-custom-type-logo {
      cursor: pointer;
      display: block;
      width: 100%;
      height: 100%;
    }
    .erm-type-segment {
      transition: transform 0.75s cubic-bezier(0.16, 1, 0.3, 1), fill 0.4s ease, filter 0.5s ease;
      transform-origin: center center;
    }

    /* DEFAULT DISASSEMBLED STATE (Fragments of E, R, M) */
    .e-spine { transform: translate(-12px, 10px) rotate(-1.5deg); fill: #9333ea; }
    .e-top   { transform: translate(-16px, -14px) rotate(2deg); fill: #a855f7; }
    .e-mid   { transform: translate(-8px, -5px); fill: #c084fc; }
    .e-bot   { transform: translate(-14px, 16px) rotate(-2.5deg); fill: #7e22ce; }

    .r-spine { transform: translate(-5px, -16px) rotate(1.2deg); fill: #a855f7; }
    .r-loop  { transform: translate(14px, -12px) rotate(-2.5deg); fill: #e9d5ff; }
    .r-leg   { transform: translate(16px, 14px) rotate(2deg); fill: #c084fc; }

    .m-left  { transform: translate(8px, -14px) rotate(-2deg); fill: #9333ea; }
    .m-diag1 { transform: translate(12px, -8px); fill: #c084fc; }
    .m-diag2 { transform: translate(14px, 10px) rotate(2.5deg); fill: #e9d5ff; }
    .m-right { transform: translate(18px, 16px) rotate(-1.5deg); fill: #a855f7; }

    /* Geometric dots & particles */
    .tech-dot { transition: transform 0.75s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.4s ease; }
    .dot-1 { transform: translate(-10px, -15px); opacity: 0.6; }
    .dot-2 { transform: translate(15px, -10px); opacity: 0.7; }
    .dot-3 { transform: translate(-12px, 18px); opacity: 0.5; }
    .dot-4 { transform: translate(18px, 12px); opacity: 0.65; }

    /* HOVER RECONSTRUCTION STATE */
    .erm-custom-type-logo:hover .erm-type-segment {
      transform: translate(0, 0) rotate(0deg) !important;
      filter: drop-shadow(0 0 14px rgba(192, 132, 252, 0.95));
    }
    .erm-custom-type-logo:hover .tech-dot {
      transform: translate(0, 0) !important;
      opacity: 1;
    }
    .erm-custom-type-logo:hover .e-spine,
    .erm-custom-type-logo:hover .r-spine,
    .erm-custom-type-logo:hover .m-left { fill: #a855f7; }
    .erm-custom-type-logo:hover .e-top,
    .erm-custom-type-logo:hover .r-loop,
    .erm-custom-type-logo:hover .m-diag2 { fill: #e9d5ff; }
    .erm-custom-type-logo:hover .e-mid,
    .erm-custom-type-logo:hover .e-bot,
    .erm-custom-type-logo:hover .r-leg,
    .erm-custom-type-logo:hover .m-diag1,
    .erm-custom-type-logo:hover .m-right { fill: #c084fc; }

    /* TWO COLUMN ENTERPRISE LOGIN LAYOUT */
    .login-container-split {
      min-height: 100vh;
      display: grid;
      grid-template-columns: 1.1fr 0.9fr;
      position: relative;
      z-index: 10;
      width: 100%;
      max-width: 1400px;
      margin: 0 auto;
      padding: 2.5rem;
      align-items: center;
      gap: 3rem;
    }

    html[dir="rtl"] .login-container-split {
      direction: rtl;
    }

    @media (max-width: 1024px) {
      .login-container-split {
        grid-template-columns: 1fr;
        padding: 1.5rem;
        gap: 2rem;
      }
    }

    /* HERO BRAND CANVAS */
    .hero-brand-canvas {
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 2rem;
      position: relative;
    }

    .hero-logo-box {
      width: 100%;
      max-width: 540px;
      height: 220px;
      background: rgba(10, 12, 19, 0.92);
      border: 1px solid var(--border-accent);
      border-radius: 1.5rem;
      padding: 1.25rem;
      margin-bottom: 2rem;
      box-shadow: 0 20px 45px -10px rgba(0, 0, 0, 0.85), 0 0 30px rgba(168, 85, 247, 0.18);
      position: relative;
    }

    .hero-title {
      font-size: 2.25rem;
      font-weight: 800;
      letter-spacing: -0.02em;
      color: #ffffff;
      margin-bottom: 0.75rem;
    }

    .hero-subtitle {
      font-size: 1rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--purple-light);
      margin-bottom: 1.25rem;
    }

    .hero-description {
      font-size: 0.9375rem;
      color: var(--text-muted);
      line-height: 1.6;
      max-width: 520px;
      margin-bottom: 2rem;
    }

    .telemetry-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 1rem;
      max-width: 520px;
    }

    .telemetry-card {
      background: rgba(14, 16, 25, 0.75);
      border: 1px solid var(--border-color);
      border-radius: 0.75rem;
      padding: 0.875rem 1rem;
      font-family: var(--font-tech);
      font-size: 0.75rem;
      color: #cbd5e1;
    }

    .telemetry-card span {
      display: block;
      color: var(--purple-light);
      font-weight: 600;
      font-size: 0.6875rem;
      margin-bottom: 0.25rem;
    }

    /* ENTERPRISE LOGIN CARD */
    .card-login-enterprise {
      background: var(--bg-card);
      backdrop-filter: blur(28px);
      -webkit-backdrop-filter: blur(28px);
      border: 1px solid var(--border-color);
      border-radius: 1.5rem;
      padding: 3rem 2.5rem;
      box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.9), 0 0 40px var(--purple-glow);
      position: relative;
    }

    .card-login-enterprise::before {
      content: '';
      position: absolute;
      top: -1px;
      left: 15%;
      right: 15%;
      height: 2px;
      background: linear-gradient(to right, transparent, var(--purple-primary), transparent);
    }

    .card-top-controls {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
    }

    .card-title-group h2 {
      font-size: 1.5rem;
      font-weight: 800;
      color: #ffffff;
    }

    .card-title-group p {
      font-size: 0.8125rem;
      color: var(--text-muted);
      margin-top: 0.25rem;
    }

    .form-group {
      margin-bottom: 1.5rem;
    }

    label {
      display: block;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: #cbd5e1;
      margin-bottom: 0.5rem;
    }

    .input-wrapper {
      position: relative;
    }

    .input-icon {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      width: 1.15rem;
      height: 1.15rem;
      color: #64748b;
      pointer-events: none;
    }

    html[dir="rtl"] .input-icon {
      left: auto;
      right: 1rem;
    }

    input[type="text"],
    input[type="password"],
    input[type="email"],
    select {
      width: 100%;
      height: 3.125rem;
      background: rgba(8, 9, 14, 0.9);
      border: 1px solid #282238;
      border-radius: 0.75rem;
      padding-left: 2.75rem;
      padding-right: 2.75rem;
      color: #f8fafc;
      font-size: 0.9375rem;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
    }

    select {
      padding-left: 1rem;
      padding-right: 1rem;
    }

    input[type="text"]:focus,
    input[type="password"]:focus,
    input[type="email"]:focus,
    select:focus {
      border-color: var(--purple-primary);
      box-shadow: 0 0 0 2px var(--purple-glow);
    }

    .toggle-password {
      position: absolute;
      right: 1rem;
      top: 50%;
      transform: translateY(-50%);
      background: transparent;
      border: none;
      color: #64748b;
      cursor: pointer;
      padding: 0.25rem;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    html[dir="rtl"] .toggle-password {
      right: auto;
      left: 1rem;
    }

    .remember-row {
      display: flex;
      align-items: center;
      margin-top: 0.5rem;
      margin-bottom: 1.75rem;
    }

    .checkbox-custom {
      appearance: none;
      width: 1.15rem;
      height: 1.15rem;
      background: rgba(8, 9, 14, 0.9);
      border: 1px solid #3d2f59;
      border-radius: 0.35rem;
      cursor: pointer;
      position: relative;
    }

    .checkbox-custom:checked {
      background: var(--purple-primary);
      border-color: var(--purple-light);
    }

    .checkbox-custom:checked::after {
      content: '';
      position: absolute;
      left: 0.38rem;
      top: 0.15rem;
      width: 0.25rem;
      height: 0.5rem;
      border: solid #ffffff;
      border-width: 0 2px 2px 0;
      transform: rotate(45deg);
    }

    .remember-label {
      font-size: 0.84375rem;
      color: var(--text-muted);
      margin-left: 0.625rem;
      cursor: pointer;
      user-select: none;
    }

    html[dir="rtl"] .remember-label {
      margin-left: 0;
      margin-right: 0.625rem;
    }

    .btn-submit {
      width: 100%;
      height: 3.125rem;
      background: linear-gradient(135deg, #9333ea 0%, #7e22ce 100%);
      color: #ffffff;
      font-size: 0.9375rem;
      font-weight: 700;
      border: none;
      border-radius: 0.75rem;
      cursor: pointer;
      box-shadow: 0 4px 20px rgba(147, 51, 234, 0.4);
      transition: opacity 0.2s, transform 0.1s, box-shadow 0.2s;
    }

    .btn-submit:hover {
      opacity: 0.95;
      box-shadow: 0 6px 26px rgba(168, 85, 247, 0.55);
    }

    .btn-submit:active {
      transform: translateY(1px);
    }

    .error-alert {
      background: rgba(153, 27, 27, 0.25);
      border: 1px solid rgba(239, 68, 68, 0.5);
      color: #fca5a5;
      padding: 0.875rem;
      border-radius: 0.625rem;
      font-size: 0.8125rem;
      margin-bottom: 1.5rem;
      text-align: center;
    }

    .notice {
      margin-top: 2rem;
      padding-top: 1.25rem;
      border-top: 1px solid var(--border-color);
      text-align: center;
    }

    .notice p {
      font-size: 0.6875rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: #64748b;
    }

    /* DASHBOARD LAYOUT */
    .app-layout {
      display: flex;
      min-height: 100vh;
      position: relative;
      z-index: 10;
    }

    .sidebar {
      width: 290px;
      background: rgba(9, 10, 16, 0.96);
      border-right: 1px solid var(--border-color);
      display: flex;
      flex-direction: column;
      padding: 1.5rem 1rem;
    }

    html[dir="rtl"] .sidebar {
      border-right: none;
      border-left: 1px solid var(--border-color);
    }

    .sidebar-brand {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.5rem;
      padding: 0 0.5rem 1.25rem 0.5rem;
      border-bottom: 1px solid var(--border-color);
      margin-bottom: 1.5rem;
    }

    .sidebar-brand p {
      font-size: 0.625rem;
      color: var(--purple-light);
      text-transform: uppercase;
      font-weight: 700;
      letter-spacing: 1px;
    }

    .nav-list {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 0.35rem;
      flex: 1;
    }

    .nav-item {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.75rem 1rem;
      border-radius: 0.5rem;
      color: var(--text-muted);
      font-size: 0.875rem;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s;
      text-decoration: none;
    }

    .nav-icon {
      width: 18px;
      height: 18px;
      stroke: currentColor;
      flex-shrink: 0;
    }

    .nav-item:hover, .nav-item.active {
      background: rgba(168, 85, 247, 0.12);
      color: var(--purple-light);
    }

    .nav-item.logout {
      margin-top: auto;
      color: #f87171;
    }

    .nav-item.logout:hover {
      background: rgba(239, 68, 68, 0.1);
      color: #fca5a5;
    }

    .main-content {
      flex: 1;
      padding: 2rem;
      overflow-y: auto;
    }

    .top-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid var(--border-color);
    }

    .top-bar h1 {
      font-size: 1.5rem;
      font-weight: 800;
    }

    .top-bar-controls {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .lang-selector {
      height: 2.25rem;
      width: 125px;
      font-size: 0.75rem;
      font-weight: 600;
      border-radius: 0.5rem;
      background: rgba(16, 18, 28, 0.9);
      border: 1px solid var(--border-color);
      color: var(--text-main);
    }

    .audio-btn {
      background: rgba(16, 18, 28, 0.9);
      border: 1px solid var(--border-color);
      color: var(--purple-light);
      width: 2.25rem;
      height: 2.25rem;
      border-radius: 0.5rem;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.2s;
    }

    .audio-btn:hover {
      border-color: var(--purple-primary);
      background: rgba(168, 85, 247, 0.15);
    }

    .user-badge {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      background: rgba(16, 18, 28, 0.9);
      border: 1px solid var(--border-color);
      padding: 0.4rem 0.9rem;
      border-radius: 2rem;
    }

    .role-tag {
      font-size: 0.6875rem;
      font-weight: 700;
      text-transform: uppercase;
      padding: 0.2rem 0.6rem;
      border-radius: 0.25rem;
    }

    .role-admin {
      background: rgba(168, 85, 247, 0.2);
      color: #c084fc;
      border: 1px solid rgba(168, 85, 247, 0.4);
    }

    .role-analyst {
      background: rgba(192, 132, 252, 0.15);
      color: #e9d5ff;
      border: 1px solid rgba(192, 132, 252, 0.3);
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 1.25rem;
      margin-bottom: 2rem;
    }

    .stat-card {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: 0.75rem;
      padding: 1.25rem;
    }

    .stat-card p {
      font-size: 0.75rem;
      color: var(--text-muted);
      text-transform: uppercase;
      font-weight: 600;
      margin-bottom: 0.5rem;
    }

    .stat-card h3 {
      font-size: 1.75rem;
      font-weight: 800;
      color: #ffffff;
    }

    .table-container {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: 0.75rem;
      overflow: hidden;
    }

    .table-header {
      padding: 1.25rem;
      border-bottom: 1px solid var(--border-color);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
      font-size: 0.875rem;
    }

    html[dir="rtl"] table {
      text-align: right;
    }

    th {
      background: rgba(22, 25, 37, 0.6);
      padding: 0.875rem 1.25rem;
      color: #cbd5e1;
      font-weight: 600;
    }

    td {
      padding: 0.875rem 1.25rem;
      border-bottom: 1px solid var(--border-color);
      color: #e2e8f0;
    }

    .btn-action {
      background: var(--purple-dark);
      color: white;
      border: none;
      padding: 0.45rem 0.9rem;
      border-radius: 0.375rem;
      font-size: 0.75rem;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s;
    }

    .btn-action:hover {
      background: var(--purple-primary);
    }

    .btn-danger {
      background: #ef4444;
    }

    .btn-danger:hover {
      background: #dc2626;
    }

    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.85);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 100;
    }

    .modal-card {
      background: #0f111a;
      border: 1px solid var(--border-color);
      border-radius: 1rem;
      padding: 2rem;
      width: 100%;
      max-width: 420px;
    }

    .hidden {
      display: none !important;
    }
  </style>
  @yield('styles')
</head>
<body>
  <div class="bg-grid"></div>
  <div class="bg-glow"></div>

  @yield('content')

  <script>
    let currentLang = localStorage.getItem("erm_lang") || "en";
    let isMuted = localStorage.getItem("erm_muted") === "true";

    const translations = {
      en: {
        platform_name: "Engineering Reverse Mobile",
        platform_desc: "Enterprise Mobile Security Analysis Platform",
        platform_tag: "Cybersecurity Platform",
        platform_summary: "On-Premise Android Reverse Engineering, Dynamic Instrumenting & Static Binary Security Platform.",
        auth_portal: "AUTHENTICATION PORTAL",
        auth_sub: "Sign in with your enterprise security credentials",
        username: "Username",
        password: "Password",
        remember: "Remember me",
        signin: "Sign In",
        authorized_only: "Authorized personnel only • Restricted Enterprise Access",
        dashboard: "Dashboard",
        users: "Users",
        roles: "Roles & Permissions",
        apps: "Applications",
        analyses: "Analyses",
        reports: "Reports",
        audit: "Audit Logs",
        settings: "Settings",
        logout: "Logout",
        stat_users: "Total Registered Users",
        stat_analysts: "Active Analysts",
        stat_status: "System Status",
        user_management: "User Management",
        create_user: "+ Create User",
        th_id: "ID",
        th_username: "Username",
        th_email: "Email",
        th_role: "Role",
        th_status: "Status",
        th_actions: "Actions",
        module_ready: "This module is configured and ready for Phase integration.",
        create_new_user: "Create New User",
        email: "Email Address",
        create_account: "Create Account",
        cancel: "Cancel",
        active: "Active",
        disabled: "Disabled",
        current: "(Current)",
        t_node: "CORE ENGINE",
        t_sec: "ARGON2ID ENFORCED",
        t_arch: "ANDROID REVERSE",
        t_status: "ON-PREMISE ACTIVE"
      },
      ar: {
        platform_name: "الهندسة العكسية للهواتف (ERM)",
        platform_desc: "منصة تحليل أمن تطبيقات الهواتف للمؤسسات",
        platform_tag: "منصة الأمن السيبراني",
        platform_summary: "منصة هندسة عكسية، تحليل ديناميكي، وفحص باينري مغلق لتطبيقات أندرويد للمؤسسات.",
        auth_portal: "بوابة التحقق من الهوية",
        auth_sub: "سجل الدخول باستخدام بيانات الاعتماد الأمنية الخاصة بك",
        username: "اسم المستخدم",
        password: "كلمة المرور",
        remember: "تذكرني",
        signin: "تسجيل الدخول",
        authorized_only: "للموظفين المصرح لهم فقط • وصول محمي للمؤسسات",
        dashboard: "لوحة التحكم",
        users: "المستخدمين",
        roles: "الأدوار والصلاحيات",
        apps: "التطبيقات",
        analyses: "التحليلات",
        reports: "التقارير",
        audit: "سجلات التدقيق",
        settings: "الإعدادات",
        logout: "تسجيل الخروج",
        stat_users: "إجمالي المستخدمين المسجلين",
        stat_analysts: "محللي أمن نشطين",
        stat_status: "حالة النظام",
        user_management: "إدارة المستخدمين",
        create_user: "+ إنشاء مستخدم",
        th_id: "المعرف",
        th_username: "اسم المستخدم",
        th_email: "البريد الإلكتروني",
        th_role: "الدور",
        th_status: "الحالة",
        th_actions: "الإجراءات",
        module_ready: "هذا القسم مهيأ وجاهز للتكامل مع مراحل التطوير القادمة.",
        create_new_user: "إنشاء مستخدم جديد",
        email: "البريد الإلكتروني",
        create_account: "إنشاء الحساب",
        cancel: "إلغاء",
        active: "نشط",
        disabled: "معطل",
        current: "(الحالي)",
        t_node: "المحرك الرئيسي",
        t_sec: "حماية ARGON2ID",
        t_arch: "هندسة عكسية لأندرويد",
        t_status: "نظام محلي نشط"
      }
    };

    document.addEventListener("DOMContentLoaded", () => {
      setLanguage(currentLang);
      updateAudioIcon();

      const langSelect = document.getElementById("langSelect");
      if (langSelect) {
        langSelect.value = currentLang;
        langSelect.addEventListener("change", (e) => setLanguage(e.target.value));
      }

      const audioBtn = document.getElementById("audioToggleBtn");
      if (audioBtn) {
        audioBtn.addEventListener("click", () => {
          isMuted = !isMuted;
          localStorage.setItem("erm_muted", isMuted);
          updateAudioIcon();
        });
      }
    });

    function setLanguage(lang) {
      currentLang = lang;
      localStorage.setItem("erm_lang", lang);
      document.documentElement.lang = lang;
      document.documentElement.dir = lang === "ar" ? "rtl" : "ltr";

      document.querySelectorAll("[data-i18n]").forEach(el => {
        const key = el.getAttribute("data-i18n");
        if (translations[lang] && translations[lang][key]) {
          el.innerText = translations[lang][key];
        }
      });
    }

    function updateAudioIcon() {
      const btn = document.getElementById("audioToggleBtn");
      const icon = document.getElementById("audioIcon");
      if (!btn || !icon) return;
      if (isMuted) {
        btn.style.opacity = "0.5";
        icon.innerHTML = `<polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><line x1="23" y1="9" x2="17" y2="15"></line><line x1="17" y1="9" x2="23" y2="15"></line>`;
      } else {
        btn.style.opacity = "1";
        icon.innerHTML = `<polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path>`;
      }
    }

    function playWelcomeVoice() {
      if (isMuted) return;
      try {
        if ('speechSynthesis' in window) {
          window.speechSynthesis.cancel();
          const utterance = new SpeechSynthesisUtterance("Welcome to ERM.");
          utterance.rate = 0.95;
          utterance.pitch = 0.9;
          utterance.volume = 0.85;
          window.speechSynthesis.speak(utterance);
        }
      } catch (e) {}
    }
  </script>
  @yield('scripts')
</body>
</html>
