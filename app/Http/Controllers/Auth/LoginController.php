<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $loginInput = $request->input('username');
        $password = $request->input('password');
        $remember = $request->boolean('remember');

        // Determine if input is email or username
        $fieldType = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $credentials = [
            $fieldType => $loginInput,
            'password' => $password,
        ];

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            if (!$user->is_active) {
                AuditLog::log('Login Attempt Disabled Account', $user->username);
                Auth::logout();
                if ($request->expectsJson()) {
                    return response()->json(['detail' => 'User account is disabled.'], 403);
                }
                return back()->withErrors(['username' => 'User account is disabled.']);
            }

            $request->session()->regenerate();

            AuditLog::log('User Login Successful', $user->username, ['role' => $user->role?->name]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Authenticated successfully',
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'email' => $user->email,
                        'role' => [
                            'name' => $user->role?->name
                        ]
                    ]
                ]);
            }

            return redirect()->intended(route('dashboard'));
        }

        AuditLog::log('User Login Failed', $loginInput);

        if ($request->expectsJson()) {
            return response()->json(['detail' => 'Incorrect username or password'], 401);
        }

        return back()->withErrors([
            'username' => 'Incorrect username or password.',
        ])->onlyInput('username');
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            AuditLog::log('User Logout', Auth::user()->username);
        }
        
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Successfully logged out']);
        }

        return redirect()->route('login');
    }
}
