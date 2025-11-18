<?php

namespace App\Http\Controllers;

use App\Models\Division;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DivisionsController extends Controller
{
    public function index()
    {
        $divisions = Division::all();
        $user = Auth::user();

        // Check access for each division
        $divisionsWithAccess = $divisions->map(function ($division) use ($user) {
            $division->has_access = $user->hasAccessToDivision($division->slug);
            return $division;
        });

        return view('divisions-of-learning.index', compact('divisionsWithAccess'));
    }
}
