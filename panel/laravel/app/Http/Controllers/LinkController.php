<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Shlink\LinkProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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

    public function store(Request $request, LinkProvisioner $provisioner): RedirectResponse
    {
        $data = $request->validate([
            'long_url' => ['required', 'url'],
            'premium' => ['nullable', 'boolean'],
            'custom_slug' => ['nullable', 'string', 'max:190'],
            'domain' => ['nullable', 'string', 'max:190'],
            'valid_until' => ['nullable', 'date'],
        ]);

        $response = $provisioner->provision(
            userId: (int) $request->user()->id,
            longUrl: (string) $data['long_url'],
            options: [
                'premium' => (bool) ($data['premium'] ?? false),
                'customSlug' => $data['custom_slug'] ?? null,
                'domain' => $data['domain'] ?? null,
                'validUntil' => $data['valid_until'] ?? null,
                'findIfExists' => true,
            ]
        );

        return redirect()
            ->route('links.index')
            ->with('status', 'Link criado: ' . ($response['shortUrl'] ?? 'ok'));
    }
}
