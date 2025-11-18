<?php

namespace App\Http\Controllers;

use App\Models\Division;
use Illuminate\Http\Request;

class DivisionController extends Controller
{
    public function show($slug)
    {
        $division = Division::where('slug', $slug)->with('activities')->firstOrFail();
        
        // Check access via middleware
        return view('divisions.show', compact('division'));
    }
}
