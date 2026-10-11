<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Handle login for Cashier and Owner.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => 'required|string|max:60',
            'password' => 'required|string',
            'remember' => 'nullable|boolean',
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $username = trim($validated['username']);
        $password = $validated['password'];
        $remember = (bool) ($validated['remember'] ?? false);

        // Throttle key per username & IP
        $throttleKey = Str::transliterate(Str::lower($username).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'error' => "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik.",
                'retry_after' => $seconds,
            ], 429);
        }

        $user = User::where('username', $username)->first();

        if (!$user || !Hash::check($password, $user->password_hash)) {
            RateLimiter::hit($throttleKey, 300); // lockout for 5 minutes after 5 failures

            return response()->json([
                'error' => 'Username atau password salah.',
            ], 401);
        }

        if (!$user->active) {
            return response()->json([
                'error' => 'Akses akun dinonaktifkan. Hubungi Owner.',
            ], 403);
        }

        RateLimiter::clear($throttleKey);

        // Perform session login
        Auth::guard('web')->login($user, $remember);
        $request->session()->regenerate();

        // Update last login
        $user->last_login_at = now();
        $user->save();

        // Record account activity
        try {
            DB::table('account_activities')->insert([
                'user_id' => $user->id,
                'name' => $user->name,
                'type' => 'Login',
                'detail' => 'Login ke sistem (' . ($user->role === 'owner' ? 'Owner' : 'Kasir') . ')',
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $ignored) {}

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'role' => $user->role,
                'phone' => $user->phone,
                'shift' => $user->shift,
            ],
            'csrf' => csrf_token(),
        ]);
    }

    /**
     * Terminate user session.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            try {
                DB::table('account_activities')->insert([
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'type' => 'Logout',
                    'detail' => 'Keluar dari sistem',
                    'ip_address' => $request->ip(),
                    'created_at' => now(),
                ]);
            } catch (\Throwable $ignored) {}
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'ok' => true,
            'csrf' => csrf_token(),
        ]);
    }

    /**
     * Get current session and application state.
     */
    public function session(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'role' => $user->role,
                'phone' => $user->phone,
                'shift' => $user->shift,
            ] : null,
            'csrf' => csrf_token(),
            'settings' => BusinessSetting::allAsArray(),
        ]);
    }

    /**
     * Endpoint for authenticated user info.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'error' => 'Silakan masuk untuk melanjutkan.'
            ], 401);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'role' => $user->role,
                'phone' => $user->phone,
                'shift' => $user->shift,
            ]
        ]);
    }
}
