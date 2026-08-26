<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class CreateApiToken extends Command
{
    protected $signature = 'melink:api-token
        {user : ID do usuário dono do token}
        {name : Nome humano do token}
        {--scope=read,write,analytics,events : Scopes separados por vírgula}
        {--expires= : Dias até expirar; padrão configurado no painel}';

    protected $description = 'Emite um token Bearer da API MElink e exibe o segredo uma única vez';

    public function handle(): int
    {
        $user = User::query()->find((int) $this->argument('user'));
        if ($user === null) {
            $this->error('Usuário não encontrado.');

            return self::FAILURE;
        }

        $scopes = array_values(array_filter(array_map(
            static fn (string $scope): string => trim($scope),
            explode(',', (string) $this->option('scope'))
        )));
        $allowedScopes = ['read', 'write', 'analytics', 'events'];
        if ($scopes === [] || array_diff($scopes, $allowedScopes) !== []) {
            $this->error('Scopes inválidos. Use: read, write, analytics, events.');

            return self::FAILURE;
        }

        $plainToken = 'mlk_live_'.Str::random(48);
        $expiryDays = $this->option('expires') === null
            ? (int) config('panel.api_token_expiry_days', 365)
            : max(0, (int) $this->option('expires'));
        $expiresAt = $expiryDays > 0 ? now()->addDays($expiryDays) : null;

        $token = ApiToken::query()->create([
            'user_id' => $user->id,
            'name' => trim((string) $this->argument('name')),
            'token_prefix' => ApiToken::prefixForPlainToken($plainToken),
            'token_hash' => ApiToken::hashPlainToken($plainToken),
            'scopes' => $scopes,
            'expires_at' => $expiresAt,
        ]);

        $this->newLine();
        $this->info('Token criado: #'.$token->id.' | usuário '.$user->id.' | expira em '.($expiresAt?->toISOString() ?? 'nunca'));
        $this->line('Scopes: '.implode(', ', $scopes));
        $this->newLine();
        $this->warn('Exiba este segredo uma única vez e armazene-o em um secret manager:');
        $this->line($plainToken);
        $this->newLine();

        return self::SUCCESS;
    }
}
