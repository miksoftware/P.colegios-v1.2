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
        // If user is not admin, they should have a school selected
        if (!auth()->user()->is_admin && !session()->has('selected_school_id')) {
            // Auto-select the first school for normal users
            $school = auth()->user()->schools()->first();
            if ($school) {
                session(['selected_school_id' => $school->id]);
            }
        }

        // If user is admin and no school selected, redirect to selection
        if (auth()->user()->is_admin && !session()->has('selected_school_id')) {
            if (!$request->routeIs('school.select')) {
                return redirect()->route('school.select');
            }
        }

        return $next($request);
    }
}
