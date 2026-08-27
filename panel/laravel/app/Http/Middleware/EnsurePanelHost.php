<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsurePanelHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredHosts = array_filter([
            (string) config('panel.host', ''),
            (string) config('panel.admin_host', ''),
        ]);

        // Local/testes podem servir o painel sem restrição de host.
        if ($configuredHosts !== [] && ! in_array($request->getHost(), $configuredHosts, true)) {
            abort(404);
        }

        return $next($request);
    }
}
