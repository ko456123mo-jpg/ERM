<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AiAnalysisController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\JadxController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ToolController;
use Illuminate\Support\Facades\Route;

// Authentication Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Protected Platform Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/api/dashboard', [DashboardController::class, 'stats']);

    // Authenticated User Profile & Permissions API
    Route::get('/api/auth/me', function () {
        $user = auth()->user();
        $user->load('role.permissions');
        
        $permissions = $user->isSuperAdmin()
            ? \App\Models\Permission::pluck('name')->toArray()
            : ($user->role ? $user->role->permissions->pluck('name')->toArray() : []);

        return response()->json([
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'is_active' => $user->is_active,
            'is_super_admin' => $user->isSuperAdmin(),
            'role' => [
                'id' => $user->role?->id,
                'name' => $user->role?->name ?? 'N/A'
            ],
            'permissions' => $permissions
        ]);
    });

    // Applications & Security Analysis Routes
    Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index')->middleware('permission:applications.view');
    Route::post('/applications', [ApplicationController::class, 'store'])->name('applications.store')->middleware('permission:applications.upload');
    Route::get('/applications/{application}', [ApplicationController::class, 'show'])->name('applications.show')->middleware('permission:applications.view');
    Route::delete('/applications/{application}', [ApplicationController::class, 'destroy'])->name('applications.destroy')->middleware('permission:applications.delete');
    Route::post('/applications/{application}/reanalyze', [ApplicationController::class, 'reanalyze'])->name('applications.reanalyze')->middleware('permission:analysis.start');

    // User Management APIs
    Route::get('/api/users', [UserController::class, 'index'])->middleware('permission:users.view');
    Route::post('/api/users', [UserController::class, 'store'])->middleware('permission:users.create');
    Route::put('/api/users/{user}', [UserController::class, 'update'])->middleware('permission:users.edit');
    Route::post('/api/users/{user}/reset-password', [UserController::class, 'resetPassword'])->middleware('permission:users.reset_password');
    Route::post('/api/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->middleware('permission:users.edit');
    Route::delete('/api/users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete');

    // Role & Permission Management APIs
    Route::get('/api/roles', [RoleController::class, 'index'])->middleware('permission:roles.view');
    Route::get('/api/permissions', [RoleController::class, 'getPermissions'])->middleware('permission:roles.view');
    Route::post('/api/roles', [RoleController::class, 'store'])->middleware('permission:roles.create');
    Route::put('/api/roles/{role}', [RoleController::class, 'update'])->middleware('permission:roles.edit');
    Route::delete('/api/roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:roles.delete');

    // Audit Log APIs
    Route::get('/api/audit-logs', [AuditLogController::class, 'index'])->middleware('permission:audit_logs.view');

    // Security Reports APIs
    Route::get('/api/reports', [ReportController::class, 'index'])->middleware('permission:reports.view');
    Route::get('/api/reports/{analysis}', [ReportController::class, 'show'])->middleware('permission:reports.view');
    Route::get('/api/reports/{analysis}/download', [ReportController::class, 'download'])->middleware('permission:reports.download');
    Route::delete('/api/reports/{analysis}', [ReportController::class, 'destroy'])->middleware('permission:reports.delete');

    // JADX APK decompilation
    Route::get('/api/jadx/status', [JadxController::class, 'status'])
        ->middleware('permission:applications.view');
    Route::post('/api/applications/{application}/jadx', [JadxController::class, 'decompile'])
        ->middleware('permission:applications.view');

    // Security Tools APIs
    Route::get('/api/tools/status', [ToolController::class, 'status'])->middleware('permission:tools.mobsf');
    Route::post('/api/tools/execute', [ToolController::class, 'execute'])->middleware('permission:tools.mobsf');

    // AI Analysis APIs
    Route::post('/api/ai/analyze', [AiAnalysisController::class, 'analyze'])->middleware('permission:ai.analysis');

    // Platform Settings APIs
    Route::get('/api/settings', [SettingsController::class, 'index'])->middleware('permission:settings.view');
    Route::post('/api/settings', [SettingsController::class, 'update'])->middleware('permission:settings.edit');

    // User Profile APIs
    Route::get('/api/profile', [ProfileController::class, 'show']);
    Route::put('/api/profile', [ProfileController::class, 'update']);
    Route::post('/api/profile/password', [ProfileController::class, 'changePassword']);
});
