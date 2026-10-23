<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\AuditLog;
use App\Services\JadxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class JadxController extends Controller
{
    public function __construct(private readonly JadxService $jadxService)
    {
    }

    public function status(): JsonResponse
    {
        $health = $this->jadxService->checkHealth();

        return response()->json($health, $health['available'] ? 200 : 503);
    }

    public function decompile(Request $request, Application $application): JsonResponse
    {
        $user = $request->user();

        if ($user->isStandardUser() && (int) $application->user_id !== (int) $user->id) {
            return response()->json([
                'message' => 'Forbidden - Access Denied',
                'detail' => 'You cannot decompile applications owned by other users.',
            ], 403);
        }

        try {
            $result = $this->jadxService->decompile($application);

            AuditLog::log('JADX Decompilation Completed', $application->name, [
                'application_id' => $application->id,
                'run_id' => $result['run_id'],
                'file_count' => $result['file_count'],
            ]);

            return response()->json([
                'message' => 'APK decompiled successfully.',
                'result' => $result,
            ]);
        } catch (RuntimeException $exception) {
            report($exception);

            return response()->json([
                'message' => 'JADX could not decompile this APK.',
                'detail' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'An unexpected error occurred while decompiling the APK.',
            ], 500);
        }
    }
}
