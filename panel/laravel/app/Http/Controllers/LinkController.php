<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ShortLink;
use App\Support\Shlink\LinkProvisioner;
use App\Support\Shlink\ShlinkClient;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

final class LinkController extends Controller
{
    public function index(Request $request): View
    {
        $query = ShortLink::where('user_id', $request->user()->id);

        if ($q = $request->string('q')->trim()->toString()) {
            $query->where(function ($w) use ($q) {
                $w->where('shlink_short_code', 'like', "%{$q}%")
                    ->orWhere('long_url', 'like', "%{$q}%");
            });
        }

        $filter = $request->string('filter')->toString();
        if ($filter === 'free') {
            $query->where('is_free_link', true);
        } elseif ($filter === 'premium') {
            $query->where('is_free_link', false);
        } elseif ($filter === 'expiring') {
            $query->whereNotNull('valid_until')
                ->whereBetween('valid_until', [Carbon::now(), Carbon::now()->addDays(3)]);
        }

        $links = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('links.index', compact('links'));
    }

    public function create(): View
    {
        return view('links.create');
    }

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
            return redirect()->route('links.create')->withInput()
                ->withErrors(['long_url' => 'Limite mensal de links gratuitos atingido. Faça upgrade para continuar.']);
        } catch (InvalidArgumentException $e) {
            return redirect()->route('links.create')->withInput()
                ->withErrors(['long_url' => 'Entrada inválida para link gratuito.']);
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('links.create')->withInput()
                ->withErrors(['long_url' => 'Não foi possível criar o link agora. Tente novamente em instantes.']);
        }

        $shortUrl = $response['shortUrl'] ?? null;

        return redirect()->route('links.index')
            ->with('status', 'Link criado: ' . ($shortUrl ?? ''))
            ->with('short_url', $shortUrl);
    }

    public function destroy(Request $request, ShortLink $link, ShlinkClient $shlink): RedirectResponse
    {
        abort_if($link->user_id !== $request->user()->id, 403);

        try {
            $shlink->deleteShortUrl($link->shlink_short_code);
        } catch (\Throwable $e) {
            report($e);
        }

        $link->delete();

        return redirect()->route('links.index')->with('status', 'Link excluído.');
    }

    public function createPremium(Request $request): View
    {
        abort_unless((bool) optional($request->user())->isPremium(), 403);
        return view('links.premium');
    }

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
            return redirect()->route('links.premium')->withInput()
                ->withErrors(['custom_slug' => 'Entrada inválida para link premium.']);
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('links.premium')->withInput()
                ->withErrors(['custom_slug' => 'Não foi possível criar o link premium. Verifique se o slug está disponível.']);
        }

        $shortUrl = $response['shortUrl'] ?? null;

        return redirect()->route('links.index')
            ->with('status', 'Link premium criado: ' . ($shortUrl ?? ''))
            ->with('short_url', $shortUrl);
    }
}
