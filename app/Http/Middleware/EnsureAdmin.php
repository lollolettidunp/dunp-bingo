<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(strtolower((string) $request->user()?->email) === strtolower((string) config('services.google.admin_email')), 403);

        return $next($request);
    }
}
