<?php

namespace App\Http\Controllers;

use App\Models\GalleryPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $school = $user->school;
        
        // Get effective user for progress tracking
        $effectiveUser = $user->getEffectiveUser();
        
        $memberships = $school ? $school->memberships()->get() : collect();
        $galleryPosts = $effectiveUser->galleryPosts()->latest()->get();
        $favorites = $effectiveUser->progress()->where('is_favorite', true)->with('activity')->get();
        
        return view('profile.index', compact('user', 'effectiveUser', 'memberships', 'galleryPosts', 'favorites'));
    }
}
