<?php

namespace App\Services;

use App\Models\Application;
use App\Services\Contracts\SecurityToolServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Throwable;

class FridaService implements SecurityToolServiceInterface
{
    protected string $baseUrl;
    protected int $timeout;
    protected string $adbBinary;
    protected string $fridaServerBinary;
    protected string $remoteServerPath;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.frida.url', env('FRIDA_SERVICE_URL', 'http://127.0.0.1:8001')), '/');
        $this->timeout = (int) config('services.frida.timeout', 30);
        $this->adbBinary = (string) config('services.frida.adb_binary', '/usr/bin/adb');
        $this->fridaServerBinary = (string) config('services.frida.server_binary', '');
        $this->remoteServerPath = '/data/local/tmp/frida-server';
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

    /** List connected ADB devices with their authorization state. */
    public function listAdbDevices(): array
    {
        try {
            $process = $this->runAdb(['devices'], 15);
            if (!$process->isSuccessful()) {
                return [
                    'success' => false,
                    'message' => trim($process->getErrorOutput()) ?: 'ADB devices command failed.',
                    'devices' => [],
                ];
            }

            $devices = [];
            foreach (preg_split('/\r?\n/', trim($process->getOutput())) as $line) {
                if (str_starts_with($line, 'List of devices attached') || trim($line) === '') {
                    continue;
                }
                if (preg_match('/^([A-Za-z0-9._:-]+)\s+(device|offline|unauthorized)(?:\s+(.*))?$/', trim($line), $match)) {
                    $devices[] = [
                        'serial' => $match[1],
                        'state' => $match[2],
                        'details' => $match[3] ?? '',
                    ];
                }
            }

            return ['success' => true, 'devices' => $devices];
        } catch (Throwable $exception) {
            Log::warning('ADB device listing failed: ' . $exception->getMessage());
            return ['success' => false, 'message' => $exception->getMessage(), 'devices' => []];
        }
    }

    /** Check the selected device and whether frida-server is running. */
    public function serverStatus(?string $serial = null): array
    {
        $device = $this->resolveDevice($serial);
        if (!$device['success']) {
            return $device;
        }

        try {
            $process = $this->runAdb([
                '-s', $device['serial'], 'shell', 'su', '-c', 'pidof frida-server',
            ], 15);
            $pid = trim($process->getOutput());
            $running = $process->isSuccessful() && $pid !== '';

            return [
                'success' => true,
                'serial' => $device['serial'],
                'device_state' => 'device',
                'running' => $running,
                'pid' => $running ? $pid : null,
                'message' => $running ? 'Frida server is running.' : 'Frida server is not running (or root access is unavailable).',
            ];
        } catch (Throwable $exception) {
            return ['success' => false, 'serial' => $device['serial'], 'message' => $exception->getMessage()];
        }
    }

    /** Push, mark executable, and start the configured frida-server on a rooted device. */
    public function startServer(?string $serial = null): array
    {
        if ($this->fridaServerBinary === '' || !is_file($this->fridaServerBinary) || !is_readable($this->fridaServerBinary)) {
            return ['success' => false, 'message' => 'FRIDA_SERVER_BINARY is unset or does not point to a readable file.'];
        }

        $device = $this->resolveDevice($serial);
        if (!$device['success']) {
            return $device;
        }

        try {
            $push = $this->runAdb([
                '-s', $device['serial'], 'push', $this->fridaServerBinary, $this->remoteServerPath,
            ], 120);
            if (!$push->isSuccessful()) {
                return ['success' => false, 'serial' => $device['serial'], 'message' => $this->processError($push, 'ADB push failed.')];
            }

            $chmod = $this->runAdb([
                '-s', $device['serial'], 'shell', 'su', '-c', 'chmod 755 ' . $this->remoteServerPath,
            ], 20);
            if (!$chmod->isSuccessful()) {
                return ['success' => false, 'serial' => $device['serial'], 'message' => $this->processError($chmod, 'Could not chmod frida-server; check root access.')];
            }

            // The command is fixed; the device serial is separately validated and never interpolated into shell text.
            $start = $this->runAdb([
                '-s', $device['serial'], 'shell', 'su', '-c',
                'nohup ' . $this->remoteServerPath . ' >/data/local/tmp/frida-server.log 2>&1 </dev/null &',
            ], 20);
            if (!$start->isSuccessful()) {
                return ['success' => false, 'serial' => $device['serial'], 'message' => $this->processError($start, 'Could not start frida-server; check root access and binary architecture.')];
            }

            usleep(500000);
            $status = $this->serverStatus($device['serial']);
            if (!($status['running'] ?? false)) {
                return ['success' => false, 'serial' => $device['serial'], 'message' => 'Start command returned, but frida-server was not detected.', 'status' => $status];
            }

            return ['success' => true, 'serial' => $device['serial'], 'pid' => $status['pid'], 'message' => 'Frida server started.'];
        } catch (Throwable $exception) {
            Log::error('Starting frida-server failed: ' . $exception->getMessage());
            return ['success' => false, 'serial' => $device['serial'], 'message' => $exception->getMessage()];
        }
    }

    /** Stop frida-server on a rooted device. */
    public function stopServer(?string $serial = null): array
    {
        $device = $this->resolveDevice($serial);
        if (!$device['success']) {
            return $device;
        }

        try {
            $process = $this->runAdb([
                '-s', $device['serial'], 'shell', 'su', '-c',
                'pkill -x frida-server || killall frida-server || true',
            ], 20);
            if (!$process->isSuccessful()) {
                return ['success' => false, 'serial' => $device['serial'], 'message' => $this->processError($process, 'Could not stop frida-server.')];
            }

            usleep(300000);
            $status = $this->serverStatus($device['serial']);
            if ($status['running'] ?? false) {
                return ['success' => false, 'serial' => $device['serial'], 'message' => 'frida-server is still running.', 'status' => $status];
            }

            return ['success' => true, 'serial' => $device['serial'], 'message' => 'Frida server stopped.'];
        } catch (Throwable $exception) {
            Log::error('Stopping frida-server failed: ' . $exception->getMessage());
            return ['success' => false, 'serial' => $device['serial'], 'message' => $exception->getMessage()];
        }
    }

    private function resolveDevice(?string $serial): array
    {
        if ($serial !== null && !preg_match('/^[A-Za-z0-9._:-]{1,128}$/', $serial)) {
            return ['success' => false, 'message' => 'Invalid ADB device serial.'];
        }

        $listed = $this->listAdbDevices();
        if (!$listed['success']) {
            return ['success' => false, 'message' => $listed['message'] ?? 'ADB is unavailable.'];
        }

        $usable = array_values(array_filter($listed['devices'], fn (array $d) => $d['state'] === 'device'));
        if ($serial !== null) {
            foreach ($usable as $device) {
                if ($device['serial'] === $serial) {
                    return ['success' => true, 'serial' => $serial];
                }
            }
            return ['success' => false, 'message' => 'Requested device is not connected and authorized.'];
        }

        if (count($usable) !== 1) {
            return ['success' => false, 'message' => count($usable) === 0
                ? 'No authorized Android device is connected.'
                : 'Multiple devices are connected; specify device_serial.'];
        }

        return ['success' => true, 'serial' => $usable[0]['serial']];
    }

    private function runAdb(array $arguments, int $timeout): Process
    {
        $process = new Process(array_merge([$this->adbBinary], $arguments));
        $process->setTimeout($timeout);
        $process->run();

        return $process;
    }

    private function processError(Process $process, string $fallback): string
    {
        $message = trim($process->getErrorOutput()) ?: trim($process->getOutput());
        return mb_substr($message ?: $fallback, 0, 1500);
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
