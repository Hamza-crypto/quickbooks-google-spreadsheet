<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RestrictTelescopeAccess
{
    public function handle(Request $request, Closure $next)
    {
        // List of allowed IP addresses
        $allowedIps = [
            '223.123.93.116',
            '2402:ad80:f8:4fb4:62e4:8cd8:fae7:1697',
        ];

        if (!in_array($request->ip(), $allowedIps)) {
            // Abort with 403 Forbidden response if the IP is not allowed
            abort(403, 'Unauthorized access');
        }

        return $next($request);
    }
}
