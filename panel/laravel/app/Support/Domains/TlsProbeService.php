<?php

declare(strict_types=1);

namespace App\Support\Domains;

use App\Models\CustomerDomain;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Sonda HTTPS um dominio customizado para descobrir se o certificado TLS
 * ja foi emitido pelo proxy reverso (Caddy/Traefik com Lets Encrypt).
 *
 * Nao emite certificados: apenas observa o estado real via requisicao HTTPS
 * e atualiza tls_status/tls_last_error no CustomerDomain.
 */
final class TlsProbeService
{
    public function __construct(private readonly int $timeout = 10)
    {
    }

    public function probe(CustomerDomain $domain): string
    {
        $host = strtolower(trim((string) $domain->domain));
        if ($host === '') {
            $domain->update(['tls_status' => 'error', 'tls_last_error' => 'Dominio vazio.']);
            return 'error';
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withOptions(['verify' => true, 'allow_redirects' => false])
                ->withHeaders(['User-Agent' => 'PanelTlsProbe/1.0'])
                ->get('https://' . $host . '/');

            // Qualquer resposta HTTP significa que o handshake TLS funcionou.
            $domain->update([
                'tls_status'     => 'active',
                'tls_last_error' => null,
            ]);

            return 'active';
        } catch (Throwable $e) {
            $message = mb_substr($e->getMessage(), 0, 250);
            $status  = str_contains(strtolower($message), 'ssl') || str_contains(strtolower($message), 'certificate')
                ? 'pending'
                : 'error';

            $domain->update([
                'tls_status'     => $status,
                'tls_last_error' => $message,
            ]);

            return $status;
        }
    }
}
