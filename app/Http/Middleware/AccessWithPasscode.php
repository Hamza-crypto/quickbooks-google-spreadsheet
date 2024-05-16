<?php

namespace App\Http\Middleware;

use Closure;

class AccessWithPasscode
{
    public function handle($request, Closure $next)
    {
        if ($request->is('telescope*') && $request->isMethod('GET')) {

            if (! $request->user()) {

                $passcode = $request->input('passcode');
                if ($passcode !== env('TELESCOPE_PASSCODE')) {
                    abort(403, 'Unauthorized');
                }
            }
        }

        return $next($request);
    }
}
