<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Shlink\DomainService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DomainController extends Controller
{
    public function index(): View
    {
        return view('domains.index');
    }

    public function store(Request $request, DomainService $domainService): RedirectResponse
    {
        $data = $request->validate([
            'domain' => ['required', 'string', 'max:190'],
        ]);

        $domainService->ensureRegistered($data['domain']);

        return redirect()
            ->route('domains.index')
            ->with('status', 'Domínio registrado com sucesso.');
    }
}
