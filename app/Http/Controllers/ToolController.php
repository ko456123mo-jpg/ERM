<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\AuditLog;
use App\Services\FastApiService;
use App\Services\FridaService;
use App\Services\MobSfService;
use App\Services\ObjectionService;
use Illuminate\Http\Request;

class ToolController extends Controller
{
    protected FastApiService $fastApiService;
    protected FridaService $fridaService;
    protected MobSfService $mobSfService;
    protected ObjectionService $objectionService;

    public function __construct(
        FastApiService $fastApiService,
        FridaService $fridaService,
        MobSfService $mobSfService,
        ObjectionService $objectionService
    ) {
        $this->fastApiService = $fastApiService;
        $this->fridaService = $fridaService;
        $this->mobSfService = $mobSfService;
        $this->objectionService = $objectionService;
    }

    public function status(Request $request)
    {
        $fastApiHealth = $this->fastApiService->checkEngineHealth();
        $fridaHealth = $this->fridaService->checkHealth();
        $mobsfHealth = $this->mobSfService->checkHealth();
        $objectionHealth = $this->objectionService->checkHealth();

        $tools = [
            [
                'name' => 'Frida Dynamic Instrumenter',
                'slug' => 'frida',
                'description' => 'Dynamic instrumentation toolkit for runtime hooking, SSL pinning bypass, and method tracing.',
                'capabilities' => ['Runtime Hooking', 'SSL Pinning Bypass', 'Method Tracing', 'Memory Inspection', 'API Monitoring'],
                'status' => $fridaHealth['online'] ? 'online' : ($fastApiHealth['online'] ? 'online' : 'standby'),
                'latency_ms' => $fridaHealth['latency_ms'] ?? $fastApiHealth['latency_ms'] ?? null,
                'endpoint' => $fridaHealth['service_url'] ?? null,
            ],
            [
                'name' => 'MobSF (Mobile Security Framework)',
                'slug' => 'mobsf',
                'description' => 'Static binary analysis engine for Android APK decompilation, manifest inspection, and vulnerability scanning.',
                'capabilities' => ['APK Decompilation', 'Manifest Analysis', 'Code Review', 'Vulnerability Scanning', 'Permission Mapping'],
                'status' => $mobsfHealth['online'] ? 'online' : 'standby',
                'latency_ms' => $mobsfHealth['latency_ms'] ?? null,
                'endpoint' => $mobsfHealth['service_url'] ?? null,
            ],
            [
                'name' => 'Objection Mobile Exploration',
                'slug' => 'objection',
                'description' => 'Runtime mobile exploration powered by Frida for file system, keychain, and SQLite inspection.',
                'capabilities' => ['File System Browse', 'Keychain Dump', 'SQLite Analysis', 'SSL Pinning Bypass', 'Root Detection Bypass'],
                'status' => $objectionHealth['online'] ? 'online' : 'standby',
                'latency_ms' => $objectionHealth['latency_ms'] ?? null,
                'endpoint' => $objectionHealth['service_url'] ?? null,
            ],
        ];

        $isEngineOnline = $fridaHealth['online'] || $fastApiHealth['online'];

        return response()->json([
            'overall_status' => $isEngineOnline ? 'Engine Online' : 'Standby / Local',
            'engine_url' => $fridaHealth['service_url'] ?? $fastApiHealth['engine_url'],
            'tools' => $tools,
        ]);
    }

    public function execute(Request $request)
    {
        $request->validate([
            'tool' => ['required', 'string', 'in:mobsf,frida,objection'],
            'application_id' => ['required', 'exists:applications,id'],
            'action' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $app = Application::findOrFail($request->input('application_id'));

        // Standard User ownership check
        if ($user->isStandardUser() && $app->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden - Access Denied', 'detail' => 'You cannot execute tools on applications owned by other users.'], 403);
        }

        $tool = $request->input('tool');
        $action = $request->input('action', 'default');

        // Route execution to dedicated service
        if ($tool === 'frida') {
            $result = $this->fridaService->execute($app, $action);
        } elseif ($tool === 'mobsf') {
            $result = $this->mobSfService->execute($app, $action);
        } elseif ($tool === 'objection') {
            $result = $this->objectionService->execute($app, $action);
        } else {
            $result = $this->fastApiService->runDynamicTool($tool, $app, $action);
        }

        AuditLog::log("Tool Executed: {$tool}", $app->name, [
            'tool' => $tool,
            'action' => $action,
            'app_id' => $app->id,
        ]);

        return response()->json([
            'message' => "Tool '{$tool}' executed for {$app->name}",
            'tool' => $tool,
            'application' => $app->name,
            'result' => $result,
        ]);
    }
}
