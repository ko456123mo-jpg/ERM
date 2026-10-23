<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\FridaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FridaController extends Controller
{
    public function __construct(private readonly FridaService $fridaService)
    {
    }

    public function status(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_serial' => ['nullable', 'string', 'max:128', 'regex:/^[A-Za-z0-9._:-]+$/'],
        ]);

        $result = isset($validated['device_serial'])
            ? $this->fridaService->serverStatus($validated['device_serial'])
            : $this->fridaService->listAdbDevices();

        AuditLog::log('Frida Server Status Checked', $validated['device_serial'] ?? null, [
            'success' => $result['success'] ?? false,
            'running' => $result['running'] ?? null,
        ]);

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_serial' => ['nullable', 'string', 'max:128', 'regex:/^[A-Za-z0-9._:-]+$/'],
        ]);

        $result = $this->fridaService->startServer($validated['device_serial'] ?? null);
        AuditLog::log('Frida Server Start Requested', $result['serial'] ?? ($validated['device_serial'] ?? null), [
            'success' => $result['success'] ?? false,
            'message' => $result['message'] ?? null,
        ]);

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    public function stop(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_serial' => ['nullable', 'string', 'max:128', 'regex:/^[A-Za-z0-9._:-]+$/'],
        ]);

        $result = $this->fridaService->stopServer($validated['device_serial'] ?? null);
        AuditLog::log('Frida Server Stop Requested', $result['serial'] ?? ($validated['device_serial'] ?? null), [
            'success' => $result['success'] ?? false,
            'message' => $result['message'] ?? null,
        ]);

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }
}
