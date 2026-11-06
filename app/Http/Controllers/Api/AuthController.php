<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Craftsman;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Common API Login for both Admin and Craftsman.
     * Enforces single-device login by revoking all existing tokens on new login.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
            'role'     => ['nullable', 'string', 'in:admin,craftsman'],
        ]);

        $login = trim($validated['login']);
        $password = $validated['password'];
        $requestedRole = $validated['role'] ?? null;

        $user = null;
        $role = null;

        // 1. Attempt Admin Login (if requested role is admin or not specified)
        if ($requestedRole === 'admin' || $requestedRole === null) {
            $admin = Admin::where('email', $login)->first();
            if ($admin && Hash::check($password, $admin->password)) {
                $user = $admin;
                $role = 'admin';
            }
        }

        // 2. Attempt Craftsman Login (if requested role is craftsman or auto-detect fallback)
        if (! $user && ($requestedRole === 'craftsman' || $requestedRole === null)) {
            $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL);
            $craftsman = Craftsman::query()
                ->when($isEmail, fn ($q) => $q->where('email', $login))
                ->when(! $isEmail, fn ($q) => $q->where('mobile', $login)->orWhere('email', $login))
                ->first();

            if ($craftsman && Hash::check($password, $craftsman->password)) {
                $user = $craftsman;
                $role = 'craftsman';
            }
        }

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials. Please verify your login and password.',
            ], 422);
        }

        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated. Please contact support.',
            ], 403);
        }

        // 3. Enforce Single-Device Session: Revoke all existing tokens across any previous devices
        $user->tokens()->delete();

        // 4. Issue a new personal access token for this device
        $token = $user->createToken('mobile_app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data'    => [
                'role'       => $role,
                'token'      => $token,
                'token_type' => 'Bearer',
                'user'       => [
                    'id'         => $user->id,
                    'name'       => $user->name,
                    'email'      => $user->email,
                    'mobile'     => $user->mobile ?? null,
                    'is_active'  => (bool) $user->is_active,
                    'created_at' => $user->created_at?->timezone('Asia/Kolkata')->toIso8601String(),
                ],
            ],
        ]);
    }

    /**
     * Get the authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $role = $user instanceof Admin ? 'admin' : ($user instanceof Craftsman ? 'craftsman' : 'user');

        return response()->json([
            'success' => true,
            'data'    => [
                'role' => $role,
                'user' => [
                    'id'         => $user->id,
                    'name'       => $user->name,
                    'email'      => $user->email,
                    'mobile'     => $user->mobile ?? null,
                    'is_active'  => (bool) $user->is_active,
                    'created_at' => $user->created_at?->timezone('Asia/Kolkata')->toIso8601String(),
                ],
            ],
        ]);
    }

    /**
     * Log out the current device by revoking the current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }
}

