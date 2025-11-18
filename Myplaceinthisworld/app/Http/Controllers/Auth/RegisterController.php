<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use App\Models\Membership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'school_name' => ['required', 'string', 'max:255'],
            'school_board' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Create school
        $school = School::create([
            'school_name' => $validated['school_name'],
            'school_board' => $validated['school_board'],
            'address' => $validated['address'],
        ]);

        // Create school owner user
        $user = User::create([
            'school_id' => $school->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_owner' => true,
        ]);

        // Assign educator role
        $user->assignRole('educator');

        // Create free High School membership
        Membership::create([
            'school_id' => $school->id,
            'membership_type' => 'high_school',
            'status' => 'active',
            'expires_at' => null, // Never expires
        ]);

        Auth::login($user);

        return redirect()->route('divisions-of-learning.index')
            ->with('success', 'Registration successful! You now have access to High School resources.');
    }
}
