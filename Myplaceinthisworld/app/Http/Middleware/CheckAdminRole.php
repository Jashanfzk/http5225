<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Please log in to access the admin area.');
        }

        $user = auth()->user();

        if (!$user->hasRole('administrator')) {
            return redirect()->route('home')->with('error', 'You do not have permission to access the admin area.');
        }

        return $next($request);
    }
}

