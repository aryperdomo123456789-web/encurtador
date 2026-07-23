# Roteamento de borda — isolando Shlink do painel

Este documento é a **fonte da verdade** sobre como um clique em link curto
chega ao motor Shlink sem passar pelo painel PHP/Laravel, e como um domínio
de cliente (`dominiodocliente.com`) é servido com TLS automático.

## Diagrama

```
                    ┌─────────────────────────────────────────┐
Internet  ─── 443 ──▶│  Caddy (borda, único exposto)          │
                    │                                         │
                    │  ┌───────────────────────────────────┐  │
                    │  │ Host = app.me.vr766.com           │──┼─▶ panel:8000  (Laravel)
                    │  │ Host = me.vr766.com               │──┼─▶ shlink:8080
                    │  │ Host = *  (on_demand_tls)         │──┼─▶ shlink:8080
                    │  └───────────────────────────────────┘  │
                    └─────────────────────────────────────────┘
```

O painel PHP **nunca** vê `/slug`. Ele só responde em `app.me.vr766.com`,
e o middleware `PanelHostGuard` (config `panel.host`) rejeita qualquer
outro Host que vaze até lá.

## Regras (arquivo `ops/Caddyfile`)

1. `app.me.vr766.com` → painel Laravel. UI, auth, API interna, Stripe webhook,
   endpoint `/api/tls/allow`.
2. `me.vr766.com/{slug}` e `www.me.vr766.com/{slug}` → Shlink direto.
   Redirect 302 emitido pelo próprio Shlink em ~5ms.
3. Qualquer outro host chegando em `:443` cai no bloco `on_demand_tls`:
   - Caddy consulta `panel:8000/api/tls/allow?domain=<host>`.
   - Se o painel confirmar (domínio pertence a um usuário premium e está
     verificado), Caddy emite/renova o cert Let's Encrypt e faz proxy
     para `shlink:8080`.
   - Se o painel negar, Caddy recusa o handshake — sem cert, sem tráfego.
4. `:80` redireciona tudo para `:443`.

## Por que Shlink resolve certo mesmo em domínios diferentes

O Shlink usa o header `Host` para descobrir qual domínio o cliente pediu
e busca o slug **naquele domínio** no MariaDB. Isso funciona porque:

- O painel registra cada `CustomerDomain` no Shlink via
  `POST /rest/v3/domains` (ver `DomainService::ensureDomainExists`).
- Ao criar um link premium com `custom_slug`, o painel passa `domain`
  igual ao domínio do cliente (`LinkController::store` no fluxo premium).
- Caddy preserva o `Host` original ao encaminhar (`header_up
  X-Forwarded-Host` + comportamento default do `reverse_proxy`).

## Isolamento operacional

- O Shlink só escuta `127.0.0.1:8080` no host (ver `compose.yml`). Nunca
  é exposto direto na Internet — só Caddy chega nele pela rede Docker.
- O painel só escuta `127.0.0.1:8000`. Mesma lógica.
- Restart do painel **não** derruba links curtos.
- Deploy de nova versão do painel **não** afeta latência de redirect.
- Falha no Stripe/webhook não afeta redirect.

## Endpoint `/api/tls/allow`

Retorna `200` se o domínio pode ter cert emitido, `403` caso contrário.
Fica dentro do painel (`routes/web.php`) e consulta a tabela
`customer_domains` com status `verified`. É o único acoplamento entre
Caddy e painel — e é read-only.

## Como testar localmente

```
# 1. Painel em app.me.vr766.com
curl -H 'Host: app.me.vr766.com' https://localhost/login

# 2. Redirect padrão
curl -I -H 'Host: me.vr766.com' https://localhost/abc123
# → HTTP/2 302, location: <url longa>, servido pelo Shlink

# 3. Domínio de cliente (após verify + provision)
curl -I -H 'Host: promo.cliente.com' https://localhost/black-friday
# → HTTP/2 302
```

## Referências

- `ops/Caddyfile` — configuração exata.
- `docs/tls-automatico.md` — fluxo de emissão de certificado.
- `docs/architecture.md` — matriz painel × motor.
- `panel/laravel/app/Http/Controllers/DomainController.php` — verify/store/destroy.
- `panel/laravel/config/panel.php` — `PANEL_HOST` guard.
