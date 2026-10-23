<?php

namespace App\Http\Controllers;

use App\Models\Analysis;
use App\Models\Application;
use App\Models\AuditLog;
use App\Services\FastApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ApplicationController extends Controller
{
    protected FastApiService $fastApiService;

    public function __construct(FastApiService $fastApiService)
    {
        $this->fastApiService = $fastApiService;
    }

    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->hasPermission('applications.view')) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Forbidden - Access Denied', 'detail' => 'Missing permission: applications.view'], 403);
            }
            abort(403, 'Missing permission: applications.view');
        }

        $query = Application::with(['user', 'latestAnalysis'])->orderBy('id', 'desc');

        // Standard Users / Viewers can only view their own uploaded applications
        if ($user->isStandardUser()) {
            $query->where('user_id', $user->id);
        }

        $applications = $query->get();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($applications);
        }

        return view('applications.index', compact('applications'));
    }

    public function store(Request $request)
    {
        if (!$request->user()->hasPermission('applications.upload')) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Forbidden - Access Denied', 'detail' => 'Missing permission: applications.upload'], 403);
            }
            abort(403, 'Missing permission: applications.upload');
        }

        $validator = Validator::make($request->all(), [
            'apk_file' => [
                'required',
                'file',
                'max:102400', // Max 100MB
                function ($attribute, $value, $fail) {
                    $ext = strtolower($value->getClientOriginalExtension());
                    if ($ext !== 'apk') {
                        $fail('The uploaded file must have a .apk extension.');
                    }
                }
            ],
            'name' => ['nullable', 'string', 'max:100']
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Validation Failed',
                    'detail' => $validator->errors()->first()
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $file = $request->file('apk_file');
        $originalFilename = $file->getClientOriginalName();
        $appName = $request->input('name') ?: pathinfo($originalFilename, PATHINFO_FILENAME);
        $tempPath = $file->getRealPath();

        // Calculate SHA-256 Hash
        $sha256 = hash_file('sha256', $tempPath);
        $fileSize = $file->getSize();

        // Check Duplicate SHA-256
        $existingApp = Application::where('sha256', $sha256)->first();
        if ($existingApp) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Duplicate application detected',
                    'detail' => "An application with identical SHA-256 hash already exists: #{$existingApp->id} ({$existingApp->name})",
                    'application' => $existingApp
                ], 409);
            }
            return back()->withErrors(['apk_file' => "An application with identical SHA-256 hash already exists: {$existingApp->name}"]);
        }

        // Store file securely outside public web directory
        $uuidFilename = Str::uuid()->toString() . '.apk';
        $relativeStoragePath = $file->storeAs('private/apks', $uuidFilename);
        $fullPath = storage_path('app/' . $relativeStoragePath);

        // Create Application record
        $application = Application::create([
            'user_id' => $request->user()?->id ?? auth()->id(),
            'name' => $appName,
            'original_filename' => $originalFilename,
            'storage_path' => $relativeStoragePath,
            'sha256' => $sha256,
            'file_size' => $fileSize,
            'status' => 'analyzing',
        ]);

        AuditLog::log('Application Uploaded', $appName, ['sha256' => $sha256, 'app_id' => $application->id]);

        // Create Analysis queue record
        $analysis = Analysis::create([
            'application_id' => $application->id,
            'engine' => 'MobSF',
            'analysis_type' => 'static',
            'status' => 'analyzing',
        ]);

        AuditLog::log('Analysis Started', $appName, ['analysis_id' => $analysis->id]);

        // Call FastAPI Security Engine for MobSF Static Analysis
        $analysisResult = $this->fastApiService->analyzeApk($fullPath, $originalFilename, $sha256);

        if (isset($analysisResult['status']) && $analysisResult['status'] === 'completed') {
            $analysisData = $analysisResult['analysis'] ?? [];

            $analysis->update([
                'status' => 'completed',
                'risk_level' => $analysisData['risk_level'] ?? 'Unknown',
                'score' => $analysisData['score'] ?? 0,
                'findings_json' => $analysisResult['findings'] ?? [],
                'permissions_json' => $analysisResult['permissions'] ?? [],
                'components_json' => $analysisResult['components'] ?? [],
                'security_issues_json' => $analysisResult['security_issues'] ?? [],
                'raw_metadata_json' => $analysisResult['metadata'] ?? [],
            ]);

            $application->update([
                'status' => 'completed',
                'package_name' => $analysisResult['metadata']['package_name'] ?? null,
                'version_name' => $analysisResult['metadata']['version_name'] ?? null,
            ]);
        } else {
            $errorMsg = $analysisResult['error_message'] ?? 'MobSF analysis failed or engine unavailable.';

            $analysis->update([
                'status' => 'failed',
                'error_message' => $errorMsg,
            ]);

            $application->update([
                'status' => 'failed',
            ]);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Application uploaded and analyzed.',
                'application' => $application->load('latestAnalysis')
            ], 201);
        }

        return redirect()->route('applications.show', $application->id)->with('success', 'Application uploaded and processed.');
    }

    public function show(Request $request, Application $application)
    {
        $user = $request->user();

        if (!$user->hasPermission('applications.view')) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Forbidden - Access Denied', 'detail' => 'Missing permission: applications.view'], 403);
            }
            abort(403, 'Missing permission: applications.view');
        }

        // Standard User record-level isolation
        if ($user->isStandardUser() && $application->user_id !== $user->id) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Forbidden - Access Denied', 'detail' => 'You are not authorized to view this application.'], 403);
            }
            abort(403, 'Forbidden: You are not authorized to view this application.');
        }

        $application->load(['user', 'latestAnalysis', 'analyses']);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($application);
        }

        return view('applications.show', compact('application'));
    }

    public function destroy(Request $request, Application $application)
    {
        $user = $request->user();

        if (!$user->hasPermission('applications.delete')) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Forbidden - Access Denied', 'detail' => 'Missing permission: applications.delete'], 403);
            }
            abort(403, 'Missing permission: applications.delete');
        }

        // Standard User record-level isolation
        if ($user->isStandardUser() && $application->user_id !== $user->id) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Forbidden - Access Denied', 'detail' => 'You cannot delete applications owned by other users.'], 403);
            }
            abort(403, 'Forbidden: You cannot delete applications owned by other users.');
        }

        if (Storage::exists($application->storage_path)) {
            Storage::delete($application->storage_path);
        }

        $appName = $application->name;
        $application->delete();

        AuditLog::log('Application Deleted', $appName);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Application deleted successfully']);
        }

        return redirect()->route('applications.index')->with('success', 'Application removed.');
    }

    public function reanalyze(Request $request, Application $application)
    {
        $user = $request->user();

        if (!$user->hasPermission('analysis.start')) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Forbidden - Access Denied', 'detail' => 'Missing permission: analysis.start'], 403);
            }
            abort(403, 'Missing permission: analysis.start');
        }

        // Standard User record-level isolation
        if ($user->isStandardUser() && $application->user_id !== $user->id) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Forbidden - Access Denied', 'detail' => 'You cannot reanalyze applications owned by other users.'], 403);
            }
            abort(403, 'Forbidden: You cannot reanalyze applications owned by other users.');
        }

        $fullPath = storage_path('app/' . $application->storage_path);

        $application->update(['status' => 'analyzing']);

        $analysis = Analysis::create([
            'application_id' => $application->id,
            'engine' => 'MobSF',
            'analysis_type' => 'static',
            'status' => 'analyzing',
        ]);

        AuditLog::log('Analysis Re-started', $application->name, ['analysis_id' => $analysis->id]);

        $analysisResult = $this->fastApiService->analyzeApk($fullPath, $application->original_filename, $application->sha256);

        if (isset($analysisResult['status']) && $analysisResult['status'] === 'completed') {
            $analysisData = $analysisResult['analysis'] ?? [];

            $analysis->update([
                'status' => 'completed',
                'risk_level' => $analysisData['risk_level'] ?? 'Unknown',
                'score' => $analysisData['score'] ?? 0,
                'findings_json' => $analysisResult['findings'] ?? [],
                'permissions_json' => $analysisResult['permissions'] ?? [],
                'components_json' => $analysisResult['components'] ?? [],
                'security_issues_json' => $analysisResult['security_issues'] ?? [],
                'raw_metadata_json' => $analysisResult['metadata'] ?? [],
            ]);

            $application->update([
                'status' => 'completed',
                'package_name' => $analysisResult['metadata']['package_name'] ?? null,
                'version_name' => $analysisResult['metadata']['version_name'] ?? null,
            ]);
        } else {
            $errorMsg = $analysisResult['error_message'] ?? 'MobSF analysis failed or engine unavailable.';

            $analysis->update([
                'status' => 'failed',
                'error_message' => $errorMsg,
            ]);

            $application->update(['status' => 'failed']);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Re-analysis execution completed.',
                'application' => $application->load('latestAnalysis')
            ]);
        }

        return back()->with('success', 'Re-analysis finished.');
    }
}
