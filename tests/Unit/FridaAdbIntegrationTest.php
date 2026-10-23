<?php

namespace Tests\Unit;

use App\Services\FridaService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class FridaAdbIntegrationTest extends TestCase
{
    private string $privateKeyPath;

    protected function setUp(): void
    {
        parent::setUp();
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
        $this->privateKeyPath = sys_get_temp_dir() . '/erm-test-jwt-' . bin2hex(random_bytes(6)) . '.pem';
        openssl_pkey_export_to_file($key, $this->privateKeyPath);
        config([
            'services.fastapi.jwt_private_key_path' => $this->privateKeyPath,
            'services.fastapi.jwt_issuer' => 'erm-laravel',
            'services.fastapi.jwt_audience' => 'erm-tool-gateway',
            'services.frida.url' => 'http://127.0.0.1:8001',
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->privateKeyPath);
        parent::tearDown();
    }

    public function test_frida_device_requests_are_sent_to_gateway_with_scoped_bearer_jwt(): void
    {
        Http::fake(['http://127.0.0.1:8001/devices/adb' => Http::response(['success' => true, 'devices' => []])]);

        $result = app(FridaService::class)->listAdbDevices();

        $this->assertTrue($result['success']);
        Http::assertSent(function ($request): bool {
            $authorization = $request->header('Authorization')[0] ?? '';
            if (!str_starts_with($authorization, 'Bearer ')) {
                return false;
            }
            $parts = explode('.', substr($authorization, 7));
            if (count($parts) !== 3) {
                return false;
            }
            $decode = static fn (string $part): string => base64_decode(strtr($part, '-_', '+/') . str_repeat('=', (4 - strlen($part) % 4) % 4));
            $claims = json_decode($decode($parts[1]), true);
            $privateKey = openssl_pkey_get_private(file_get_contents($this->privateKeyPath));
            $publicKey = openssl_pkey_get_details($privateKey)['key'];
            return openssl_verify($parts[0] . '.' . $parts[1], $decode($parts[2]), $publicKey, OPENSSL_ALGO_SHA256) === 1
                && ($claims['scope'] ?? null) === 'devices:read'
                && ($claims['iss'] ?? null) === 'erm-laravel'
                && ($claims['aud'] ?? null) === 'erm-tool-gateway'
                && ($claims['exp'] ?? 0) - ($claims['iat'] ?? 0) === 60;
        });
    }

    public function test_frida_routes_are_registered(): void
    {
        $this->assertNotNull(Route::getRoutes()->getByName('api.frida.status'));
        $this->assertNotNull(Route::getRoutes()->getByName('api.frida.start'));
        $this->assertNotNull(Route::getRoutes()->getByName('api.frida.stop'));
    }
}
