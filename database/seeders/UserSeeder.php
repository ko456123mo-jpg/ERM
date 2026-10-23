<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::where('name', 'Super Admin')->first();
        $adminRole = Role::where('name', 'Admin')->first() ?? Role::where('name', 'ADMIN')->first();
        $analystRole = Role::where('name', 'Security Analyst')->first() ?? Role::where('name', 'SECURITY_ANALYST')->first();
        $devRole = Role::where('name', 'Developer')->first();
        $standardUserRole = Role::where('name', 'Standard User')->first();
        $viewerRole = Role::where('name', 'Viewer')->first();

        // 1. Super Admin
        User::updateOrCreate(
            ['username' => 'superadmin'],
            [
                'name' => 'Super Administrator',
                'email' => 'superadmin@erm.local',
                'password' => Hash::make('admin'),
                'role_id' => $superAdminRole?->id,
                'is_active' => true,
            ]
        );

        // 2. Admin
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'System Administrator',
                'email' => 'admin@erm.local',
                'password' => Hash::make('AdminSecret123!'),
                'role_id' => $adminRole?->id,
                'is_active' => true,
            ]
        );

        // 3. Security Analyst
        User::firstOrCreate(
            ['username' => 'analyst'],
            [
                'name' => 'Security Analyst',
                'email' => 'analyst@erm.local',
                'password' => Hash::make('AnalystSecret123!'),
                'role_id' => $analystRole?->id,
                'is_active' => true,
            ]
        );

        // 4. Standard User
        User::firstOrCreate(
            ['username' => 'user'],
            [
                'name' => 'Standard User',
                'email' => 'user@erm.local',
                'password' => Hash::make('UserSecret123!'),
                'role_id' => $standardUserRole?->id ?? $viewerRole?->id,
                'is_active' => true,
            ]
        );

        // 5. Developer
        User::firstOrCreate(
            ['username' => 'developer'],
            [
                'name' => 'Lead Developer',
                'email' => 'developer@erm.local',
                'password' => Hash::make('DevSecret123!'),
                'role_id' => $devRole?->id,
                'is_active' => true,
            ]
        );

        // 6. Viewer
        User::firstOrCreate(
            ['username' => 'viewer'],
            [
                'name' => 'Platform Viewer',
                'email' => 'viewer@erm.local',
                'password' => Hash::make('ViewerSecret123!'),
                'role_id' => $viewerRole?->id,
                'is_active' => true,
            ]
        );
    }
}
