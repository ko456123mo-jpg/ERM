<?php

namespace App\Services;

use App\Models\Application;
use App\Services\Contracts\SecurityToolServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FridaService implements SecurityToolServiceInterface
{
    protected string $baseUrl;
    protected int $timeout;

    public function __construct(private readonly InternalJwtService $jwt)
    {
        $this->baseUrl = rtrim((string) config('services.frida.url', 'http://127.0.0.1:8001'), '/');
        $this->timeout = max(5, (int) config('services.frida.timeout', 30));
    }

    public function getSlug(): string { return 'frida'; }
    public function getName(): string { return 'Frida Dynamic Instrumenter'; }

    public function isOnline(): bool
    {
        return (bool) ($this->checkHealth()['online'] ?? false);
    }

    public function checkHealth(): array
    {
        $started = microtime(true);
        $result = $this->request('GET', '/health', [], 'health:read', 5);
        return [
            'online' => ($result['http_status'] ?? 500) < 400,
            'status' => ($result['http_status'] ?? 500) < 400 ? 'online' : 'offline',
            'latency_ms' => round((microtime(true) - $started) * 1000),
            'service_url' => $this->baseUrl,
            'details' => $result['data'] ?? null,
            'error' => $result['error'] ?? null,
        ];
    }

    /** ADB device discovery is performed inside the isolated Frida container. */
    public function listAdbDevices(): array
    {
        return $this->gatewayResult($this->request('GET', '/devices/adb', [], 'devices:read'));
    }

    public function serverStatus(?string $serial = null): array
    {
        return $this->gatewayResult($this->request(
            'GET', '/frida-server/status', array_filter(['device_serial' => $serial]), 'devices:read'
        ));
    }

    public function startServer(?string $serial = null): array
    {
        return $this->gatewayResult($this->request(
            'POST', '/frida-server/start', array_filter(['device_serial' => $serial]), 'devices:manage', 120
        ));
    }

    public function stopServer(?string $serial = null): array
    {
        return $this->gatewayResult($this->request(
            'POST', '/frida-server/stop', array_filter(['device_serial' => $serial]), 'devices:manage', 30
        ));
    }

    public function getDevices(): array
    {
        return $this->gatewayResult($this->request('GET', '/devices', [], 'devices:read'));
    }

    public function connectRemoteAdb(string $host, int $port = 5555): array
    {
        return $this->gatewayResult($this->request('POST', '/devices/connect', ['host' => $host, 'port' => $port], 'devices:manage', 15));
    }

    public function getDeviceApplications(string $deviceId): array
    {
        return $this->gatewayResult($this->request('GET', '/devices/' . rawurlencode($deviceId) . '/applications', [], 'devices:read'));
    }

    public function getDeviceProcesses(string $deviceId): array
    {
        return $this->gatewayResult($this->request('GET', '/devices/' . rawurlencode($deviceId) . '/processes', [], 'devices:read'));
    }

    public function createSession(string $target, string $mode = 'spawn', ?string $deviceId = null): array
    {
        return $this->gatewayResult($this->request('POST', '/sessions', [
            'target' => $target, 'mode' => $mode, 'device_id' => $deviceId,
        ], 'tools:execute', $this->timeout));
    }

    public function injectScript(string $sessionId, string $script): array
    {
        return $this->gatewayResult($this->request('POST', '/sessions/' . rawurlencode($sessionId) . '/scripts', [
            'script' => $script,
        ], 'tools:execute', $this->timeout));
    }

    public function getSessionOutput(string $sessionId): array
    {
        return $this->gatewayResult($this->request('GET', '/sessions/' . rawurlencode($sessionId) . '/output', [], 'tools:execute'));
    }

    public function stopSession(string $sessionId): array
    {
        return $this->gatewayResult($this->request('POST', '/sessions/' . rawurlencode($sessionId) . '/stop', [], 'tools:execute'));
    }

    public function execute(Application $application, string $action = 'default', array $params = []): array
    {
        return $this->gatewayResult($this->request('POST', '/tools/frida/execute', [
            'application_id' => $application->id,
            'package_name' => $application->package_name,
            'action' => $action,
            'device_id' => $params['device_id'] ?? null,
        ], 'tools:execute', $this->timeout));
    }

    private function request(string $method, string $path, array $payload, string $scope, int $timeout = 10): array
    {
        try {
            $request = Http::withToken($this->jwt->issue($scope))
                ->acceptJson()->timeout($timeout);

            $response = $method === 'GET'
                ? $request->get($this->baseUrl . $path, $payload)
                : $request->post($this->baseUrl . $path, $payload);

            return [
                'http_status' => $response->status(),
                'data' => $response->json(),
                'error' => $response->successful() ? null : "Gateway HTTP {$response->status()}.",
            ];
        } catch (Throwable $exception) {
            Log::warning('Frida gateway request failed.', ['path' => $path, 'exception' => $exception::class]);
            return ['http_status' => 503, 'error' => 'Frida gateway unavailable or JWT configuration invalid.'];
        }
    }

    private function gatewayResult(array $response): array
    {
        if (($response['http_status'] ?? 500) >= 400) {
            return ['success' => false, 'status' => 'error', 'message' => $response['error'] ?? 'Frida gateway request failed.'];
        }
        return is_array($response['data'] ?? null) ? $response['data'] : ['success' => true, 'data' => $response['data'] ?? null];
    }
}
