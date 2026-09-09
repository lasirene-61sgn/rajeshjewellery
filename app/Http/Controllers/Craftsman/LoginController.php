<?php

namespace App\Http\Controllers\Craftsman;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        if(Auth::guard('craftsman')->check()){
            return redirect()->route('craftsman.dashboard');
        }
        return view('craftsman.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $loginFeild = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile';

        $authAttempt = [
            $loginFeild => $credentials['login'],
            'password' => $credentials['password'],
            'is_active' => true,
        ];

        if(Auth::guard('craftsman')->attempt($authAttempt, $request->boolean('remember'))){
            $request->session()->regenerate();

            $craftsman = Auth::guard('craftsman')->user();
            $craftsman->session_id = Session::getId();
            $craftsman->save();

            return redirect()->intended(route('craftsman.dashboard'));
        }

        return back()->withInput($request->only('login'))->withErrors([
            'login' => 'Invalid Credentials check you password mobile or email',
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        if(Auth::guard('craftsman')->check()){
            $craftsman = Auth::guard('craftsman')->user();
            $craftsman->session_id = null;
            $craftsman->save();

            Auth::guard('craftsman')->logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('craftsman.login');
    }
}
