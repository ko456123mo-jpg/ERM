<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['key' => 'platform_name', 'value' => 'Engineering Reverse Mobile (ERM)', 'type' => 'string', 'group' => 'general'],
            ['key' => 'engine_url', 'value' => 'http://127.0.0.1:8000/api', 'type' => 'string', 'group' => 'engine'],
            ['key' => 'max_upload_size_mb', 'value' => '100', 'type' => 'integer', 'group' => 'general'],
            ['key' => 'session_timeout_minutes', 'value' => '120', 'type' => 'integer', 'group' => 'security'],
            ['key' => 'ai_model', 'value' => 'gpt-4o', 'type' => 'string', 'group' => 'ai'],
            ['key' => 'ai_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'ai'],
        ];

        foreach ($defaults as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
