<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Get statistics
        $totalSchools = School::count();
        $totalUsers = User::count();
        $totalMemberships = Membership::where('status', 'active')->count();
        
        // Get expiring memberships (next 30 days)
        $expiringMemberships = Membership::where('status', 'active')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays(30)])
            ->with('school')
            ->orderBy('expires_at', 'asc')
            ->get();

        // Get recent schools (last 10)
        $recentSchools = School::with('owner')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get recent memberships
        $recentMemberships = Membership::with('school')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Count by membership type
        $primaryCount = Membership::where('membership_type', 'primary')
            ->where('status', 'active')
            ->count();
        $juniorIntermediateCount = Membership::where('membership_type', 'junior_intermediate')
            ->where('status', 'active')
            ->count();
        $highSchoolCount = Membership::where('membership_type', 'high_school')
            ->where('status', 'active')
            ->count();

        return view('admin.dashboard', compact(
            'totalSchools',
            'totalUsers',
            'totalMemberships',
            'expiringMemberships',
            'recentSchools',
            'recentMemberships',
            'primaryCount',
            'juniorIntermediateCount',
            'highSchoolCount'
        ));
    }
}

