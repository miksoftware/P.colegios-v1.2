<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchoolSelected
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Admin users don't need a school selected - they can access dashboard without one
        if (auth()->user()->is_admin) {
            return $next($request);
        }

        // If user is not admin, they should have a school selected
        if (!session()->has('selected_school_id')) {
            // Auto-select the first school for normal users
            $school = auth()->user()->schools()->first();
            if ($school) {
                session(['selected_school_id' => $school->id]);
            } else {
                // Normal user without school - redirect to error or logout
                return redirect()->route('login')->with('error', 'No tienes asignado ningún colegio.');
            }
        }

        return $next($request);
    }
}
