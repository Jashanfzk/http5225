<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMembership
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $divisionSlug
     */
    public function handle(Request $request, Closure $next, ...$divisionSlugs): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Please log in to access this content.');
        }

        $user = auth()->user();
        $slug = $request->route('slug');

        // If no specific slug in route, check all provided slugs
        if (!$slug && count($divisionSlugs) > 0) {
            $slug = $divisionSlugs[0];
        }

        if ($slug && !$user->hasAccessToDivision($slug)) {
            return redirect()->route('membership.index')
                ->with('error', 'You need an active membership to access this division.');
        }

        return $next($request);
    }
}
