<?php

namespace App\Services;

use App\Models\Application;
use App\Services\Analysis\FindingNormalizer;
use App\Services\Contracts\SecurityToolServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MobSfService implements SecurityToolServiceInterface
{
    protected string $baseUrl;
    protected string $apiKey;
    protected int $timeout;
    protected FindingNormalizer $normalizer;

    public function __construct(?FindingNormalizer $normalizer = null)
    {
        $this->baseUrl = rtrim(config('services.mobsf.url', env('MOBSF_SERVICE_URL', 'http://127.0.0.1:8002')), '/');
        $this->apiKey = config('services.mobsf.api_key', env('MOBSF_API_KEY', 'erm_mobsf_api_key_secure_2026'));
        $this->timeout = (int) config('services.mobsf.timeout', 120);
        $this->normalizer = $normalizer ?? new FindingNormalizer();
    }

    public function getSlug(): string
    {
        return 'mobsf';
    }

    public function getName(): string
    {
        return 'MobSF (Mobile Security Framework)';
    }

    public function isOnline(): bool
    {
        $health = $this->checkHealth();
        return $health['online'] ?? false;
    }

    public function checkHealth(): array
    {
        try {
            $startTime = microtime(true);
            
            // Query MobSF api_docs or scans endpoint with API key
            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
                'Accept' => 'application/json',
            ])
            ->timeout(5)
            ->get("{$this->baseUrl}/api_docs");

            $latency = round((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                return [
                    'online' => true,
                    'status' => 'online',
                    'latency_ms' => $latency,
                    'service_url' => $this->baseUrl,
                    'engine' => 'MobSF REST API v1',
                ];
            }

            // Also fallback check on root path
            $rootResponse = Http::timeout(3)->get("{$this->baseUrl}/");
            if ($rootResponse->successful()) {
                return [
                    'online' => true,
                    'status' => 'online',
                    'latency_ms' => $latency,
                    'service_url' => $this->baseUrl,
                    'engine' => 'MobSF Web Engine',
                ];
            }

            return [
                'online' => false,
                'status' => 'offline',
                'latency_ms' => $latency,
                'service_url' => $this->baseUrl,
                'error' => "HTTP {$response->status()}",
            ];
        } catch (\Exception $e) {
            return [
                'online' => false,
                'status' => 'unreachable',
                'latency_ms' => null,
                'service_url' => $this->baseUrl,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getScans(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
            ])
            ->timeout(10)
            ->get("{$this->baseUrl}/api/v1/scans");

            return $response->successful() ? $response->json() : ['status' => 'error', 'scans' => []];
        } catch (\Exception $e) {
            Log::warning("MobSF getScans failed: " . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage(), 'scans' => []];
        }
    }

    public function uploadAndScan(string $filePath, string $fileName): array
    {
        if (!file_exists($filePath)) {
            return ['status' => 'error', 'message' => "Target file not found: {$filePath}"];
        }

        try {
            // 1. Upload APK
            $uploadResponse = Http::withHeaders([
                'Authorization' => $this->apiKey,
            ])
            ->timeout($this->timeout)
            ->attach('file', file_get_contents($filePath), $fileName)
            ->post("{$this->baseUrl}/api/v1/upload");

            if (!$uploadResponse->successful()) {
                return ['status' => 'error', 'message' => 'Upload failed: ' . $uploadResponse->body()];
            }

            $uploadData = $uploadResponse->json();
            $hash = $uploadData['hash'] ?? null;

            if (!$hash) {
                return ['status' => 'error', 'message' => 'Invalid upload response, missing hash'];
            }

            // 2. Scan APK
            $scanResponse = Http::withHeaders([
                'Authorization' => $this->apiKey,
            ])
            ->timeout($this->timeout)
            ->asForm()
            ->post("{$this->baseUrl}/api/v1/scan", [
                'hash' => $hash,
                'scan_type' => $uploadData['scan_type'] ?? 'apk',
                'file_name' => $fileName,
            ]);

            if (!$scanResponse->successful()) {
                return ['status' => 'error', 'message' => 'Scan initiation failed: ' . $scanResponse->body()];
            }

            // 3. Retrieve Report
            $reportResponse = Http::withHeaders([
                'Authorization' => $this->apiKey,
            ])
            ->timeout(30)
            ->asForm()
            ->post("{$this->baseUrl}/api/v1/report_json", [
                'hash' => $hash,
            ]);

            return [
                'status' => 'success',
                'hash' => $hash,
                'report' => $reportResponse->json() ?? [],
            ];
        } catch (\Exception $e) {
            Log::error("MobSF uploadAndScan exception: " . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function execute(Application $application, string $action = 'default', array $params = []): array
    {
        if (!$this->isOnline()) {
            return [
                'status' => 'standby',
                'message' => 'MobSF Service is active, but offline or unreachable.',
                'application_id' => $application->id,
                'findings' => [],
            ];
        }

        $apkPath = $params['file_path'] ?? $application->file_path ?? null;
        $fileName = $params['file_name'] ?? $application->file_name ?? "app_{$application->id}.apk";

        if ($apkPath && file_exists($apkPath)) {
            $scanResult = $this->uploadAndScan($apkPath, $fileName);
            if (($scanResult['status'] ?? '') === 'success') {
                $rawFindings = $scanResult['report']['findings'] ?? [];
                return [
                    'status' => 'success',
                    'hash' => $scanResult['hash'],
                    'findings' => $this->normalizer->normalizeList('mobsf', $rawFindings),
                    'scorecard' => $scanResult['report']['security_score'] ?? null,
                ];
            }
            return $scanResult;
        }

        return [
            'status' => 'ready',
            'message' => 'MobSF Service is online and awaiting APK upload.',
            'application_id' => $application->id,
            'package_name' => $application->package_name,
            'findings' => [],
        ];
    }
}
