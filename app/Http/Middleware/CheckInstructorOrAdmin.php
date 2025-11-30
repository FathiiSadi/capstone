<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckInstructorOrAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return redirect('/auth/login')->with('error', 'Please login to access this page.');
        }

        // Check if user has instructor or admin role
        $user = auth()->user();
        if (!in_array($user->role, ['instructor', 'admin'])) {
            return redirect('/')->with('error', 'Access denied. You must be an instructor or admin to access this page.');
        }

        return $next($request);
    }
}
