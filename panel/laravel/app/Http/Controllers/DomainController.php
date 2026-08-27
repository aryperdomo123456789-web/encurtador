<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CustomerDomain;
use App\Support\Domains\DomainDnsResolver;
use App\Support\Domains\TlsProbeService;
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

        $workspaceId = $this->workspaceId($request);
        $domains = CustomerDomain::query()
            ->where('user_id', $request->user()->id)
            ->when($workspaceId !== null, fn ($query) => $query->where('workspace_id', $workspaceId))
            ->orderBy('created_at')
            ->get();

        return view('domains.index', [
            'domains' => $domains,
            'dnsTarget' => (string) config('panel.custom_domain_dns_target', ''),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless((bool) optional($request->user())->canUseCustomDomain(), 403);
        abort_unless($this->canManageWorkspace($request), 403);

        $data = $request->validate([
            'domain' => [
                'required',
                'string',
                'max:190',
                'regex:/^(?=.{4,190}$)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i',
            ],
        ], [
            'domain.regex' => 'Informe um dominio ou subdominio valido (ex.: links.cliente.com).',
        ]);

        $domain = strtolower(trim($data['domain']));

        $reserved = array_filter([
            strtolower((string) config('panel.host', '')),
            strtolower((string) config('shlink.default_domain', '')),
        ]);
        if (in_array($domain, $reserved, true)) {
            return back()->withInput()->withErrors(['domain' => 'Este dominio e reservado pelo sistema.']);
        }

        $existing = CustomerDomain::query()->where('domain', $domain)->first();
        if ($existing !== null && $existing->user_id !== $request->user()->id) {
            return back()->withInput()->withErrors(['domain' => 'Este dominio ja esta registrado por outra conta.']);
        }

        $target = (string) config('panel.custom_domain_dns_target', '');

        $customerDomain = CustomerDomain::query()->updateOrCreate(
            ['domain' => $domain, 'user_id' => $request->user()->id],
            [
                'workspace_id' => $this->workspaceId($request),
                'status' => 'pending_dns',
                'dns_target' => $target,
                'is_primary' => false,
                'tls_mode' => 'auto',
                'tls_status' => 'pending',
            ],
        );

        return redirect()
            ->route('domains.index')
            ->with('status', 'Dominio registrado. Aponte o DNS para '.$target.' e clique em Verificar.')
            ->with('domain_id', $customerDomain->id);
    }

    public function verify(Request $request, DomainDnsResolver $resolver, DomainService $domainService, CustomerDomain $customerDomain): RedirectResponse
    {
        abort_unless((bool) optional($request->user())->canUseCustomDomain(), 403);
        abort_unless($this->canManageWorkspace($request), 403);
        abort_unless($customerDomain->user_id === $request->user()->id, 403);
        abort_unless($this->belongsToWorkspace($request, $customerDomain->workspace_id), 403);

        $target = strtolower(trim((string) ($customerDomain->dns_target
            ?: config('panel.custom_domain_dns_target', ''))));

        if ($target === '') {
            return back()->withErrors(['domain' => 'Alvo de DNS nao configurado no painel. Contate o suporte.']);
        }

        $resolved = $resolver->resolveTargets($customerDomain->domain);
        if (! in_array($target, $resolved, true)) {
            $customerDomain->update(['status' => 'pending_dns']);

            return back()->withErrors([
                'domain' => 'DNS ainda nao aponta para '.$target.'. Registros encontrados: '
                    .($resolved === [] ? 'nenhum' : implode(', ', $resolved))
                    .'. Ajuste o CNAME/A no seu provedor e tente novamente em alguns minutos.',
            ]);
        }

        if ($customerDomain->status === 'active' && $customerDomain->dns_verified_at !== null) {
            $customerDomain->update(['dns_verified_at' => now()]);

            return redirect()
                ->route('domains.index')
                ->with('status', 'Dominio ja esta ativo. DNS confirmado novamente.');
        }

        try {
            $payload = $domainService->ensureRegistered($customerDomain->domain);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['domain' => 'Dominio invalido: '.$e->getMessage()]);
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['domain' => 'Falha ao registrar o dominio no Shlink. Tente novamente em alguns instantes.']);
        }

        $customerDomain->update([
            'status' => 'active',
            'dns_verified_at' => now(),
            'shlink_domain_registered_at' => now(),
            'shlink_domain_payload' => $payload,
            'tls_status' => 'pending',
        ]);

        return redirect()
            ->route('domains.index')
            ->with('status', 'Dominio verificado e ativo. TLS sera emitido automaticamente pelo proxy reverso; use "Testar HTTPS" para acompanhar.');
    }

    /**
     * Sonda HTTPS sob demanda para atualizar o estado do certificado TLS.
     * O certificado em si e emitido pelo proxy reverso (Caddy/Traefik + Lets Encrypt);
     * este endpoint apenas observa o resultado real.
     */
    public function tls(Request $request, TlsProbeService $probe, CustomerDomain $customerDomain): RedirectResponse
    {
        abort_unless((bool) optional($request->user())->canUseCustomDomain(), 403);
        abort_unless($this->canManageWorkspace($request), 403);
        abort_unless($customerDomain->user_id === $request->user()->id, 403);
        abort_unless($this->belongsToWorkspace($request, $customerDomain->workspace_id), 403);

        if ($customerDomain->status !== 'active') {
            return back()->withErrors(['domain' => 'Verifique o DNS antes de testar o certificado TLS.']);
        }

        $result = $probe->probe($customerDomain->fresh());

        $message = match ($result) {
            'active' => 'HTTPS ativo em '.$customerDomain->domain.'.',
            'pending' => 'Certificado ainda sendo emitido. Aguarde alguns minutos e teste novamente.',
            default => 'Nao foi possivel alcancar https://'.$customerDomain->domain.'. Confira DNS e proxy reverso.',
        };

        return redirect()->route('domains.index')->with('status', $message);
    }

    private function workspaceId(Request $request): ?int
    {
        $selected = (int) $request->session()->get('workspace_id', 0);
        $query = $request->user()->workspaces();
        $workspaceId = $selected > 0
            ? $query->whereKey($selected)->value('workspaces.id')
            : $query->orderBy('workspaces.id')->value('workspaces.id');

        return $workspaceId === null ? null : (int) $workspaceId;
    }

    private function belongsToWorkspace(Request $request, mixed $workspaceId): bool
    {
        return $workspaceId === null || $this->workspaceId($request) === (int) $workspaceId;
    }

    private function canManageWorkspace(Request $request): bool
    {
        $workspaceId = $this->workspaceId($request);
        if ($workspaceId === null) {
            return true;
        }

        $role = $request->user()->workspaces()->whereKey($workspaceId)->first()?->pivot?->role;

        return in_array((string) $role, ['owner', 'admin'], true);
    }

    public function destroy(Request $request, CustomerDomain $customerDomain): RedirectResponse
    {
        abort_unless((bool) optional($request->user())->canUseCustomDomain(), 403);
        abort_unless($this->canManageWorkspace($request), 403);
        abort_unless($customerDomain->user_id === $request->user()->id, 403);
        abort_unless($this->belongsToWorkspace($request, $customerDomain->workspace_id), 403);

        $customerDomain->delete();

        return redirect()
            ->route('domains.index')
            ->with('status', 'Dominio removido do painel. Remova o registro DNS no seu provedor.');
    }
}
