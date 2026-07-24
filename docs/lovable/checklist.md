# Checklist de producao

Fonte da verdade do progresso rumo a producao do painel Shlink.
Cada item so muda de estado com PR/commit correspondente.

> **Topologia oficial:** `me.vr766.com` = painel; `api-shlink.vr766.com` = motor;
> `me.vr766.com/{slug}` e dominios de cliente vao direto ao Shlink. Detalhes em
> [`docs/topology.md`](../topology.md). Qualquer texto que cite
> `app.me.vr766.com` esta obsoleto.

## Prioridade P0

Itens que bloqueiam qualquer entrega segura.

| Pri | Status | Item |
|---|---|---|
| P0 | [x] | Travar responsabilidades entre `api-shlink.vr766.com`, `me.vr766.com` e o host publico de slugs |
| P0 | [x] | Garantir que Laravel, MariaDB e variaveis de ambiente sobem sem erro |
| P0 | [x] | Garantir que `.env`, logs e arquivos gerados nao sejam versionados |
| P0 | [x] | Fechar autenticacao e base do painel |
| P0 | [x] | Bloquear painel fora do `PANEL_HOST` e configurar `trustProxies` |
| P0 | [x] | Cobrir cota free com testes automatizados |

## Prioridade P1

Itens essenciais para o produto funcionar de ponta a ponta.

| Pri | Status | Item |
|---|---|---|
| P1 | [x] | Ligar a integracao com Shlink com `X-Api-Key` e `Accept: application/json` |
| P1 | [x] | Criar links free com slug aleatorio e expiracao de 7 dias |
| P1 | [x] | Criar links premium com `customSlug` |
| P1 | [x] | Registrar dominio proprio no Shlink depois da validacao |
| P1 | [x] | Consultar visitas e analytics no painel |
| P1 | [x] | Entregar dashboard, lista de links e tela de criacao |
| P1 | [x] | Entregar telas de dominios e metricas |
| P1 | [x] | Definir proxy reverso e TLS automatico para dominios de clientes |

## Prioridade P2

| Pri | Status | Item |
|---|---|---|
| P2 | [x] | Billing Stripe: checkout, portal, webhook, gate premium |
| P2 | [x] | Reset mensal automatico de cota free |
| P2 | [x] | Observabilidade: logs estruturados, /healthz, /health/ready, request_id |
| P2 | [x] | Deploy real em `me.vr766.com` via aaPanel/Nginx (vhost + PHP-FPM + upstream Shlink) |
