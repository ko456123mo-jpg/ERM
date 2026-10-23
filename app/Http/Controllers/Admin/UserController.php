<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $caller = $request->user();

        if (!$caller->hasPermission('users.view')) {
            return response()->json(['message' => 'Forbidden - Access Denied', 'detail' => 'Missing permission: users.view'], 403);
        }

        $query = User::with('role')->orderBy('id', 'desc');

        // Non-super-admins cannot see Super Admin accounts in the user list
        if (!$caller->isSuperAdmin()) {
            $query->whereHas('role', function ($q) {
                $q->whereNotIn('name', ['Super Admin', 'SUPER ADMIN', 'SUPER_ADMIN']);
            });
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->input('role_id'));
        }

        $users = $query->get();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($users->map(fn($u) => [
                'id' => $u->id,
                'username' => $u->username,
                'email' => $u->email,
                'name' => $u->name,
                'is_active' => (bool)$u->is_active,
                'role_id' => $u->role_id,
                'role' => [
                    'id' => $u->role?->id,
                    'name' => $u->role?->name ?? 'N/A',
                    'level' => $u->getRoleLevel(),
                ]
            ]));
        }

        return view('admin.users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $caller = $request->user();

        if (!$caller->hasPermission('users.create')) {
            return response()->json(['message' => 'Forbidden - Access Denied', 'detail' => 'Missing permission: users.create'], 403);
        }

        $validator = Validator::make($request->all(), [
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role_id' => ['required', 'exists:roles,id'],
            'is_active' => ['boolean']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Failed',
                'detail' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $targetRole = Role::findOrFail($data['role_id']);

        // Hierarchical Authorization Check: Cannot assign role >= caller level
        if (!$caller->canAssignRole($targetRole)) {
            return response()->json([
                'message' => 'Forbidden - Privilege Escalation Denied',
                'detail' => "You cannot create a user with a role ('{$targetRole->name}') equal to or higher than your privilege level."
            ], 403);
        }

        $user = User::create([
            'username' => $data['username'],
            'name' => $data['name'] ?? $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => $data['role_id'],
            'is_active' => $data['is_active'] ?? true,
        ]);

        $user->load('role');

        AuditLog::log('User Created', $user->username, [
            'target_user_id' => $user->id,
            'role' => $user->role?->name
        ]);

        $userData = [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'name' => $user->name,
            'is_active' => (bool)$user->is_active,
            'role_id' => $user->role_id,
            'role' => ['id' => $user->role?->id, 'name' => $user->role?->name ?? 'N/A', 'level' => $user->getRoleLevel()]
        ];

        return response()->json(array_merge(['message' => "User '{$user->username}' created successfully.", 'user' => $userData], $userData), 201);
    }

    public function update(Request $request, User $user)
    {
        $caller = $request->user();

        if (!$caller->hasPermission('users.edit')) {
            return response()->json(['message' => 'Forbidden - Access Denied', 'detail' => 'Missing permission: users.edit'], 403);
        }

        // Hierarchical check: Cannot manage user with level >= caller's level
        if (!$caller->canManageUser($user)) {
            return response()->json([
                'message' => 'Forbidden - Access Denied',
                'detail' => "You do not have permission to modify a user ('{$user->username}') with a privilege level equal to or higher than your own."
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'username' => ['required', 'string', 'max:50', 'unique:users,username,' . $user->id],
            'name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email,' . $user->id],
            'role_id' => ['required', 'exists:roles,id'],
            'password' => ['nullable', 'string', 'min:6'],
            'is_active' => ['boolean']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Failed',
                'detail' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Granular Check: users.change_email
        if ($data['email'] !== $user->email && !$caller->hasPermission('users.change_email')) {
            return response()->json([
                'message' => 'Forbidden - Access Denied',
                'detail' => 'Missing permission: users.change_email'
            ], 403);
        }

        // Granular Check: users.change_role
        $newRole = Role::findOrFail($data['role_id']);
        if ((int)$data['role_id'] !== $user->role_id) {
            if (!$caller->hasPermission('users.change_role')) {
                return response()->json([
                    'message' => 'Forbidden - Access Denied',
                    'detail' => 'Missing permission: users.change_role'
                ], 403);
            }

            // Hierarchical check: Cannot assign role with level >= caller's level
            if (!$caller->canAssignRole($newRole)) {
                return response()->json([
                    'message' => 'Forbidden - Privilege Escalation Denied',
                    'detail' => "You cannot assign a role ('{$newRole->name}') equal to or higher than your privilege level."
                ], 403);
            }

            // Sole Super Admin Protection: Prevent demoting the last Super Admin
            if ($user->isSuperAdmin()) {
                $superAdminRole = Role::whereIn('name', ['Super Admin', 'SUPER ADMIN', 'SUPER_ADMIN'])->first();
                $superAdminCount = User::where('role_id', $superAdminRole?->id)->count();
                if ($superAdminCount <= 1) {
                    return response()->json(['message' => 'Operation Denied', 'detail' => 'Cannot demote the sole remaining Super Admin user.'], 400);
                }
            }
        }

        // Granular Check: users.change_password (if password provided)
        $passwordChanged = false;
        if (!empty($data['password'])) {
            if (!$caller->hasPermission('users.change_password') && !$caller->hasPermission('users.reset_password')) {
                return response()->json([
                    'message' => 'Forbidden - Access Denied',
                    'detail' => 'Missing permission: users.change_password'
                ], 403);
            }
            $user->password = Hash::make($data['password']);
            $passwordChanged = true;
        }

        $emailChanged = ($data['email'] !== $user->email);
        $roleChanged = ((int)$data['role_id'] !== $user->role_id);
        $oldRoleName = $user->role?->name;

        $user->username = $data['username'];
        $user->name = $data['name'] ?? $data['username'];
        $user->email = $data['email'];
        $user->role_id = $data['role_id'];
        if (isset($data['is_active'])) {
            $user->is_active = (bool)$data['is_active'];
        }
        $user->save();
        $user->load('role');

        if ($emailChanged) {
            AuditLog::log('USER_EMAIL_CHANGED', $user->username, ['target_user_id' => $user->id, 'new_email' => $user->email]);
        }
        if ($roleChanged) {
            AuditLog::log('USER_ROLE_CHANGED', $user->username, ['target_user_id' => $user->id, 'old_role' => $oldRoleName, 'new_role' => $user->role?->name]);
        }
        if ($passwordChanged) {
            AuditLog::log('USER_PASSWORD_CHANGED', $user->username, ['target_user_id' => $user->id]);
        }

        AuditLog::log('USER_UPDATED', $user->username, [
            'target_user_id' => $user->id,
            'role' => $user->role?->name,
            'is_active' => $user->is_active
        ]);

        return response()->json([
            'message' => "User '{$user->username}' updated successfully.",
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'name' => $user->name,
                'is_active' => (bool)$user->is_active,
                'role_id' => $user->role_id,
                'role' => ['id' => $user->role?->id, 'name' => $user->role?->name ?? 'N/A', 'level' => $user->getRoleLevel()]
            ]
        ]);
    }

    public function resetPassword(Request $request, User $user)
    {
        $caller = $request->user();

        if (!$caller->hasPermission('users.reset_password') && !$caller->hasPermission('users.change_password')) {
            return response()->json(['message' => 'Forbidden - Access Denied', 'detail' => 'Missing permission: users.reset_password'], 403);
        }

        if (!$caller->canManageUser($user)) {
            return response()->json([
                'message' => 'Forbidden - Access Denied',
                'detail' => "You do not have permission to reset password for a user with privilege level equal to or higher than your own."
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'password' => ['required', 'string', 'min:6'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Failed',
                'detail' => $validator->errors()->first()
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->input('password')),
        ]);

        AuditLog::log('USER_PASSWORD_RESET', $user->username, ['target_user_id' => $user->id]);

        return response()->json(['message' => "Password for user '{$user->username}' has been reset successfully."]);
    }

    public function toggleStatus(Request $request, User $user)
    {
        $caller = $request->user();

        if ($user->id === $caller->id) {
            return response()->json(['message' => 'Operation Denied', 'detail' => 'Cannot disable your own account'], 400);
        }

        if (!$caller->canManageUser($user)) {
            return response()->json([
                'message' => 'Forbidden - Access Denied',
                'detail' => "You do not have permission to change status of a user with privilege level equal to or higher than your own."
            ], 403);
        }

        // Granular check: users.disable vs users.enable
        if ($user->is_active) {
            if (!$caller->hasPermission('users.disable')) {
                return response()->json(['message' => 'Forbidden - Access Denied', 'detail' => 'Missing permission: users.disable'], 403);
            }
        } else {
            if (!$caller->hasPermission('users.enable')) {
                return response()->json(['message' => 'Forbidden - Access Denied', 'detail' => 'Missing permission: users.enable'], 403);
            }
        }

        // Sole Super Admin Protection
        if ($user->isSuperAdmin() && $user->is_active) {
            $superAdminRole = Role::whereIn('name', ['Super Admin', 'SUPER ADMIN', 'SUPER_ADMIN'])->first();
            $activeSuperAdmins = User::where('role_id', $superAdminRole?->id)->where('is_active', true)->count();
            if ($activeSuperAdmins <= 1) {
                return response()->json(['message' => 'Operation Denied', 'detail' => 'Cannot disable the sole active Super Admin user.'], 400);
            }
        }

        $user->update([
            'is_active' => !$user->is_active,
        ]);

        $statusStr = $user->is_active ? 'activated' : 'disabled';
        $auditAction = $user->is_active ? 'USER_ENABLED' : 'USER_DISABLED';

        AuditLog::log($auditAction, $user->username, [
            'target_user_id' => $user->id,
            'new_status' => $statusStr
        ]);

        return response()->json([
            'message' => "User '{$user->username}' account status changed to {$statusStr}.",
            'is_active' => (bool)$user->is_active
        ]);
    }

    public function destroy(Request $request, User $user)
    {
        $caller = $request->user();

        if (!$caller->hasPermission('users.delete')) {
            return response()->json(['message' => 'Forbidden - Access Denied', 'detail' => 'Missing permission: users.delete'], 403);
        }

        if ($user->id === $caller->id) {
            return response()->json(['message' => 'Operation Denied', 'detail' => 'Cannot delete your own account'], 400);
        }

        if (!$caller->canManageUser($user)) {
            return response()->json([
                'message' => 'Forbidden - Access Denied',
                'detail' => "You do not have permission to delete a user with privilege level equal to or higher than your own."
            ], 403);
        }

        if ($user->isSuperAdmin()) {
            $superAdminRole = Role::whereIn('name', ['Super Admin', 'SUPER ADMIN', 'SUPER_ADMIN'])->first();
            $superAdminCount = User::where('role_id', $superAdminRole?->id)->count();
            if ($superAdminCount <= 1) {
                return response()->json(['message' => 'Operation Denied', 'detail' => 'Cannot delete the sole Super Admin user.'], 400);
            }
        }

        $deletedUsername = $user->username;
        $user->delete();

        AuditLog::log('USER_DELETED', $deletedUsername);

        return response()->json(['message' => "User '{$deletedUsername}' deleted successfully."]);
    }
}
