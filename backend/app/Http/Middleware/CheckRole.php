<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'error' => 'Silakan masuk untuk melanjutkan.'
            ], 401);
        }

        if (!$user->active) {
            // Log out user if deactivated
            auth()->guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'error' => 'Akses akun dinonaktifkan. Hubungi Owner.'
            ], 403);
        }

        if (!empty($roles) && !in_array($user->role, $roles, true)) {
            return response()->json([
                'error' => 'Akses ditolak. Peran Anda tidak memiliki izin untuk tindakan ini.'
            ], 403);
        }

        return $next($request);
    }
}
