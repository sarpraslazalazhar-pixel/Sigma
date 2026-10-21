<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if ($user->can($permission)) {
            return $next($request);
        }

        abort(403, "Akses ditolak: Anda tidak memiliki izin ({$permission}) untuk mengakses halaman ini.");
    }
}
