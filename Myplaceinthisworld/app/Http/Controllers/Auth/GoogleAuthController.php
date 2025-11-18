<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use App\Models\Membership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            $user = User::where('email', $googleUser->email)->first();

            if ($user) {
                Auth::login($user);
                return redirect()->route('divisions-of-learning.index');
            } else {
                // User doesn't exist, redirect to registration
                return redirect()->route('register')
                    ->with('error', 'Please register first, then you can use Google login.');
            }
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->with('error', 'Google authentication failed. Please try again.');
        }
    }
}
