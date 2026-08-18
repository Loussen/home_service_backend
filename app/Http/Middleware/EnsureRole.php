<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (! $user || $user->active_role !== $role) {
            return response()->json([
                'success' => false,
                'message' => $role === 'provider'
                    ? 'Bu əməliyyat yalnız xidmət göstərən üçündür'
                    : 'Bu əməliyyat yalnız müştəri üçündür',
                'data' => null,
            ], 403);
        }

        return $next($request);
    }
}
