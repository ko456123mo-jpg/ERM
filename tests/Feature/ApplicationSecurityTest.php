<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $analyst;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::where('username', 'admin')->first();
        $this->analyst = User::where('username', 'analyst')->first();
    }

    public function test_unauthenticated_access_redirected(): void
    {
        $response = $this->get('/applications');
        $response->assertRedirect('/login');
    }

    public function test_invalid_file_extension_rejected(): void
    {
        Storage::fake('local');
        $invalidFile = UploadedFile::fake()->create('malicious.exe', 100, 'application/octet-stream');

        $response = $this->actingAs($this->analyst)
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson('/applications', [
                'apk_file' => $invalidFile
            ]);

        $response->assertStatus(422);
    }

    public function test_valid_apk_upload_creates_records_and_sha256(): void
    {
        Storage::fake('local');
        $apkFile = UploadedFile::fake()->create('sample_app.apk', 500, 'application/vnd.android.package-archive');

        $response = $this->actingAs($this->analyst)
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson('/applications', [
                'name' => 'Sample Banking Security App',
                'apk_file' => $apkFile
            ]);

        $response->assertStatus(201);
        
        $app = Application::where('name', 'Sample Banking Security App')->first();
        $this->assertNotNull($app);
        $this->assertEquals(64, strlen($app->sha256));
        $this->assertDatabaseHas('applications', ['sha256' => $app->sha256]);
        $this->assertDatabaseHas('analyses', ['application_id' => $app->id]);
    }

    public function test_duplicate_sha256_upload_rejected(): void
    {
        Storage::fake('local');
        $apkFile1 = UploadedFile::fake()->create('app1.apk', 300, 'application/vnd.android.package-archive');

        // First Upload
        $this->actingAs($this->analyst)
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson('/applications', [
                'name' => 'Original App',
                'apk_file' => $apkFile1
            ]);

        // Duplicate Upload with same content/hash
        $apkFile2 = UploadedFile::fake()->create('app1.apk', 300, 'application/vnd.android.package-archive');
        $response = $this->actingAs($this->analyst)
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson('/applications', [
                'name' => 'Duplicate App',
                'apk_file' => $apkFile2
            ]);

        $response->assertStatus(409);
        $response->assertJsonFragment(['message' => 'Duplicate application detected']);
    }

    public function test_application_deletion(): void
    {
        Storage::fake('local');
        $apkFile = UploadedFile::fake()->create('delete_me.apk', 200, 'application/vnd.android.package-archive');

        $uploadResp = $this->actingAs($this->admin)
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson('/applications', [
                'name' => 'App To Delete',
                'apk_file' => $apkFile
            ]);

        $appId = $uploadResp->json('application.id');

        $deleteResp = $this->actingAs($this->admin)
            ->withHeaders(['Accept' => 'application/json'])
            ->deleteJson("/applications/{$appId}");
        $deleteResp->assertStatus(200);

        $this->assertDatabaseMissing('applications', ['id' => $appId]);
    }
}
