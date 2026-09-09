<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AdminLoginRequest;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View
    {
        return view('admin.auth.login');
    }

    public function login(AdminLoginRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        $credentials = $request->only('email', 'password');
        $admin = Admin::where('email', $credentials['email'])->first();


        if(! $admin || ! Auth::guard('admin')->getProvider()->validateCredentials($admin, $credentials)){
            $request->hitRateLimit();

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if(! $admin->is_active){
            $request->hitRateLimit();

            throw ValidationException::withMessages([
                'email' => 'your account has been deactivated check you contact support',
            ]);
        }

        Auth::guard('admin')->login($admin, $request->boolean('remember'));
            $request->session()->regenerate();

            $newSessionId = Session::getId();

        DB::transaction(function () use ($admin, $newSessionId){
            $currentAdmin = Admin::lockForUpdate()->find($admin->id);

            if(config('session.driver') === 'database' && $currentAdmin->session_id){
                DB::table(config('session.table', 'sessions'))->where('id', $currentAdmin->session_id)->delete();
            }
            $currentAdmin->update(['session_id' => $newSessionId]);
        });
        $request->clearRateLimit();
        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $admin = Auth::guard('admin')->user();

        if($admin){
            $admin->update(['session_id' => null]);
        }

        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
