<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ErmPhase1Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_login_success(): void
    {
        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'AdminSecret123!'
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_analyst_login_success(): void
    {
        $response = $this->post('/login', [
            'username' => 'analyst',
            'password' => 'AnalystSecret123!'
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_invalid_password_fails(): void
    {
        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'WrongPassword!'
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_unauthenticated_request_protected(): void
    {
        $response = $this->getJson('/api/users');
        $response->assertStatus(401);
    }

    public function test_rbac_admin_endpoint_access(): void
    {
        $admin = User::where('username', 'admin')->first();
        $analyst = User::where('username', 'analyst')->first();

        // Admin should have access (200 OK)
        $responseAdmin = $this->actingAs($admin)->getJson('/api/users');
        $responseAdmin->assertStatus(200);

        // Security Analyst should be denied (403 Forbidden)
        $responseAnalyst = $this->actingAs($analyst)->getJson('/api/users');
        $responseAnalyst->assertStatus(403);
    }

    public function test_argon2id_password_hashing(): void
    {
        $admin = User::where('username', 'admin')->first();
        $this->assertTrue(str_starts_with($admin->password, '$argon2id$'));
        $this->assertTrue(Hash::check('AdminSecret123!', $admin->password));
    }

    public function test_user_creation_and_deletion(): void
    {
        $admin = User::where('username', 'admin')->first();
        $analystRole = Role::where('name', 'Security Analyst')->first() ?? Role::where('name', 'SECURITY_ANALYST')->first();

        // Create new user as Admin
        $createResponse = $this->actingAs($admin)->postJson('/api/users', [
            'username' => 'testuser',
            'email' => 'testuser@erm.local',
            'password' => 'TestPass123!',
            'role_id' => $analystRole->id,
            'is_active' => true
        ]);
        $createResponse->assertStatus(201);
        $this->assertDatabaseHas('users', ['username' => 'testuser']);

        $newUserId = $createResponse->json('id');

        // Delete user as Admin
        $deleteResponse = $this->actingAs($admin)->deleteJson("/api/users/{$newUserId}");
        $deleteResponse->assertStatus(200);
        $this->assertDatabaseMissing('users', ['username' => 'testuser']);
    }
}
