<?php

namespace App\Http\Controllers;

use App\Models\Analysis;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->hasPermission('reports.view')) {
            return response()->json(['message' => 'Forbidden - Access Denied', 'detail' => 'Missing permission: reports.view'], 403);
        }

        $query = Analysis::with('application')
            ->where('status', 'completed')
            ->orderBy('id', 'desc');

        // Standard Users / Viewers only see reports for their own applications
        if ($user->isStandardUser()) {
            $query->whereHas('application', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        if ($request->filled('risk_level')) {
            $query->where('risk_level', $request->input('risk_level'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('application', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('package_name', 'like', "%{$search}%");
            });
        }

        $reports = $query->limit(100)->get();

        return response()->json($reports->map(fn($a) => [
            'id' => $a->id,
            'application_id' => $a->application_id,
            'application_name' => $a->application?->name ?? 'N/A',
            'package_name' => $a->application?->package_name,
            'engine' => $a->engine,
            'analysis_type' => $a->analysis_type,
            'risk_level' => $a->risk_level,
            'score' => $a->score,
            'findings_count' => count($a->findings_json ?? []),
            'created_at' => $a->created_at->format('Y-m-d H:i:s'),
        ]));
    }

    public function show(Request $request, Analysis $analysis)
    {
        $user = $request->user();

        if (!$user->hasPermission('reports.view')) {
            return response()->json(['message' => 'Forbidden - Access Denied', 'detail' => 'Missing permission: reports.view'], 403);
        }

        $analysis->load('application');

        // Ownership check for standard users
        if ($user->isStandardUser() && $analysis->application?->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden - Access Denied', 'detail' => 'You are not authorized to view this report.'], 403);
        }

        return response()->json([
            'id' => $analysis->id,
            'application' => [
                'id' => $analysis->application?->id,
                'name' => $analysis->application?->name,
                'package_name' => $analysis->application?->package_name,
                'sha256' => $analysis->application?->sha256,
            ],
            'engine' => $analysis->engine,
            'analysis_type' => $analysis->analysis_type,
            'risk_level' => $analysis->risk_level,
            'score' => $analysis->score,
            'status' => $analysis->status,
            'findings' => $analysis->findings_json ?? [],
            'permissions' => $analysis->permissions_json ?? [],
            'components' => $analysis->components_json ?? [],
            'security_issues' => $analysis->security_issues_json ?? [],
            'created_at' => $analysis->created_at->format('Y-m-d H:i:s'),
        ]);
    }

    public function download(Request $request, Analysis $analysis)
    {
        $user = $request->user();

        if (!$user->hasPermission('reports.download')) {
            return response()->json(['message' => 'Forbidden - Access Denied', 'detail' => 'Missing permission: reports.download'], 403);
        }

        $analysis->load('application');

        // Ownership check for standard users
        if ($user->isStandardUser() && $analysis->application?->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden - Access Denied', 'detail' => 'You are not authorized to download this report.'], 403);
        }

        $report = [
            'report_title' => 'ERM Security Analysis Report',
            'generated_at' => now()->toIso8601String(),
            'application' => [
                'name' => $analysis->application?->name,
                'package_name' => $analysis->application?->package_name,
                'version' => $analysis->application?->version_name,
                'sha256' => $analysis->application?->sha256,
                'file_size_bytes' => $analysis->application?->file_size,
            ],
            'analysis' => [
                'engine' => $analysis->engine,
                'type' => $analysis->analysis_type,
                'risk_level' => $analysis->risk_level,
                'score' => $analysis->score,
                'status' => $analysis->status,
            ],
            'findings' => $analysis->findings_json ?? [],
            'permissions' => $analysis->permissions_json ?? [],
            'components' => $analysis->components_json ?? [],
            'security_issues' => $analysis->security_issues_json ?? [],
        ];

        $filename = 'ERM_Report_' . str_replace(' ', '_', $analysis->application?->name ?? 'Unknown') . '_' . now()->format('Ymd_His') . '.json';

        AuditLog::log('Report Downloaded', $analysis->application?->name ?? 'Unknown', ['analysis_id' => $analysis->id]);

        return response()->json($report)
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->header('Content-Type', 'application/json');
    }

    public function destroy(Request $request, Analysis $analysis)
    {
        $user = $request->user();

        if (!$user->hasPermission('reports.delete')) {
            return response()->json(['message' => 'Forbidden - Access Denied', 'detail' => 'Missing permission: reports.delete'], 403);
        }

        // Ownership check for standard users
        if ($user->isStandardUser() && $analysis->application?->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden - Access Denied', 'detail' => 'You cannot delete reports for other users.'], 403);
        }

        $appName = $analysis->application?->name ?? 'Unknown';
        $analysisId = $analysis->id;

        $analysis->delete();

        AuditLog::log('Report Deleted', $appName, ['analysis_id' => $analysisId]);

        return response()->json(['message' => "Report for '{$appName}' deleted successfully."]);
    }
}
