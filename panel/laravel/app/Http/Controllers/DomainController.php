<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CustomerDomain;
use App\Support\Domains\DomainDnsResolver;
use App\Support\Shlink\DomainService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

final class DomainController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless((bool) optional($request->user())->canUseCustomDomain(), 403);

        $domains = CustomerDomain::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at')
            ->get();

        return view('domains.index', [
            'domains'    => $domains,
            'dnsTarget'  => (string) config('panel.custom_domain_dns_target', ''),
        ]);
    }

    /**
     * Registra o pedido do cliente e devolve as instruções de DNS.
     * O domínio só é registrado no Shlink depois do verify().
     */
    public function store(Request $request): RedirectResponse
    {
        abort_unless((bool) optional($request->user())->canUseCustomDomain(), 403);

        $data = $request->validate([
            'domain' => [
                'required',
                'string',
                'max:190',
                'regex:/^(?=.{4,190}$)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i',
            ],
        ], [
            'domain.regex' => 'Informe um domínio ou subdomínio válido (ex.: links.cliente.com).',
        ]);

        $domain = strtolower(trim($data['domain']));

        // Impede colisão com host do painel ou host default do Shlink.
        $reserved = array_filter([
            strtolower((string) config('panel.host', '')),
            strtolower((string) config('shlink.default_domain', '')),
        ]);
        if (in_array($domain, $reserved, true)) {
            return back()->withInput()->withErrors(['domain' => 'Este domínio é reservado pelo sistema.']);
        }

        $existing = CustomerDomain::query()->where('domain', $domain)->first();
        if ($existing !== null && $existing->user_id !== $request->user()->id) {
            return back()->withInput()->withErrors(['domain' => 'Este domínio já está registrado por outra conta.']);
        }

        $target = (string) config('panel.custom_domain_dns_target', '');

        $customerDomain = CustomerDomain::query()->updateOrCreate(
            ['domain' => $domain, 'user_id' => $request->user()->id],
            [
                'status'     => 'pending_dns',
                'dns_target' => $target,
                'is_primary' => false,
                'tls_mode'   => 'auto',
                'tls_status' => 'pending',
            ],
        );

        return redirect()
            ->route('domains.index')
            ->with('status', 'Domínio registrado. Aponte o DNS para ' . $target . ' e clique em Verificar.')
            ->with('domain_id', $customerDomain->id);
    }

    /**
     * Verifica DNS e, se OK, registra o domínio no Shlink e marca como ativo.
     */
    public function verify(Request $request, DomainDnsResolver $resolver, DomainService $domainService, CustomerDomain $customerDomain): RedirectResponse
    {
        abort_unless((bool) optional($request->user())->canUseCustomDomain(), 403);
        abort_unless($customerDomain->user_id === $request->user()->id, 403);

        $target = strtolower(trim((string) ($customerDomain->dns_target
            ?: config('panel.custom_domain_dns_target', ''))));

        if ($target === '') {
            return back()->withErrors(['domain' => 'Alvo de DNS não configurado no painel.']);
        }

        $resolved = $resolver->resolveTargets($customerDomain->domain);
        if (!in_array($target, $resolved, true)) {
            $customerDomain->update(['status' => 'pending_dns']);
            return back()->withErrors([
                'domain' => 'DNS ainda não aponta para ' . $target . '. Registros encontrados: '
                    . ($resolved === [] ? 'nenhum' : implode(', ', $resolved)),
            ]);
        }

        try {
            $payload = $domainService->ensureRegistered($customerDomain->domain);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['domain' => 'Domínio inválido: ' . $e->getMessage()]);
        } catch (Throwable $e) {
            report($e);
            return back()->withErrors(['domain' => 'Falha ao registrar o domínio no Shlink. Tente novamente em instantes.']);
        }

        $customerDomain->update([
            'status'                      => 'active',
            'dns_verified_at'             => now(),
            'shlink_domain_registered_at' => now(),
            'shlink_domain_payload'       => $payload,
            'tls_status'                  => 'pending',
        ]);

        return redirect()
            ->route('domains.index')
            ->with('status', 'Domínio verificado e registrado no Shlink.');
    }

    public function destroy(Request $request, CustomerDomain $customerDomain): RedirectResponse
    {
        abort_unless((bool) optional($request->user())->canUseCustomDomain(), 403);
        abort_unless($customerDomain->user_id === $request->user()->id, 403);

        $customerDomain->delete();

        return redirect()
            ->route('domains.index')
            ->with('status', 'Domínio removido do painel. Remova o registro DNS no seu provedor.');
    }
}
