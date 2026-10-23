<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $caller = $request->user();

        if (!$caller->hasPermission('audit_logs.view')) {
            return response()->json(['message' => 'Forbidden - Access Denied', 'detail' => 'Missing permission: audit_logs.view'], 403);
        }

        $query = AuditLog::with('user')->orderBy('id', 'desc');

        // Admin cannot view Super Admin login or telemetry events
        if (!$caller->isSuperAdmin()) {
            $query->where('username', '!=', 'superadmin')
                  ->whereDoesntHave('user.role', function($q) {
                      $q->whereIn('name', ['Super Admin', 'SUPER ADMIN', 'SUPER_ADMIN']);
                  });
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('target', 'like', "%{$search}%");
            });
        }

        $logs = $query->limit(100)->get();

        return response()->json($logs->map(fn($l) => [
            'id' => $l->id,
            'username' => $l->username,
            'action' => $l->action,
            'target' => $l->target,
            'ip_address' => $l->ip_address,
            'details' => $l->details,
            'created_at' => $l->created_at->format('Y-m-d H:i:s'),
        ]));
    }
}
