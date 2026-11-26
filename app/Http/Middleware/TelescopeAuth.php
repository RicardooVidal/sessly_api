<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class TelescopeAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->bearerToken() && $request->session()->has('telescope_token')) {
            $request->headers->set('Authorization', 'Bearer '.$request->session()->get('telescope_token'));
        }

        $user = Auth::guard('sanctum')->user();

        if (! $user || ! Gate::forUser($user)->allows('viewTelescope')) {
            return response('Não autorizado', 403);
        }

        Auth::shouldUse('sanctum');

        return $next($request);
    }
}
