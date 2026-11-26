<?php

namespace App\Http\Middleware;

use App\Helpers\DatabaseHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSchema
{
    public function handle(Request $request, Closure $next): Response
    {
        $companyUuid = $request->header('X-Tenant');
        DatabaseHelper::changeSchema($companyUuid);

        return $next($request);
    }
}
