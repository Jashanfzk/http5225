<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();
            $school = $user->school;

            // Check if user has sub-accounts and needs to select one
            if ($school && $user->is_owner) {
                $subAccountsCount = $school->users()
                    ->where('is_owner', false)
                    ->count();

                if ($subAccountsCount > 0 && !$user->current_sub_account_id) {
                    return redirect()->route('sub-accounts.select');
                }
            }

            return redirect()->intended(route('divisions-of-learning.index'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
