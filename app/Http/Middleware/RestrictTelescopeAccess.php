<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RestrictTelescopeAccess
{
    public function handle(Request $request, Closure $next)
    {
        // List of allowed IP addresses (wildcards allowed)
        $allowedIps = [
            '223.123.*.*', //Z
            '2402:ad80:f8:4fb4:62e4:8cd8:fae7:1697',
            '119.155.*.*', // U
            '123.253.*.*'
        ];

        if (!$this->isAllowedIp($request->ip(), $allowedIps)) {
            // Abort with 403 Forbidden response if the IP is not allowed
            abort(403, 'Your IP is not whitelisted');
        }

        return $next($request);
    }


    protected function isAllowedIp($ip, $allowedIps)
    {
        foreach ($allowedIps as $allowedIp) {
            // Convert the allowed IP into a regular expression
            $pattern = str_replace(['*', '.'], ['[0-9]{1,3}', '\.'], $allowedIp);
            $pattern = '/^' . $pattern . '$/';

            // Check if the request IP matches the pattern
            if (preg_match($pattern, $ip)) {
                return true;
            }
        }

        return false;
    }
}
