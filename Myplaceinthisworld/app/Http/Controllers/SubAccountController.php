<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubAccountController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show sub-account selection page.
     */
    public function select()
    {
        $user = Auth::user();
        $school = $user->school;

        if (!$school) {
            return redirect()->route('home')
                ->with('error', 'No school associated with your account.');
        }

        // Get all educators (sub-accounts) for this school
        $subAccounts = $school->users()
            ->where('is_owner', false)
            ->orderBy('name')
            ->get();

        return view('sub-accounts.select', compact('subAccounts'));
    }

    /**
     * Switch to selected sub-account.
     */
    public function switch(Request $request)
    {
        $validated = $request->validate([
            'sub_account_id' => ['nullable', 'exists:users,id'],
        ]);

        $user = Auth::user();
        $school = $user->school;

        if (!$school) {
            return redirect()->route('home')
                ->with('error', 'No school associated with your account.');
        }

        // If switching to a sub-account, verify it belongs to the same school
        if ($validated['sub_account_id']) {
            $subAccount = $school->users()
                ->where('id', $validated['sub_account_id'])
                ->where('is_owner', false)
                ->first();

            if (!$subAccount) {
                return redirect()->route('sub-accounts.select')
                    ->with('error', 'Invalid sub-account selected.');
            }

            // Set current sub-account
            $user->update([
                'current_sub_account_id' => $validated['sub_account_id'],
            ]);
        } else {
            // Continue as owner (clear sub-account)
            $user->update([
                'current_sub_account_id' => null,
            ]);
        }

        return redirect()->route('divisions-of-learning.index')
            ->with('success', 'Profile switched successfully.');
    }
}
