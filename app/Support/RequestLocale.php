<?php

namespace App\Support;

use App\Repositories\AppStringRepository;
use Illuminate\Http\Request;

final class RequestLocale
{
    public static function from(?Request $request = null): string
    {
        $request ??= request();

        return app(AppStringRepository::class)->normalize(
            $request->query('locale') ?? $request->header('Accept-Language')
        );
    }
}
