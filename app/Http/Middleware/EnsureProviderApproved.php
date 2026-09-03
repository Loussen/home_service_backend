<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProviderApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && $user->isProvider() && $user->needsProviderApproval()) {
            return response()->json([
                'success' => false,
                'message' => 'Hesabınız hələ təsdiqlənməyib. Sorğunuz 1 saat ərzində baxılacaq.',
                'data' => [
                    'provider_approval_status' => $user->provider_approval_status,
                ],
            ], 403);
        }

        return $next($request);
    }
}
