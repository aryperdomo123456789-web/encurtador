<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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
        return view('links.index');
    }

    public function create(): View
    {
        return view('links.create');
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
            // Cota mensal free atingida.
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

        return redirect()
            ->route('links.index')
            ->with('status', 'Link criado: ' . ($response['shortUrl'] ?? ''))
            ->with('short_url', $response['short_url'] ?? null);
    }
}
