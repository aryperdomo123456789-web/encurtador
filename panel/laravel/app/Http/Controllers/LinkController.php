<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CustomerDomain;
use App\Models\MonthlyQuotaUsage;
use App\Models\ShortLink;
use App\Support\Shlink\LinkProvisioner;
use App\Support\Shlink\ShlinkClient;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

final class LinkController extends Controller
{
    public function index(): View
    {
        $user = request()->user();
        $monthStart = now('UTC')->startOfMonth();
        $nextMonthStart = (clone $monthStart)->addMonth();
        $createdThisMonth = ShortLink::query()
            ->where('user_id', $user->id)
            ->where('is_free_link', true)
            ->whereBetween('created_at', [$monthStart, $nextMonthStart])
            ->count();

        return view('links.index', [
            'links' => ShortLink::query()
                ->where('user_id', $user->id)
                ->latest('id')
                ->get(),
            'createdThisMonth' => $createdThisMonth,
            'freeLimit' => (int) config('shlink.free_monthly_limit', 5),
            'remainingFreeLinks' => max(0, (int) config('shlink.free_monthly_limit', 5) - $createdThisMonth),
            'monthlyUsage' => MonthlyQuotaUsage::query()
                ->where('user_id', $user->id)
                ->where('quota_month', $monthStart->format('Y-m'))
                ->first(),
            'isPremium' => (bool) $user->isPremium(),
        ]);
    }

    public function update(Request $request, ShortLink $link, ShlinkClient $client): RedirectResponse
    {
        abort_unless($this->canEditLink($request, $link), 404);

        $data = $request->validate([
            'long_url' => ['required', 'url', 'max:2048'],
        ]);

        try {
            $response = $client->updateShortUrl((string) $link->shlink_short_code, [
                'longUrl' => (string) $data['long_url'],
            ]);
            $link->update([
                'long_url' => $data['long_url'],
                'updated_by_user_id' => $request->user()->id,
                'shlink_response' => array_merge((array) $link->shlink_response, ['last_update' => $response]),
            ]);
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['long_url' => 'Não foi possível atualizar o destino agora.']);
        }

        return redirect()->route('links.index')->with('status', 'Destino do link atualizado.');
    }

    public function destroy(Request $request, ShortLink $link, ShlinkClient $client): RedirectResponse
    {
        abort_unless((int) $link->user_id === (int) $request->user()->id, 404);

        try {
            if ($link->shlink_short_code) {
                $client->deleteShortUrl((string) $link->shlink_short_code);
            }
            $link->delete();
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('links.index')
                ->withErrors(['link' => 'Não foi possível excluir o link agora.']);
        }

        return redirect()->route('links.index')->with('status', 'Link excluído.');
    }

    public function create(): View
    {
        $user = request()->user();
        $monthStart = now('UTC')->startOfMonth();
        $nextMonthStart = (clone $monthStart)->addMonth();
        $createdThisMonth = ShortLink::query()
            ->where('user_id', $user->id)
            ->where('is_free_link', true)
            ->whereBetween('created_at', [$monthStart, $nextMonthStart])
            ->count();

        return view('links.create', [
            'createdThisMonth' => $createdThisMonth,
            'freeLimit' => (int) config('shlink.free_monthly_limit', 5),
            'remainingFreeLinks' => max(0, (int) config('shlink.free_monthly_limit', 5) - $createdThisMonth),
            'isPremium' => (bool) $user->isPremium(),
        ]);
    }

    /**
     * Fluxo free: aceita apenas long_url. Slug aleatório e expiração de 7 dias
     * são derivados no servidor pelo LinkProvisioner.
     */
    public function store(Request $request, LinkProvisioner $provisioner): RedirectResponse
    {
        $data = $request->validate([
            'long_url' => ['required', 'url', 'max:2048'],
        ]);

        try {
            $response = $provisioner->createFreeLink(
                userId: (int) $request->user()->id,
                longUrl: (string) $data['long_url'],
                options: [
                    'findIfExists' => true,
                    'workspaceId' => $this->workspaceId($request),
                ],
            );
        } catch (DomainException $e) {
            return redirect()
                ->route('links.create')
                ->withInput()
                ->withErrors(['long_url' => 'Limite mensal de links gratuitos atingido. Faça upgrade para continuar.']);
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('links.create')
                ->withInput()
                ->withErrors(['long_url' => 'Entrada inválida para link gratuito.']);
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('links.create')
                ->withInput()
                ->withErrors(['long_url' => 'Não foi possível criar o link agora. Tente novamente em instantes.']);
        }

        $shortUrl = $response['shortUrl'] ?? null;

        return redirect()
            ->route('links.index')
            ->with('status', 'Link criado: '.($shortUrl ?? ''))
            ->with('short_url', $shortUrl);
    }

    public function createPremium(Request $request): View
    {
        abort_unless((bool) optional($request->user())->isPremium(), 403);

        return view('links.premium', [
            'isPremium' => true,
            'allowLifetimeLinks' => true,
            'customDomains' => CustomerDomain::query()
                ->where('user_id', $request->user()->id)
                ->where('status', 'active')
                ->orderBy('domain')
                ->get(),
        ]);
    }

    /**
     * Fluxo premium: slug, domínio próprio, tags, UTMs e expiração opcional.
     * O domínio é validado contra os registros ativos do usuário.
     */
    public function storePremium(Request $request, LinkProvisioner $provisioner): RedirectResponse
    {
        abort_unless((bool) optional($request->user())->isPremium(), 403);

        $data = $request->validate([
            'long_url' => ['required', 'url', 'max:2048'],
            'custom_slug' => ['required', 'string', 'regex:/^[a-z0-9][a-z0-9-]{1,38}[a-z0-9]$/'],
            'valid_until' => ['nullable', 'date', 'after:now', 'before:+1 year'],
            'title' => ['nullable', 'string', 'max:120'],
            'tags' => ['nullable', 'string', 'max:500'],
            'domain' => [
                'nullable',
                'string',
                'max:190',
                Rule::exists('customer_domains', 'domain')->where(
                    fn ($query) => $query
                        ->where('user_id', $request->user()->id)
                        ->where('status', 'active')
                ),
            ],
            'utm_source' => ['nullable', 'string', 'max:100', 'regex:/^[^&=#]+$/'],
            'utm_medium' => ['nullable', 'string', 'max:100', 'regex:/^[^&=#]+$/'],
            'utm_campaign' => ['nullable', 'string', 'max:100', 'regex:/^[^&=#]+$/'],
            'utm_term' => ['nullable', 'string', 'max:100', 'regex:/^[^&=#]+$/'],
            'utm_content' => ['nullable', 'string', 'max:100', 'regex:/^[^&=#]+$/'],
            'forward_query' => ['nullable', 'boolean'],
        ], [
            'custom_slug.regex' => 'O slug deve ter 3 a 40 caracteres: letras minúsculas, números e hífens, sem começar ou terminar em hífen.',
            'domain.exists' => 'Selecione um domínio ativo da sua conta ou verifique o DNS antes de usar.',
        ]);

        $options = [
            'workspaceId' => $this->workspaceId($request),
            'title' => $this->nullableString($data['title'] ?? null),
            'tags' => $this->normalizeTags($data['tags'] ?? null),
            'domain' => $this->nullableString($data['domain'] ?? null),
            'forwardQuery' => (bool) ($data['forward_query'] ?? false),
        ];
        if (! empty($data['valid_until'])) {
            $options['validUntil'] = $data['valid_until'];
        }

        $trackedUrl = $this->appendUtmParameters((string) $data['long_url'], $data);

        try {
            $response = $provisioner->createPremiumLink(
                userId: (int) $request->user()->id,
                longUrl: $trackedUrl,
                customSlug: (string) $data['custom_slug'],
                options: $options,
            );
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('links.premium')
                ->withInput()
                ->withErrors(['custom_slug' => 'Entrada inválida para link premium.']);
        } catch (Throwable $e) {
            report($e);
            Log::warning('links.premium_creation_failed', [
                'request_id' => $request->attributes->get('request_id'),
                'user_id' => $request->user()->id,
                'domain' => $data['domain'] ?? null,
                'exception' => $e::class,
            ]);

            return redirect()
                ->route('links.premium')
                ->withInput()
                ->withErrors(['custom_slug' => 'Não foi possível criar o link premium. Verifique se o slug está disponível.']);
        }

        $shortUrl = $response['shortUrl'] ?? null;

        return redirect()
            ->route('links.index')
            ->with('status', 'Link premium criado: '.($shortUrl ?? ''))
            ->with('short_url', $shortUrl);
    }

    /**
     * @param  array<string,mixed>  $data
     */
    private function appendUtmParameters(string $url, array $data): string
    {
        $utm = [];
        foreach (['source', 'medium', 'campaign', 'term', 'content'] as $key) {
            $value = $this->nullableString($data['utm_'.$key] ?? null);
            if ($value !== null) {
                $utm['utm_'.$key] = $value;
            }
        }

        if ($utm === []) {
            return $url;
        }

        $parts = parse_url($url);
        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return $url;
        }

        parse_str((string) ($parts['query'] ?? ''), $query);
        $query = array_merge($query, $utm);

        $rebuilt = $parts['scheme'].'://';
        if (isset($parts['user'])) {
            $rebuilt .= $parts['user'];
            if (isset($parts['pass'])) {
                $rebuilt .= ':'.$parts['pass'];
            }
            $rebuilt .= '@';
        }
        $rebuilt .= $parts['host'];
        if (isset($parts['port'])) {
            $rebuilt .= ':'.$parts['port'];
        }
        $rebuilt .= $parts['path'] ?? '';
        $rebuilt .= '?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        if (isset($parts['fragment'])) {
            $rebuilt .= '#'.$parts['fragment'];
        }

        return $rebuilt;
    }

    /**
     * @return list<string>
     */
    private function normalizeTags(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        return collect(explode(',', $value))
            ->map(static fn (string $tag): string => Str::lower(trim($tag)))
            ->filter(static fn (string $tag): bool => $tag !== '' && preg_match('/^[\p{L}\p{N}][\p{L}\p{N} _-]{0,38}$/u', $tag) === 1)
            ->unique()
            ->take(10)
            ->values()
            ->all();
    }

    private function canEditLink(Request $request, ShortLink $link): bool
    {
        if ((int) $link->user_id === (int) $request->user()->id) {
            return true;
        }
        if ($link->workspace_id === null) {
            return false;
        }

        $role = $request->user()->workspaces()->whereKey($link->workspace_id)->first()?->pivot?->role;

        return in_array((string) $role, ['owner', 'admin', 'member'], true);
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

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }
}
