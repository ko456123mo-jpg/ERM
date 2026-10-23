<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()->hasPermission('settings.view')) {
            return response()->json(['message' => 'Forbidden - Access Denied', 'detail' => 'Missing permission: settings.view'], 403);
        }

        $settings = Setting::all();

        $result = [];
        foreach ($settings as $s) {
            $result[$s->key] = [
                'value' => $s->value,
                'type' => $s->type,
                'group' => $s->group,
            ];
        }

        return response()->json($result);
    }

    public function update(Request $request)
    {
        if (!$request->user()->hasPermission('settings.edit')) {
            return response()->json(['message' => 'Forbidden - Access Denied', 'detail' => 'Missing permission: settings.edit'], 403);
        }

        $allowedKeys = [
            'platform_name', 'engine_url', 'max_upload_size_mb',
            'session_timeout_minutes', 'ai_model', 'ai_enabled',
        ];

        $updated = [];
        foreach ($allowedKeys as $key) {
            if ($request->has($key)) {
                $existing = Setting::where('key', $key)->first();
                $type = $existing ? $existing->type : 'string';
                $group = $existing ? $existing->group : 'general';
                Setting::set($key, $request->input($key), $type, $group);
                $updated[] = $key;
            }
        }

        if (count($updated) > 0) {
            AuditLog::log('Platform Settings Updated', 'System Configuration', ['keys' => $updated]);
        }

        return response()->json([
            'message' => 'Settings saved successfully.',
            'updated_keys' => $updated,
        ]);
    }
}
