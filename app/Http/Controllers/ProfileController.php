<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $user->load('role');

        return response()->json([
            'id' => $user->id,
            'username' => $user->username,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role?->name ?? 'N/A',
            'is_active' => (bool) $user->is_active,
            'created_at' => $user->created_at->format('Y-m-d H:i:s'),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username,' . $user->id],
            'email' => ['required', 'email', 'max:100', 'unique:users,email,' . $user->id],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Failed',
                'detail' => $validator->errors()->first(),
            ], 422);
        }

        $data = $validator->validated();
        $user->update($data);

        AuditLog::log('User Profile Updated', $user->username, ['user_id' => $user->id]);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Failed',
                'detail' => $validator->errors()->first(),
            ], 422);
        }

        if (!Hash::check($request->input('current_password'), $user->password)) {
            return response()->json([
                'message' => 'Validation Failed',
                'detail' => 'Current password is incorrect.',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->input('new_password')),
        ]);

        AuditLog::log('Password Changed', $user->username, ['user_id' => $user->id]);

        return response()->json([
            'message' => 'Password changed successfully.',
        ]);
    }
}
