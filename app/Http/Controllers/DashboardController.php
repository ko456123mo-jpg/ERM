<?php

namespace App\Http\Controllers;

use App\Models\Analysis;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Services\FastApiService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard.admin');
    }

    public function stats(Request $request)
    {
        $user = $request->user();
        $roleLevel = $user->getRoleLevel();

        $fastApi = app(FastApiService::class);

        // --- LEVEL 4: SUPER ADMIN DASHBOARD ---
        if ($roleLevel >= 4) {
            $totalUsers = User::count();
            $activeUsers = User::where('is_active', true)->count();
            $disabledUsers = User::where('is_active', false)->count();

            $adminRoleIds = Role::whereIn('name', ['Admin', 'ADMIN'])->pluck('id');
            $analystRoleIds = Role::whereIn('name', ['Security Analyst', 'SECURITY_ANALYST'])->pluck('id');
            $standardRoleIds = Role::whereNotIn('name', ['Super Admin', 'Admin', 'ADMIN', 'Security Analyst', 'SECURITY_ANALYST'])->pluck('id');

            $adminCount = User::whereIn('role_id', $adminRoleIds)->count();
            $analystCount = User::whereIn('role_id', $analystRoleIds)->count();
            $standardCount = User::whereIn('role_id', $standardRoleIds)->count();

            $totalApplications = Application::count();
            $completedAnalyses = Analysis::where('status', 'completed')->count();
            $totalReports = Analysis::where('status', 'completed')->count();
            $highRiskFindings = Analysis::where('status', 'completed')
                ->where(function ($q) {
                    $q->where('risk_level', 'High')->orWhere('risk_level', 'Critical');
                })->count();

            $recentActivities = AuditLog::orderBy('id', 'desc')->limit(8)->get()->map(fn($l) => [
                'id' => $l->id,
                'username' => $l->username,
                'action' => $l->action,
                'target' => $l->target,
                'ip_address' => $l->ip_address,
                'created_at' => $l->created_at->format('Y-m-d H:i:s'),
            ]);

            $engineHealth = $fastApi->checkEngineHealth();

            return response()->json([
                'dashboard_type' => 'super_admin',
                'role_level' => 4,
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'disabled_users' => $disabledUsers,
                'admin_users' => $adminCount,
                'analyst_users' => $analystCount,
                'standard_users' => $standardCount,
                'total_applications' => $totalApplications,
                'completed_analyses' => $completedAnalyses,
                'total_reports' => $totalReports,
                'high_risk_findings' => $highRiskFindings,
                'recent_activities' => $recentActivities,
                'engine_health' => $engineHealth,
                'audit_count' => AuditLog::count(),
            ]);
        }

        // --- LEVEL 3: ADMIN DASHBOARD ---
        if ($roleLevel === 3) {
            $superAdminRoleIds = Role::whereIn('name', ['Super Admin', 'SUPER ADMIN', 'SUPER_ADMIN'])->pluck('id');
            $managedUsers = User::whereNotIn('role_id', $superAdminRoleIds)->count();
            $activeUsers = User::whereNotIn('role_id', $superAdminRoleIds)->where('is_active', true)->count();
            
            $analystRoleIds = Role::whereIn('name', ['Security Analyst', 'SECURITY_ANALYST'])->pluck('id');
            $standardRoleIds = Role::whereNotIn('name', ['Super Admin', 'Admin', 'ADMIN', 'Security Analyst', 'SECURITY_ANALYST'])->pluck('id');

            $analystCount = User::whereIn('role_id', $analystRoleIds)->count();
            $standardCount = User::whereIn('role_id', $standardRoleIds)->count();

            $totalApplications = Application::count();
            $completedAnalyses = Analysis::where('status', 'completed')->count();
            $totalReports = Analysis::where('status', 'completed')->count();
            $highRiskFindings = Analysis::where('status', 'completed')
                ->where(function ($q) {
                    $q->where('risk_level', 'High')->orWhere('risk_level', 'Critical');
                })->count();

            // Recent permitted activity (excluding superadmin specific telemetry)
            $recentActivities = AuditLog::where('username', '!=', 'superadmin')
                ->orderBy('id', 'desc')
                ->limit(6)
                ->get()
                ->map(fn($l) => [
                    'id' => $l->id,
                    'username' => $l->username,
                    'action' => $l->action,
                    'target' => $l->target,
                    'created_at' => $l->created_at->format('Y-m-d H:i:s'),
                ]);

            $engineHealth = $fastApi->checkEngineHealth();

            return response()->json([
                'dashboard_type' => 'admin',
                'role_level' => 3,
                'managed_users' => $managedUsers,
                'active_users' => $activeUsers,
                'analyst_users' => $analystCount,
                'standard_users' => $standardCount,
                'total_applications' => $totalApplications,
                'completed_analyses' => $completedAnalyses,
                'total_reports' => $totalReports,
                'high_risk_findings' => $highRiskFindings,
                'recent_activities' => $recentActivities,
                'engine_health' => $engineHealth,
            ]);
        }

        // --- LEVEL 2: SECURITY ANALYST DASHBOARD ---
        if ($roleLevel === 2) {
            $totalApplications = Application::count();
            $completedAnalyses = Analysis::where('status', 'completed')->count();
            $activeScans = Application::where('status', 'analyzing')->count();
            $totalReports = Analysis::where('status', 'completed')->count();

            $criticalRisk = Analysis::where('status', 'completed')->where('risk_level', 'Critical')->count();
            $highRisk = Analysis::where('status', 'completed')->where('risk_level', 'High')->count();
            $mediumRisk = Analysis::where('status', 'completed')->where('risk_level', 'Medium')->count();
            $lowRisk = Analysis::where('status', 'completed')->where('risk_level', 'Low')->count();

            $engineHealth = $fastApi->checkEngineHealth();

            $recentScans = Analysis::with('application')
                ->where('status', 'completed')
                ->orderBy('id', 'desc')
                ->limit(5)
                ->get()
                ->map(fn($a) => [
                    'id' => $a->id,
                    'app_name' => $a->application?->name ?? 'Unknown',
                    'risk_level' => $a->risk_level,
                    'score' => $a->score,
                    'created_at' => $a->created_at->format('Y-m-d H:i:s'),
                ]);

            return response()->json([
                'dashboard_type' => 'analyst',
                'role_level' => 2,
                'total_applications' => $totalApplications,
                'completed_analyses' => $completedAnalyses,
                'active_scans' => $activeScans,
                'total_reports' => $totalReports,
                'risk_breakdown' => [
                    'critical' => $criticalRisk,
                    'high' => $highRisk,
                    'medium' => $mediumRisk,
                    'low' => $lowRisk,
                ],
                'recent_scans' => $recentScans,
                'engine_health' => $engineHealth,
            ]);
        }

        // --- LEVEL 1: STANDARD USER / VIEWER DASHBOARD ---
        $myApplications = Application::where('user_id', $user->id)->count();
        $myCompletedAnalyses = Analysis::whereHas('application', fn($q) => $q->where('user_id', $user->id))
            ->where('status', 'completed')
            ->count();
        $myReports = $myCompletedAnalyses;

        $myRecentAnalyses = Analysis::whereHas('application', fn($q) => $q->where('user_id', $user->id))
            ->with('application')
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'app_name' => $a->application?->name ?? 'Unknown',
                'status' => $a->status,
                'risk_level' => $a->risk_level,
                'created_at' => $a->created_at->format('Y-m-d H:i:s'),
            ]);

        $myRecentActivity = AuditLog::where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($l) => [
                'action' => $l->action,
                'target' => $l->target,
                'created_at' => $l->created_at->format('Y-m-d H:i:s'),
            ]);

        return response()->json([
            'dashboard_type' => 'standard',
            'role_level' => 1,
            'my_applications' => $myApplications,
            'my_reports' => $myReports,
            'my_completed_analyses' => $myCompletedAnalyses,
            'my_recent_analyses' => $myRecentAnalyses,
            'my_activity' => $myRecentActivity,
        ]);
    }
}
