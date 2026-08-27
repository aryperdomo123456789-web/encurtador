<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureOwnerAdminHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $adminHost = (string) config('panel.admin_host', '');

        if ($adminHost !== '' && $request->getHost() === $adminHost) {
            abort_unless((bool) optional($request->user())->isOwner(), 403);
        }

        return $next($request);
    }
}
