<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if ($user->isAdmin() || in_array($user->role, $roles, true)) {
            return $next($request);
        }

        abort(403, 'Akses ditolak: Anda tidak memiliki peran (role) yang diperlukan untuk mengakses halaman ini.');
    }
}
