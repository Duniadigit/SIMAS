<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Pemakaian: EnsureRole::class.':admin,kaprodi' */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless($user && $user->is_active, 403, 'Akun tidak aktif.');
        abort_if($roles && ! in_array($user->role->value, $roles, true), 403, 'Anda tidak memiliki akses.');

        return $next($request);
    }
}
