<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityController extends Controller
{
    public function show($divisionSlug, $activitySlug)
    {
        $activity = Activity::where('slug', $activitySlug)
            ->whereHas('division', function($query) use ($divisionSlug) {
                $query->where('slug', $divisionSlug);
            })
            ->with('division')
            ->firstOrFail();

        $user = Auth::user();
        
        // Get effective user for progress tracking
        $effectiveUser = $user->getEffectiveUser();
        $progress = $effectiveUser->progress()->where('activity_id', $activity->id)->first();
        $isFavorite = $progress && $progress->is_favorite;

        return view('activities.show', compact('activity', 'progress', 'isFavorite', 'effectiveUser'));
    }
}
