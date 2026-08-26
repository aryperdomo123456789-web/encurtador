# Topologia oficial do MElink

Fonte da verdade da arquitetura de hosts do projeto. Qualquer texto anterior que
descreva um domínio de painel diferente (`app.me.vr766.com`, `panel.*`, etc.)
está **obsoleto** e não deve ser usado.

## Regra resumida

> `me.vr766.com` é o painel, `api-shlink.vr766.com` é o motor, e tudo que for
> `/{slug}` ou domínio de cliente passa pelo fallback do painel e segue para o
> motor de links.

## Hosts ativos

| Host | Papel | Backend | Observação |
|---|---|---|---|
| `me.vr766.com` | Painel administrativo (Laravel 12 / PHP 8.3) | Painel Laravel via aaPanel/Nginx + PHP-FPM | Único host que executa PHP do painel. Responde só nas rotas administrativas listadas abaixo. |
| `me.vr766.com/{slug}` | Redirect público de slug | Laravel fallback -> motor de links | O request de `/{slug}` cai primeiro no painel e o `PublicRedirectController` repassa para o motor. |
| `api-shlink.vr766.com` | API/motor de links | Contêiner Docker do motor | Consumida pelo painel via `X-Api-Key`. Também aceita hits de redirect quando o hostname público apontar aqui. |
| `{cliente}.tld` (CNAME) | Domínio próprio de cliente premium | Motor de links | CNAME aponta para o hostname público do motor; TLS emitido pelo proxy reverso (aaPanel/Nginx + certbot). Nunca passa pelo Laravel. |

## Hosts obsoletos

- `app.me.vr766.com` — **não faz parte da topologia atual**. Nenhuma
  documentação nova, `.env.example`, `Caddyfile`, vhost ou script deve
  referenciá-lo. Se aparecer em texto antigo, tratar como legado a remover.

## Rotas do painel (host `me.vr766.com`)

Somente estas rotas respondem pelo Laravel. Qualquer path fora desta lista que
não seja rota administrativa é atendido pelo Laravel e, se não for reconhecido,
segue para o fallback público do painel que repassa ao motor.

- `/` — landing/entrada do painel
- `/login`, `/logout` — autenticação
- `/admin`, `/admin/users`, `/admin/users/{user}` — admin do dono
- `/dashboard` — painel do usuário logado
- `/links`, `/links/create`, `/links/premium` — gestão de links
- `/domains`, `/domains/verify`, `/domains/{id}` — domínios próprios
- `/billing`, `/billing/portal`, `/billing/webhook` — Stripe
- `/analytics/{shortCode}` — leitura de métricas via API do motor
- `/healthz`, `/health/ready` — health checks
- `/tls/allow` — endpoint read-only consultado pelo proxy reverso antes de
  emitir certificado on-demand para domínio de cliente

Todo o resto — inclusive `/{slug}` — passa pelo fallback do Laravel e então
vai para o motor.

## Camada de borda (aaPanel/Nginx)

O host real já roda em **aaPanel + Nginx + PHP-FPM**. Nenhuma dependência nova
de proxy (Caddy, Traefik) foi introduzida no projeto. Caddy/Traefik podem
aparecer em documentação de exemplos históricos, mas o deploy oficial usa o
vhost do aaPanel.

Responsabilidades do vhost `me.vr766.com`:

1. Terminar TLS (Let's Encrypt gerenciado pelo aaPanel/certbot).
2. Servir as rotas administrativas listadas acima via PHP-FPM (document root:
   `panel/laravel/public`).
3. Encaminhar qualquer outro path para o painel Laravel, que usa fallback para
   repassar slugs ao motor sem executar lógica extra no vhost.
4. Para domínios de cliente (`{cliente}.tld`): TLS on-demand (certbot/aaPanel)
   consultando `GET https://me.vr766.com/tls/allow?domain={host}` antes de
   emitir, e proxy direto para o motor.

## Isolamento operacional

- Restart, deploy ou queda do painel Laravel afeta o fallback de slug.
- Falha no Stripe/webhook **não** afeta redirect.
- Painel e motor não compartilham banco.
- O painel é o único componente que fala com o MariaDB do SaaS.
- O motor é o único que emite o 302 final.

## Como testar

```sh
# rota administrativa: deve responder pelo painel Laravel
curl -I https://me.vr766.com/login

# slug: deve responder 302 do motor via fallback do painel
curl -I https://me.vr766.com/abc123

# domínio de cliente: mesmo comportamento do slug
curl -I -H 'Host: links.cliente.com' https://<ip-do-servidor>/abc123

# API do motor: só o painel autentica com X-Api-Key
curl -I https://api-shlink.vr766.com/rest/health
```

## Referências

- [`docs/architecture.md`](architecture.md) — matriz de responsabilidades por host.
- [`docs/tls-automatico.md`](tls-automatico.md) — fluxo de TLS on-demand no aaPanel/Nginx.
- [`docs/lovable/checklist.md`](lovable/checklist.md) — backlog rumo a produção.
- [`panel/laravel/config/panel.php`](../panel/laravel/config/panel.php) — guard `PANEL_HOST`.
