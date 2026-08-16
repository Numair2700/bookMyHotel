<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route guard for role-based access (spec 9). Usage: ->middleware('role:admin')
 * or 'role:manager,admin'. Authorisation stays in middleware and policies, not
 * scattered through controllers.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! in_array($user->role, $roles, true)) {
            abort(403);
        }

        return $next($request);
    }
}
