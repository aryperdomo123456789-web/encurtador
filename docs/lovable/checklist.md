# Checklist de Produção — Painel Shlink

> Fonte da verdade do backlog. Manter atualizado a cada PR mesclado.

## P0 — Base (concluído)
- [x] Matriz de responsabilidades documentada (`docs/architecture.md`)
- [x] `.gitignore` cobre artefatos de OS/IDE/Docker/Laravel
- [x] `PANEL_HOST` configurado, domain guard e `trustProxies`
- [x] Auth base mínimo (`AuthController`, login/logout, view)
- [x] Testes de cota free e login

## P1 — Fluxos principais (concluído)
- [x] Integração Shlink (config, client, health check, testes)
- [x] Criar links free (slug aleatório + expiração 7 dias)
- [x] Criar links premium (custom slug + valid_until)
- [x] Registrar domínio próprio no Shlink (store/verify/destroy)
- [x] TLS automático (probe + agendamento + badge UI)
- [x] Deploy Caddy (Caddyfile, on-demand TLS, compose.prod)
- [x] **Painel de UI completo** (layout, dashboard, lista de links, analytics)

## P2 — Produção (em andamento)
- [ ] Reset automático de cota mensal (comando + agendamento)
- [ ] Billing Stripe (checkout, webhook, upgrade/downgrade)
- [ ] Observabilidade (logs estruturados, métricas, alertas)
- [ ] Fluxo de troca de senha e recuperação
- [ ] Rate limiting por usuário nas rotas de criação
- [ ] Testes E2E do fluxo completo

## Regras
- Não avançar em P2 com bloqueios em P1.
- Segredos e logs ficam fora do repositório.
- Todo PR passa por `php artisan test` antes do merge.
