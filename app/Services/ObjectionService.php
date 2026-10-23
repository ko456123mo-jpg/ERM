<?php

namespace App\Services;

use App\Models\Application;
use App\Services\Analysis\FindingNormalizer;
use App\Services\Contracts\SecurityToolServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ObjectionService implements SecurityToolServiceInterface
{
    protected string $baseUrl;
    protected int $timeout;
    protected FindingNormalizer $normalizer;

    public function __construct(?FindingNormalizer $normalizer = null)
    {
        $this->baseUrl = rtrim(config('services.objection.url', env('OBJECTION_SERVICE_URL', 'http://127.0.0.1:8003')), '/');
        $this->timeout = (int) config('services.objection.timeout', 60);
        $this->normalizer = $normalizer ?? new FindingNormalizer();
    }

    public function getSlug(): string
    {
        return 'objection';
    }

    public function getName(): string
    {
        return 'Objection Mobile Exploration';
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
            $response = Http::timeout(4)
                ->acceptJson()
                ->get("{$this->baseUrl}/health");

            $latency = round((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'online' => true,
                    'status' => 'online',
                    'latency_ms' => $latency,
                    'service_url' => $this->baseUrl,
                    'details' => $data,
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

    public function getDevices(): array
    {
        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->get("{$this->baseUrl}/devices");

            return $response->successful() ? $response->json() : ['status' => 'error', 'devices' => []];
        } catch (\Exception $e) {
            Log::warning("Objection getDevices failed: " . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage(), 'devices' => []];
        }
    }

    public function connectRemoteAdb(string $host, int $port = 5555): array
    {
        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->post("{$this->baseUrl}/devices/connect", [
                    'host' => $host,
                    'port' => $port,
                ]);

            return $response->json();
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function execute(Application $application, string $action = 'default', array $params = []): array
    {
        if (!$this->isOnline()) {
            return [
                'status' => 'standby',
                'message' => 'Objection Service is currently in standby / offline.',
                'application_id' => $application->id,
                'findings' => [],
            ];
        }

        try {
            $packageName = $application->package_name ?? "com.erm.target{$application->id}";
            $targetAction = $action === 'default' ? 'ssl_pinning_disable' : $action;

            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->post("{$this->baseUrl}/tools/objection/execute", [
                    'application_id' => $application->id,
                    'package_name' => $packageName,
                    'action' => $targetAction,
                    'device_id' => $params['device_id'] ?? null,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $rawFindings = $data['findings'] ?? [];
                return [
                    'status' => $data['status'] ?? 'completed',
                    'action' => $targetAction,
                    'target' => $packageName,
                    'output' => $data['stdout'] ?? $data['message'] ?? '',
                    'findings' => $this->normalizer->normalizeList('objection', $rawFindings),
                    'raw_response' => $data,
                ];
            }

            return [
                'status' => 'error',
                'message' => "Objection returned HTTP {$response->status()}",
                'details' => $response->body(),
                'findings' => [],
            ];
        } catch (\Exception $e) {
            Log::error("Objection execute exception: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'findings' => [],
            ];
        }
    }
}
