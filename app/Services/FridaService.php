<?php

namespace App\Services;

use App\Models\Application;
use App\Services\Contracts\SecurityToolServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FridaService implements SecurityToolServiceInterface
{
    protected string $baseUrl;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.frida.url', env('FRIDA_SERVICE_URL', 'http://127.0.0.1:8001')), '/');
        $this->timeout = (int) config('services.frida.timeout', 30);
    }

    public function getSlug(): string
    {
        return 'frida';
    }

    public function getName(): string
    {
        return 'Frida Dynamic Instrumenter';
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

            if ($response->successful()) {
                return $response->json();
            }
            return ['status' => 'error', 'message' => "HTTP {$response->status()}", 'devices' => []];
        } catch (\Exception $e) {
            Log::warning("Frida getDevices failed: " . $e->getMessage());
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

    public function getDeviceApplications(string $deviceId): array
    {
        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->get("{$this->baseUrl}/devices/{$deviceId}/applications");

            return $response->json();
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function getDeviceProcesses(string $deviceId): array
    {
        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->get("{$this->baseUrl}/devices/{$deviceId}/processes");

            return $response->json();
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function createSession(string $target, string $mode = 'spawn', ?string $deviceId = null): array
    {
        try {
            $payload = [
                'target' => $target,
                'mode' => $mode,
            ];
            if ($deviceId) {
                $payload['device_id'] = $deviceId;
            }

            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->post("{$this->baseUrl}/sessions", $payload);

            return $response->json();
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function injectScript(string $sessionId, string $script): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->post("{$this->baseUrl}/sessions/{$sessionId}/scripts", [
                    'script' => $script,
                ]);

            return $response->json();
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function getSessionOutput(string $sessionId): array
    {
        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->get("{$this->baseUrl}/sessions/{$sessionId}/output");

            return $response->json();
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function stopSession(string $sessionId): array
    {
        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->post("{$this->baseUrl}/sessions/{$sessionId}/stop");

            return $response->json();
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function execute(Application $application, string $action = 'default', array $params = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->post("{$this->baseUrl}/tools/frida/execute", [
                    'application_id' => $application->id,
                    'package_name' => $application->package_name,
                    'action' => $action,
                    'device_id' => $params['device_id'] ?? null,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'status' => 'error',
                'message' => "Frida service responded with status HTTP {$response->status()}",
                'output' => $response->body(),
            ];
        } catch (\Exception $e) {
            Log::warning("FridaService execution failed: " . $e->getMessage());
            return [
                'status' => 'standby',
                'message' => "Frida Service is unreachable at {$this->baseUrl}. " . $e->getMessage(),
                'diagnostic' => [
                    'hint' => 'Verify that the Frida container is running in Docker and reachable on ' . $this->baseUrl
                ]
            ];
        }
    }
}
