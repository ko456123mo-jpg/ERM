<?php

namespace App\Services;

use App\Models\Application;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FastApiService
{
    protected string $baseUrl;
    protected int $timeout;

    public function __construct(private readonly InternalJwtService $jwt)
    {
        $this->baseUrl = rtrim((string) config('services.fastapi.url', 'http://127.0.0.1:8001'), '/');
        $this->timeout = max(10, (int) config('services.fastapi.timeout', 300));
    }

    public function analyzeApk(string $filePath, string $originalFilename, string $sha256): array
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            return ['status' => 'failed', 'error_message' => 'APK file missing or unreadable in private storage.'];
        }

        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            return ['status' => 'failed', 'error_message' => 'Could not open APK for analysis.'];
        }

        try {
            $safeFilename = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($originalFilename)) ?: 'application.apk';
            $response = Http::withToken($this->jwt->issue('analysis:run'))
                ->acceptJson()
                ->timeout($this->timeout)
                ->attach('file', $handle, $safeFilename, ['Content-Type' => 'application/vnd.android.package-archive'])
                ->post("{$this->baseUrl}/security/analyze", ['sha256' => $sha256]);

            if ($response->successful()) {
                return $response->json() ?? ['status' => 'failed', 'error_message' => 'Gateway returned invalid JSON.'];
            }

            return [
                'status' => 'failed',
                'error_message' => $response->json('detail') ?? "FastAPI returned HTTP {$response->status()}.",
            ];
        } catch (Throwable $exception) {
            Log::error('FastAPI APK analysis request failed.', ['exception' => $exception::class]);
            return ['status' => 'failed', 'error_message' => 'Analysis gateway request failed. Check service configuration and logs.'];
        } finally {
            fclose($handle);
        }
    }

    public function checkEngineHealth(): array
    {
        try {
            $startTime = microtime(true);
            $response = Http::withToken($this->jwt->issue('health:read'))
                ->acceptJson()->timeout(5)->get("{$this->baseUrl}/health");
            $latency = round((microtime(true) - $startTime) * 1000);

            return [
                'online' => $response->successful(),
                'latency_ms' => $latency,
                'engine_url' => $this->baseUrl,
                'details' => $response->successful() ? $response->json() : null,
                'error' => $response->successful() ? null : "HTTP {$response->status()}",
            ];
        } catch (Throwable $exception) {
            return ['online' => false, 'latency_ms' => null, 'engine_url' => $this->baseUrl, 'error' => 'Gateway unavailable or JWT configuration invalid.'];
        }
    }

    public function runDynamicTool(string $toolSlug, Application $application, string $action): array
    {
        if (!in_array($toolSlug, ['frida', 'objection'], true)) {
            return ['status' => 'error', 'message' => 'Unsupported tool.'];
        }

        try {
            $response = Http::withToken($this->jwt->issue('tools:execute'))
                ->acceptJson()->timeout($this->timeout)
                ->post("{$this->baseUrl}/tools/{$toolSlug}/execute", [
                    'application_id' => $application->id,
                    'package_name' => $application->package_name,
                    'action' => $action,
                ]);

            return $response->successful()
                ? ($response->json() ?? ['status' => 'error', 'message' => 'Gateway returned invalid JSON.'])
                : ['status' => 'error', 'message' => "Tool gateway returned HTTP {$response->status()}."];
        } catch (Throwable $exception) {
            Log::warning('FastAPI tool execution request failed.', ['tool' => $toolSlug, 'exception' => $exception::class]);
            return ['status' => 'engine_unavailable', 'message' => 'Tool gateway is unavailable.'];
        }
    }
}
