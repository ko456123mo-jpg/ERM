<?php

namespace Tests\Unit;

use App\Services\FridaService;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class FridaAdbIntegrationTest extends TestCase
{
    private string $fakeAdb;
    private string|false $previousMode;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeAdb = sys_get_temp_dir() . '/fake-adb-' . bin2hex(random_bytes(6));
        file_put_contents($this->fakeAdb, <<<'SH'
#!/bin/sh
if [ "$1" = "devices" ]; then
    if [ "$FAKE_ADB_MODE" = "failure" ]; then
        echo "adb server unavailable" >&2
        exit 1
    fi
    printf 'List of devices attached\nABC123\tdevice product:test model:Test_Device\nXYZ999\tunauthorized usb:1-1\n'
    exit 0
fi
echo "unexpected fake adb invocation: $*" >&2
exit 2
SH);
        chmod($this->fakeAdb, 0700);

        config(['services.frida.adb_binary' => $this->fakeAdb]);
        $this->previousMode = getenv('FAKE_ADB_MODE');
    }

    protected function tearDown(): void
    {
        if ($this->previousMode === false) {
            putenv('FAKE_ADB_MODE');
        } else {
            putenv('FAKE_ADB_MODE=' . $this->previousMode);
        }
        @unlink($this->fakeAdb);

        parent::tearDown();
    }

    public function test_it_parses_connected_and_unauthorized_adb_devices(): void
    {
        putenv('FAKE_ADB_MODE=success');

        $result = app(FridaService::class)->listAdbDevices();

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['devices']);
        $this->assertSame('ABC123', $result['devices'][0]['serial']);
        $this->assertSame('device', $result['devices'][0]['state']);
        $this->assertSame('unauthorized', $result['devices'][1]['state']);
    }

    public function test_it_returns_an_error_when_adb_fails(): void
    {
        putenv('FAKE_ADB_MODE=failure');

        $result = app(FridaService::class)->listAdbDevices();

        $this->assertFalse($result['success']);
        $this->assertSame([], $result['devices']);
        $this->assertStringContainsString('adb server unavailable', $result['message']);
    }

    public function test_frida_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('api.frida.status'));
        $this->assertTrue(Route::has('api.frida.start'));
        $this->assertTrue(Route::has('api.frida.stop'));
    }
}
