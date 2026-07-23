<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CustomerDomain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Endpoint público consultado pelo proxy reverso (Caddy `on_demand_tls.ask`
 * ou equivalente) para decidir se deve emitir um certificado TLS para
 * determinado domínio. Só autoriza domínios registrados e verificados no
 * painel; qualquer outra requisição responde 404 para evitar emissão
 * indiscriminada de certificados Let's Encrypt.
 */
final class TlsAllowController extends Controller
{
    public function allow(Request $request): Response|JsonResponse
    {
        $domain = strtolower(trim((string) $request->query('domain', '')));

        if ($domain === '') {
            return response()->json(['allowed' => false, 'reason' => 'missing_domain'], 400);
        }

        // Bloqueia o host do painel e o domínio default do Shlink por
        // segurança — esses são servidos por outros vhosts.
        $reserved = array_filter([
            strtolower((string) config('panel.host', '')),
            strtolower((string) config('shlink.default_domain', '')),
        ]);
        if (in_array($domain, $reserved, true)) {
            return response()->json(['allowed' => false, 'reason' => 'reserved'], 404);
        }

        $registered = CustomerDomain::query()
            ->where('domain', $domain)
            ->whereIn('status', ['pending_dns', 'active'])
            ->exists();

        if (! $registered) {
            return response()->json(['allowed' => false, 'reason' => 'not_registered'], 404);
        }

        return response()->json(['allowed' => true]);
    }
}
