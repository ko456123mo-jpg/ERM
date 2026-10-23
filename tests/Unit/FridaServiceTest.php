<?php

namespace Tests\Unit;

use App\Models\Application;
use App\Services\FridaService;
use App\Services\MobSfService;
use App\Services\ObjectionService;
use App\Services\AiService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FridaServiceTest extends TestCase
{
    public function test_frida_service_health_check_when_online(): void
    {
        Http::fake([
            'http://127.0.0.1:8001/health' => Http::response([
                'status' => 'ok',
                'service' => 'frida-integration',
                'frida_version' => '16.5.9',
                'adb_available' => true,
                'connected_devices_count' => 1
            ], 200)
        ]);

        $service = new FridaService();
        $health = $service->checkHealth();

        $this->assertTrue($health['online']);
        $this->assertEquals('online', $health['status']);
        $this->assertEquals('16.5.9', $health['details']['frida_version']);
    }

    public function test_frida_service_health_check_when_offline(): void
    {
        Http::fake([
            'http://127.0.0.1:8001/health' => Http::response(null, 500)
        ]);

        $service = new FridaService();
        $health = $service->checkHealth();

        $this->assertFalse($health['online']);
        $this->assertEquals('offline', $health['status']);
    }

    public function test_frida_service_execute_returns_structured_result(): void
    {
        Http::fake([
            'http://127.0.0.1:8001/tools/frida/execute' => Http::response([
                'status' => 'success',
                'session_id' => 'exec_12345',
                'target' => 'com.example.bank',
                'action' => 'ssl_pinning_bypass',
                'output' => [
                    ['type' => 'finding', 'payload' => ['finding' => 'SSL Pinning Hook Active', 'severity' => 'medium']]
                ],
                'findings' => [
                    ['finding' => 'SSL Pinning Hook Active', 'severity' => 'medium']
                ]
            ], 200)
        ]);

        $app = new Application();
        $app->id = 10;
        $app->name = 'BankApp';
        $app->package_name = 'com.example.bank';

        $service = new FridaService();
        $res = $service->execute($app, 'ssl_pinning_bypass');

        $this->assertEquals('success', $res['status']);
        $this->assertEquals('exec_12345', $res['session_id']);
        $this->assertCount(1, $res['findings']);
    }

    public function test_future_services_report_standby_gracefully_when_unconfigured(): void
    {
        Http::fake([
            'http://127.0.0.1:8002*' => Http::response(null, 500),
            'http://127.0.0.1:8003*' => Http::response(null, 500),
        ]);

        $mobsf = new MobSfService();
        $objection = new ObjectionService();
        $ai = new AiService();

        $this->assertFalse($mobsf->isOnline());
        $this->assertFalse($objection->isOnline());
        $this->assertFalse($ai->isOnline());

        $mobsfHealth = $mobsf->checkHealth();
        $this->assertFalse($mobsfHealth['online']);
        $this->assertContains($mobsfHealth['status'], ['offline', 'unreachable', 'not_configured']);
    }

    public function test_all_services_report_online_when_mocked_or_connected(): void
    {
        Http::fake([
            'http://127.0.0.1:8001*' => Http::response(['status' => 'ok', 'service' => 'frida-integration', 'frida_version' => '17.17.0', 'adb_available' => true, 'connected_devices_count' => 3], 200),
            'http://127.0.0.1:8002*' => Http::response('OK', 200),
            'http://127.0.0.1:8003*' => Http::response(['status' => 'ok', 'service' => 'objection-integration', 'objection_version' => '1.12.5', 'frida_version' => '17.17.0', 'adb_available' => true, 'connected_devices_count' => 3], 200),
        ]);

        $frida = new FridaService();
        $mobsf = new MobSfService();
        $objection = new ObjectionService();

        $this->assertTrue($frida->isOnline());
        $this->assertTrue($mobsf->isOnline());
        $this->assertTrue($objection->isOnline());
    }
}
