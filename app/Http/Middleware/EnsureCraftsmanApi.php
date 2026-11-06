<?php

namespace App\Http\Middleware;

use App\Models\Craftsman;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCraftsmanApi
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof Craftsman) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Craftsman credentials required.',
            ], 403);
        }

        if (! $user->is_active) {
            $user->tokens()->delete();

            return response()->json([
                'success' => false,
                'message' => 'Your craftsman account has been deactivated. Contact support.',
            ], 403);
        }

        return $next($request);
    }
}

