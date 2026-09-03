<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoleChosen
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->needsRole()) {
            return response()->json([
                'success' => false,
                'message' => 'Əvvəlcə rol seçin: ailə və ya icraçı',
                'data' => null,
            ], 403);
        }

        return $next($request);
    }
}
