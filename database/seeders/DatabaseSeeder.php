<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            // Creates only a configured bootstrap administrator; no built-in credentials.
            UserSeeder::class,
            SettingSeeder::class,
        ]);
    }
}
