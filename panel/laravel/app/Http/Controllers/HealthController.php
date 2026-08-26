<?php

namespace App\Http\Controllers;

use App\Models\CustomerDomain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Endpoints de saude:
 *  - GET /healthz  -> liveness (processo vivo, sem dependencias externas)
 *  - GET /health/ready -> readiness (DB + motor Shlink respondendo)
 *
 * Ambos retornam JSON, ficam fora do PANEL_HOST guard e nao usam sessao.
 */
class HealthController extends Controller
{
    public function live(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'panel',
            'time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Autoriza emissao on-demand de TLS pelo proxy reverso.
     *
     * O proxy consulta este endpoint antes de emitir o certificado.
     * Permitimos o dominio padrao de slugs e qualquer dominio cadastrado no
     * painel. Hosts reservados do painel continuam fora desse fluxo.
     */
    public function tlsAllow(Request $request): Response
    {
        $domain = strtolower(trim((string) $request->query('domain', '')));
        $reserved = array_filter([
            strtolower((string) config('panel.host', '')),
            strtolower((string) config('shlink.default_domain', '')),
        ]);

        $allowed = $domain !== ''
            && (
                in_array($domain, $reserved, true)
                || CustomerDomain::query()
                    ->where('domain', $domain)
                    ->where('status', 'active')
                    ->whereNotNull('dns_verified_at')
                    ->exists()
            );

        abort_unless($allowed, 403);

        return response()->noContent();
    }

    public function ready(): JsonResponse
    {
        $checks = [];
        $ok = true;

        // Database
        try {
            DB::connection()->getPdo();
            DB::select('select 1');
            $checks['database'] = ['status' => 'ok'];
        } catch (Throwable $e) {
            $ok = false;
            $checks['database'] = ['status' => 'fail'];
            Log::warning('panel.health.database_fail', ['exception' => $e::class, 'error' => $e->getMessage()]);
        }

        // Motor Shlink (endpoint publico /rest/health nao exige X-Api-Key).
        $base = rtrim((string) config('shlink.base_url', env('SHLINK_BASE_URL', '')), '/');
        if ($base === '') {
            $ok = false;
            $checks['shlink'] = ['status' => 'fail'];
            Log::error('panel.health.shlink_not_configured');
        } else {
            try {
                $started = microtime(true);
                $response = Http::timeout(5)
                    ->acceptJson()
                    ->get($base.'/rest/health');
                $latency = (int) ((microtime(true) - $started) * 1000);
                $healthy = $response->successful()
                    && strtolower((string) $response->json('status')) === 'pass';
                $checks['shlink'] = [
                    'status' => $healthy ? 'ok' : 'fail',
                    'http_status' => $response->status(),
                    'latency_ms' => $latency,
                ];
                if (! $healthy) {
                    $ok = false;
                    Log::warning('panel.health.shlink_fail', [
                        'http_status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                }
            } catch (Throwable $e) {
                $ok = false;
                $checks['shlink'] = ['status' => 'fail'];
                Log::warning('panel.health.shlink_fail', ['exception' => $e::class, 'error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'status' => $ok ? 'ok' : 'degraded',
            'service' => 'panel',
            'time' => now()->toIso8601String(),
            'checks' => $checks,
        ], $ok ? 200 : 503);
    }
}
