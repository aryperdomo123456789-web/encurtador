<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\MonthlyQuotaUsage;
use App\Models\ShortLink;
use App\Support\Shlink\LinkProvisioner;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function destroy(Request $request, ShortLink $link, \App\Support\Shlink\ShlinkClient $client): RedirectResponse
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
     * são derivados no servidor pelo LinkProvisioner. Campos como custom_slug,
     * domain e valid_until são ignorados nesta rota (uso premium virá depois).
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
                options: ['findIfExists' => true],
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
            ->with('status', 'Link criado: ' . ($shortUrl ?? ''))
            ->with('short_url', $shortUrl);
    }

    public function createPremium(Request $request): View
    {
        abort_unless((bool) optional($request->user())->isPremium(), 403);

        return view('links.premium', [
            'isPremium' => true,
            'allowLifetimeLinks' => true,
        ]);
    }

    /**
     * Fluxo premium: exige plano ativo com allow_custom_slug.
     * Aceita long_url + custom_slug (obrigatórios) e valid_until (opcional, <=1 ano).
     * Domínio próprio e outras regras premium ficam para os próximos itens do P1.
     */
    public function storePremium(Request $request, LinkProvisioner $provisioner): RedirectResponse
    {
        abort_unless((bool) optional($request->user())->isPremium(), 403);

        $data = $request->validate([
            'long_url'    => ['required', 'url', 'max:2048'],
            'custom_slug' => ['required', 'string', 'regex:/^[a-z0-9][a-z0-9-]{1,38}[a-z0-9]$/'],
            'valid_until' => ['nullable', 'date', 'after:now', 'before:+1 year'],
        ], [
            'custom_slug.regex' => 'O slug deve ter 3 a 40 caracteres: letras minúsculas, números e hífens, sem começar ou terminar em hífen.',
        ]);

        $options = [];
        if (!empty($data['valid_until'])) {
            $options['validUntil'] = $data['valid_until'];
        }

        try {
            $response = $provisioner->createPremiumLink(
                userId: (int) $request->user()->id,
                longUrl: (string) $data['long_url'],
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

            return redirect()
                ->route('links.premium')
                ->withInput()
                ->withErrors(['custom_slug' => 'Não foi possível criar o link premium. Verifique se o slug está disponível.']);
        }

        $shortUrl = $response['shortUrl'] ?? null;

        return redirect()
            ->route('links.index')
            ->with('status', 'Link premium criado: ' . ($shortUrl ?? ''))
            ->with('short_url', $shortUrl);
    }
}
