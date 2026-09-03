<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserNotBlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user instanceof User && $user->isBlocked()) {
            return response()->json([
                'success' => false,
                'message' => 'Sizin profiliniz admin tərəfindən bloklanıb.',
                'code' => 'ACCOUNT_BLOCKED',
                'data' => null,
            ], 403);
        }

        return $next($request);
    }
}
