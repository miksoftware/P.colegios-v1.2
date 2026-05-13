<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckNotInstalled
{
    /**
     * Block access to the install wizard if the system is already installed.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $lockFile = storage_path('app/installed.lock');

        if (file_exists($lockFile) || env('APP_INSTALLED') === 'true') {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
