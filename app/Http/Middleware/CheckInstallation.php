<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckInstallation
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // The test suite runs against a fresh application on every run and
        // must not depend on (or mutate) the local storage/installed marker.
        if (app()->environment('testing')) {
            return $next($request);
        }

        $installedFile = storage_path('installed');
        $isInstalled = file_exists($installedFile);
        $isInstallRoute = $request->is('install*') || $request->is('install');

        // If not installed and not on install route or assets, redirect to install wizard
        if (! $isInstalled && ! $isInstallRoute && ! $request->is('build*') && ! $request->is('assets*')) {
            $base = rtrim($request->getBaseUrl(), '/');

            return redirect($base.'/install');
        }

        // If already installed and trying to access install wizard, redirect to login unless on complete screen
        if ($isInstalled && $isInstallRoute && ! $request->is('install/complete')) {
            $base = rtrim($request->getBaseUrl(), '/');

            return redirect($base.'/login')->with('info', 'OpenBooks SG is already installed.');
        }

        return $next($request);
    }
}
