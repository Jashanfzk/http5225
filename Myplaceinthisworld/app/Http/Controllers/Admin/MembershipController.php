<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use Illuminate\Http\Request;

class MembershipController extends Controller
{
    /**
     * Display a listing of memberships.
     */
    public function index(Request $request)
    {
        $query = Membership::with('school');

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->has('type') && $request->type) {
            $query->where('membership_type', $request->type);
        }

        // Expiring soon
        if ($request->has('expiring') && $request->expiring) {
            $query->where('status', 'active')
                  ->whereNotNull('expires_at')
                  ->whereBetween('expires_at', [now(), now()->addDays(30)]);
        }

        $memberships = $query->orderBy('expires_at', 'asc')->paginate(20);

        return view('admin.memberships.index', compact('memberships'));
    }

    /**
     * Show the form for editing a membership.
     */
    public function edit(Membership $membership)
    {
        $membership->load('school');
        return view('admin.memberships.edit', compact('membership'));
    }

    /**
     * Update a membership.
     */
    public function update(Request $request, Membership $membership)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:active,expired,cancelled'],
            'expires_at' => ['nullable', 'date'],
            'billing_period' => ['nullable', 'in:monthly,annual'],
        ]);

        $membership->update($validated);

        return redirect()->route('admin.memberships.index')
            ->with('success', 'Membership updated successfully.');
    }

    /**
     * Grant free trial.
     */
    public function grantTrial(Membership $membership)
    {
        $membership->update([
            'status' => 'active',
            'expires_at' => now()->addYear(),
            'purchased_at' => now(),
        ]);

        return back()->with('success', 'Free trial granted successfully.');
    }
}
