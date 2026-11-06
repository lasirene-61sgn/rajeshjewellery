<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminApi
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof Admin) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Admin credentials required.',
            ], 403);
        }

        if (! $user->is_active) {
            $user->tokens()->delete();

            return response()->json([
                'success' => false,
                'message' => 'Your admin account has been deactivated. Contact support.',
            ], 403);
        }

        return $next($request);
    }
}

