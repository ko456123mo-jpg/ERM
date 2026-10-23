<?php

namespace App\Services;

use App\Models\Application;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FastApiService
{
    protected string $baseUrl;
    protected string $secretKey;

    public function __construct()
    {
        $this->baseUrl = config('services.fastapi.url', env('FASTAPI_ENGINE_URL', 'http://127.0.0.1:8000/api'));
        $this->secretKey = config('services.fastapi.secret', env('FASTAPI_SHARED_SECRET', 'erm_enterprise_secret_key_change_in_production_2026'));
    }

    public function analyzeApk(string $filePath, string $originalFilename, string $sha256): array
    {
        if (!file_exists($filePath)) {
            return [
                'status' => 'failed',
                'error_message' => 'APK file missing from storage path.'
            ];
        }

        try {
            $response = Http::withHeaders([
                'X-ERM-Internal-Key' => $this->secretKey,
                'Accept' => 'application/json'
            ])
            ->timeout(120)
            ->attach(
                'file',
                file_get_contents($filePath),
                $originalFilename
            )
            ->post("{$this->baseUrl}/security/analyze", [
                'sha256' => $sha256
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            $body = $response->json();
            return [
                'status' => 'failed',
                'error_message' => $body['detail'] ?? "FastAPI returned error HTTP status {$response->status()}"
            ];
        } catch (\Exception $e) {
            Log::error("FastApiService Connection Failed: " . $e->getMessage());
            return [
                'status' => 'failed',
                'error_message' => "FastAPI engine unreachable at {$this->baseUrl}. " . $e->getMessage()
            ];
        }
    }

    /**
     * Check engine health / connectivity.
     */
    public function checkEngineHealth(): array
    {
        try {
            $startTime = microtime(true);
            $response = Http::withHeaders([
                'X-ERM-Internal-Key' => $this->secretKey,
                'Accept' => 'application/json',
            ])
            ->timeout(5)
            ->get("{$this->baseUrl}/health");

            $latency = round((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                return [
                    'online' => true,
                    'latency_ms' => $latency,
                    'engine_url' => $this->baseUrl,
                    'details' => $response->json(),
                ];
            }

            return [
                'online' => false,
                'latency_ms' => $latency,
                'engine_url' => $this->baseUrl,
                'error' => "HTTP {$response->status()}",
            ];
        } catch (\Exception $e) {
            return [
                'online' => false,
                'latency_ms' => null,
                'engine_url' => $this->baseUrl,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Execute a dynamic analysis tool (Frida, Objection, etc.) against an application.
     */
    public function runDynamicTool(string $toolSlug, Application $application, string $action): array
    {
        try {
            $response = Http::withHeaders([
                'X-ERM-Internal-Key' => $this->secretKey,
                'Accept' => 'application/json',
            ])
            ->timeout(60)
            ->post("{$this->baseUrl}/tools/{$toolSlug}/execute", [
                'application_id' => $application->id,
                'package_name' => $application->package_name,
                'action' => $action,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'status' => 'error',
                'message' => "Tool '{$toolSlug}' returned HTTP {$response->status()}",
                'output' => $response->body(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'engine_unavailable',
                'message' => "FastAPI engine is not running. Tool '{$toolSlug}' queued locally.",
                'output' => "Connection error: " . $e->getMessage(),
            ];
        }
    }
}
