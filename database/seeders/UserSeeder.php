<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class UserSeeder extends Seeder
{
    /**
     * Create only an explicitly configured bootstrap administrator.
     * No predictable demo users/passwords are ever seeded.
     */
    public function run(): void
    {
        $password = (string) config('services.bootstrap_admin.password', '');
        if ($password === '') {
            Log::notice('User seeding skipped: configure DEFAULT_ADMIN_PASSWORD to create the bootstrap administrator.');
            return;
        }
        if (strlen($password) < 16) {
            throw new RuntimeException('DEFAULT_ADMIN_PASSWORD must contain at least 16 characters.');
        }

        $role = Role::where('name', 'Super Admin')->first();
        if (!$role) {
            throw new RuntimeException('The Super Admin role must be seeded before the bootstrap user.');
        }

        $username = (string) config('services.bootstrap_admin.username', 'admin');
        $email = (string) config('services.bootstrap_admin.email', 'admin@example.invalid');

        User::firstOrCreate(
            ['username' => $username],
            [
                'name' => 'Bootstrap Administrator',
                'email' => $email,
                'password' => Hash::make($password),
                'role_id' => $role->id,
                'is_active' => true,
            ]
        );
    }
}
