<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ApiToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class ApiTokenController extends Controller
{
    public function index(Request $request): View
    {
        return view('api-tokens.index', [
            'tokens' => $request->user()->apiTokens()->latest('id')->get(),
        ]);
    }

    public function store(Request $request): View
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'scopes' => ['required', 'array', 'min:1'],
            'scopes.*' => ['string', 'in:read,write,analytics'],
            'expires_in_days' => ['nullable', 'integer', 'min:0', 'max:730'],
        ]);

        $plainToken = 'mlk_live_'.Str::random(48);
        $expiryDays = array_key_exists('expires_in_days', $data)
            ? (int) $data['expires_in_days']
            : (int) config('panel.api_token_expiry_days', 365);
        $expiresAt = $expiryDays > 0 ? now()->addDays($expiryDays) : null;
        $user = $request->user();

        $token = $user->apiTokens()->create([
            'name' => trim((string) $data['name']),
            'token_prefix' => ApiToken::prefixForPlainToken($plainToken),
            'token_hash' => ApiToken::hashPlainToken($plainToken),
            'scopes' => array_values(array_unique($data['scopes'])),
            'expires_at' => $expiresAt,
        ]);

        return view('api-tokens.index', [
            'tokens' => $user->apiTokens()->latest('id')->get(),
            'newToken' => $plainToken,
            'newTokenId' => $token->id,
        ]);
    }

    public function destroy(Request $request, ApiToken $apiToken): RedirectResponse
    {
        abort_unless((int) $apiToken->user_id === (int) $request->user()->id, 404);

        $apiToken->forceFill(['revoked_at' => now()])->save();

        return back()->with('status', 'Token revogado. Integrações que o utilizavam perderam acesso imediatamente.');
    }
}
