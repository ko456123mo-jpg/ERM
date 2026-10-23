<?php

namespace App\Http\Controllers;

use App\Models\Analysis;
use App\Models\Application;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AiAnalysisController extends Controller
{
    public function analyze(Request $request)
    {
        $request->validate([
            'application_id' => ['required', 'exists:applications,id'],
        ]);

        $user = $request->user();
        $app = Application::with('latestAnalysis')->findOrFail($request->input('application_id'));

        // Standard User ownership check
        if ($user->isStandardUser() && $app->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden - Access Denied', 'detail' => 'You cannot run AI analysis on applications owned by other users.'], 403);
        }

        $analysis = $app->latestAnalysis;

        if (!$analysis || $analysis->status !== 'completed') {
            return response()->json([
                'message' => 'No completed analysis found for this application. Run a security scan first.',
            ], 422);
        }

        $findings = $analysis->findings_json ?? [];
        $permissions = $analysis->permissions_json ?? [];

        // Generate AI analysis based on real scan data
        $highCount = collect($findings)->filter(fn($f) => in_array(strtolower($f['severity'] ?? ''), ['high', 'critical']))->count();
        $mediumCount = collect($findings)->filter(fn($f) => strtolower($f['severity'] ?? '') === 'medium')->count();
        $dangerousPerms = collect($permissions)->filter(fn($p) => is_array($p) && ($p['status'] ?? '') === 'dangerous')->count();

        $riskPosture = $highCount >= 2 ? 'CRITICAL RISK' : ($highCount >= 1 ? 'HIGH RISK' : ($mediumCount >= 2 ? 'MODERATE RISK' : 'LOW RISK'));

        $summaryNarrative = "Comprehensive AI security review for '{$app->name}' based on reverse engineering analysis. "
            . "Detected {$highCount} high/critical severity findings, {$mediumCount} medium findings, "
            . "and {$dangerousPerms} dangerous Android permissions. "
            . "Overall risk posture: {$riskPosture}.";

        // Generate OWASP categorization from findings
        $owaspCategories = [];
        foreach ($findings as $f) {
            $owasp = $f['owasp'] ?? 'M1: Improper Platform Usage';
            $owaspCategories[$owasp] = ($owaspCategories[$owasp] ?? 0) + 1;
        }

        // Generate remediation suggestions
        $remediations = [];
        foreach ($findings as $f) {
            $remediations[] = [
                'issue' => $f['title'] ?? 'Security Issue',
                'severity' => strtoupper($f['severity'] ?? 'INFO'),
                'recommendation' => $this->generateRemediation($f),
            ];
        }

        AuditLog::log('AI Security Analysis Generated', $app->name, ['analysis_id' => $analysis->id]);

        return response()->json([
            'message' => 'AI security analysis generated successfully.',
            'data' => [
                'application' => $app->name,
                'risk_posture' => $riskPosture,
                'summary_narrative' => $summaryNarrative,
                'stats' => [
                    'high_critical' => $highCount,
                    'medium' => $mediumCount,
                    'low_info' => count($findings) - $highCount - $mediumCount,
                    'dangerous_permissions' => $dangerousPerms,
                ],
                'owasp_breakdown' => $owaspCategories,
                'remediations' => $remediations,
            ],
        ]);
    }

    private function generateRemediation(array $finding): string
    {
        $title = strtolower($finding['title'] ?? '');

        if (str_contains($title, 'keystore') || str_contains($title, 'storage')) {
            return 'Use Android Keystore System (AndroidKeyStore provider) with AES-256 encryption. Never store credentials in SharedPreferences or plain files.';
        }
        if (str_contains($title, 'backup') || str_contains($title, 'allowbackup')) {
            return 'Set android:allowBackup="false" in AndroidManifest.xml to prevent ADB data extraction attacks.';
        }
        if (str_contains($title, 'cleartext') || str_contains($title, 'http')) {
            return 'Enforce HTTPS-only by setting android:usesCleartextTraffic="false" and configure a Network Security Config with certificate pinning.';
        }
        if (str_contains($title, 'ssl') || str_contains($title, 'pinning')) {
            return 'Implement certificate pinning using OkHttp CertificatePinner or TrustManager with SHA-256 pin validation.';
        }
        if (str_contains($title, 'root') || str_contains($title, 'detection')) {
            return 'Integrate root/jailbreak detection using SafetyNet Attestation API or RootBeer library to detect tampered environments.';
        }

        return 'Review the identified issue and apply security hardening per OWASP Mobile Security Testing Guide (MSTG) recommendations.';
    }
}
