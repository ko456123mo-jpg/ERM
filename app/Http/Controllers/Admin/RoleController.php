<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $caller = $request->user();

        if (!$caller->hasPermission('roles.view')) {
            return response()->json(['message' => 'Forbidden - Access Denied', 'detail' => 'Missing permission: roles.view'], 403);
        }

        $roles = Role::with('permissions')->get();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($roles->map(fn($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'description' => $r->description,
                'level' => $r->level,
                'users_count' => $r->users()->count(),
                'permissions' => $r->permissions->pluck('name'),
                'permission_ids' => $r->permissions->pluck('id'),
                'can_edit' => $caller->isSuperAdmin() || ($caller->isAdmin() && $r->level < $caller->getRoleLevel()),
                'can_delete' => $caller->isSuperAdmin() && !in_array($r->level, [3, 4]) && $r->users()->count() === 0,
            ]));
        }

        return view('admin.roles.index', compact('roles'));
    }

    public function getPermissions(Request $request)
    {
        $caller = $request->user();

        if (!$caller->hasPermission('roles.view') && !$caller->hasPermission('roles.create') && !$caller->hasPermission('roles.edit')) {
            return response()->json(['message' => 'Forbidden - Access Denied', 'detail' => 'Missing role permissions'], 403);
        }

        $permissions = Permission::all();

        return response()->json($permissions->map(fn($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'description' => $p->description,
            'caller_has' => $caller->hasPermission($p->name),
        ]));
    }

    public function store(Request $request)
    {
        $caller = $request->user();

        if (!$caller->hasPermission('roles.create')) {
            return response()->json(['message' => 'Forbidden - Access Denied', 'detail' => 'Missing permission: roles.create'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:50', 'unique:roles,name'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,id']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Failed',
                'detail' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Privilege Escalation Check: Non-super-admins cannot name role "Super Admin" or "Admin"
        $nameUpper = strtoupper(trim($data['name']));
        if (!$caller->isSuperAdmin() && (str_contains($nameUpper, 'SUPER ADMIN') || str_contains($nameUpper, 'ADMIN'))) {
            return response()->json([
                'message' => 'Forbidden - Privilege Escalation Denied',
                'detail' => 'You cannot create an Administrator or Super Administrator level role.'
            ], 403);
        }

        // Validate that caller possesses all permissions they are attempting to grant
        if (!empty($data['permissions']) && !$caller->isSuperAdmin()) {
            $requestedPermNames = Permission::whereIn('id', $data['permissions'])->pluck('name');
            foreach ($requestedPermNames as $pName) {
                if (!$caller->hasPermission($pName)) {
                    return response()->json([
                        'message' => 'Forbidden - Privilege Escalation Denied',
                        'detail' => "You cannot grant permission '{$pName}' because your own account does not possess it."
                    ], 403);
                }
            }
        }

        $role = Role::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
        ]);

        if (!empty($data['permissions'])) {
            $role->permissions()->sync($data['permissions']);
        }

        AuditLog::log('Role Created', $role->name, [
            'role_id' => $role->id,
            'permissions_count' => count($data['permissions'] ?? [])
        ]);

        return response()->json([
            'message' => "Role '{$role->name}' created successfully.",
            'role' => $role->load('permissions')
        ], 201);
    }

    public function update(Request $request, Role $role)
    {
        $caller = $request->user();

        if (!$caller->hasPermission('roles.edit')) {
            return response()->json(['message' => 'Forbidden - Access Denied', 'detail' => 'Missing permission: roles.edit'], 403);
        }

        // Only Super Admin can modify roles with level >= 3 (Admin, Super Admin)
        if ($role->level >= 3 && !$caller->isSuperAdmin()) {
            return response()->json([
                'message' => 'Forbidden - Access Denied',
                'detail' => "You do not have permission to modify high-privilege role '{$role->name}'."
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:50', 'unique:roles,name,' . $role->id],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,id']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Failed',
                'detail' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Prevent renaming Super Admin
        if (strtoupper(trim($role->name)) === 'SUPER ADMIN' && strtoupper(trim($data['name'])) !== 'SUPER ADMIN') {
            return response()->json(['message' => 'Operation Denied', 'detail' => 'Cannot rename system Super Admin role.'], 422);
        }

        // Validate that caller possesses all permissions they are attempting to grant
        if (isset($data['permissions']) && !$caller->isSuperAdmin()) {
            $requestedPermNames = Permission::whereIn('id', $data['permissions'])->pluck('name');
            foreach ($requestedPermNames as $pName) {
                if (!$caller->hasPermission($pName)) {
                    return response()->json([
                        'message' => 'Forbidden - Privilege Escalation Denied',
                        'detail' => "You cannot grant permission '{$pName}' because your own account does not possess it."
                    ], 403);
                }
            }
        }

        $role->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? $role->description,
        ]);

        if (isset($data['permissions'])) {
            $role->permissions()->sync($data['permissions']);
            AuditLog::log('ROLE_PERMISSIONS_UPDATED', $role->name, [
                'role_id' => $role->id,
                'permissions_count' => count($data['permissions'])
            ]);
        }

        AuditLog::log('Role Updated', $role->name, [
            'role_id' => $role->id,
            'permissions_count' => count($data['permissions'] ?? [])
        ]);

        return response()->json([
            'message' => "Role '{$role->name}' updated successfully.",
            'role' => $role->load('permissions')
        ]);
    }

    public function destroy(Request $request, Role $role)
    {
        $caller = $request->user();

        if (!$caller->hasPermission('roles.delete')) {
            return response()->json(['message' => 'Forbidden - Access Denied', 'detail' => 'Missing permission: roles.delete'], 403);
        }

        if (!$caller->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden - Access Denied', 'detail' => 'Only Super Administrators can delete roles.'], 403);
        }

        $roleNameUpper = strtoupper(trim($role->name));
        if (in_array($roleNameUpper, ['SUPER ADMIN', 'SUPER_ADMIN', 'ADMIN', 'SECURITY ANALYST', 'STANDARD USER', 'VIEWER'])) {
            return response()->json(['message' => 'Operation Denied', 'detail' => "System role '{$role->name}' cannot be deleted."], 400);
        }

        if ($role->users()->count() > 0) {
            return response()->json(['message' => 'Operation Denied', 'detail' => "Role '{$role->name}' is assigned to active users and cannot be deleted."], 400);
        }

        $deletedName = $role->name;
        $role->permissions()->detach();
        $role->delete();

        AuditLog::log('Role Deleted', $deletedName);

        return response()->json(['message' => "Role '{$deletedName}' deleted successfully."]);
    }
}
