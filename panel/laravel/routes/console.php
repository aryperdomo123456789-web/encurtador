<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sonda de TLS: a cada 15 minutos observa o estado real dos dominios
// customizados. O proxy reverso (Caddy/Traefik) e quem emite os certificados
// automaticamente via Lets Encrypt; este job apenas espelha o resultado
// no painel para o usuario.
Schedule::command('panel:tls:refresh')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// Virada de mes da cota free: enforcement e feito em tempo real por SQL
// (LinkProvisioner conta ShortLink.created_at do mes vigente). Este job
// apenas marca o rollover, poda historico antigo de monthly_quota_usage
// e emite log estruturado para auditoria/observabilidade.
Schedule::command('panel:quota:reset')
    ->monthlyOn(1, '00:05')
    ->timezone('UTC')
    ->withoutOverlapping();
