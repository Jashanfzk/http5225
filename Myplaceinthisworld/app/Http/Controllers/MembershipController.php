<?php

namespace App\Http\Controllers;

use App\Models\Membership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MembershipController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $school = $user->school;

        if (!$school) {
            return redirect()->route('home')->with('error', 'No school associated with your account.');
        }

        $memberships = $school->memberships()->get();
        
        // Get current membership status
        $primaryMembership = $memberships->where('membership_type', 'primary')
            ->where('status', 'active')
            ->where(function($m) {
                return $m->expires_at === null || $m->expires_at->isFuture();
            })
            ->first();

        $juniorIntermediateMembership = $memberships->where('membership_type', 'junior_intermediate')
            ->where('status', 'active')
            ->where(function($m) {
                return $m->expires_at === null || $m->expires_at->isFuture();
            })
            ->first();

        $hasPrimary = $primaryMembership !== null;
        $hasJuniorIntermediate = $juniorIntermediateMembership !== null;
        $hasHighSchool = true; // Always available

        // Pricing
        $pricing = [
            'primary' => [
                'annual' => 399.99,
                'monthly' => 33.33,
            ],
            'junior_intermediate' => [
                'annual' => 399.99,
                'monthly' => 33.33,
            ],
        ];

        return view('membership.index', compact(
            'hasPrimary',
            'hasJuniorIntermediate',
            'hasHighSchool',
            'memberships',
            'primaryMembership',
            'juniorIntermediateMembership',
            'pricing'
        ));
    }
}
