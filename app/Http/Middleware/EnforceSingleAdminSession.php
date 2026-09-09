<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class EnforceSingleAdminSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('admin')->check()) {
            $currentSessionId = Session::getId();
            $admin = Auth::guard('admin')->user();
            $persistedSessionId = $admin->session_id;

            // Session ID mismatch: account logged in from another browser
            if ($persistedSessionId && $persistedSessionId !== $currentSessionId) {
                Auth::guard('admin')->logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                // If background JS heartbeat check, return 401 JSON immediately
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Session displaced by another login.',
                    ], 401);
                }

                // If regular browser navigation, redirect to login
                return redirect()->route('admin.login')->withErrors([
                    'email' => 'This account was logged in from another browser. Your session has expired.',
                ]);
            }
        }

        return $next($request);
    }
}
