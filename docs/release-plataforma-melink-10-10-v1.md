# Release de plataforma MElink — 10/10 v1

**Status:** implementada no ambiente de desenvolvimento e validada localmente. O release ainda exige publicação controlada no GitHub e em produção após revisão do diff e configuração dos serviços externos.

## Objetivo

Esta release transforma o MElink de um encurtador com painel em uma plataforma inicial de links para campanhas, equipes e integrações. O pacote prioriza quatro propriedades que os líderes do mercado tratam como obrigatórias: confiabilidade do redirect, isolamento por conta/workspace, integração segura por API e capacidade de provar resultado por analytics e conversões.

O benchmark utilizado como referência foi Bitly, Rebrandly, Short.io, BL.INK, Dub, Replug, Branch, Linktree, TinyURL e PixelMe. A implementação não copia marca, texto, layout ou ativos proprietários; absorve padrões de produto e os adapta ao contexto brasileiro do MElink.

## Entregas implementadas

| Capacidade | Implementação | Evidência de aceite |
|---|---|---|
| Dependências seguras | Laravel 12.68, Guzzle 7.15.5 e CommonMark 2.10.0 | `composer audit` sem advisories |
| Bootstrap seguro | Trusted proxies configuráveis via `PANEL_TRUSTED_PROXIES`/`TRUSTED_PROXIES`, sem `config()` antecipado | `php artisan --version` e testes passam |
| Proteção pública | Rate limits separados para login, cadastro, health, API e redirect; health sem sessão; headers de segurança globais | Testes de health e rotas |
| Billing | Deliveries Stripe idempotentes, proteção contra evento fora de ordem e bloqueio de checkout duplicado | Testes de assinatura, replay e estado |
| Quota Free | Reservas atômicas com commit/liberação | Testes de concorrência lógica e rollback |
| Domínios | DNS com AAAA e bloqueio de IPs privados/reservados; domínio associado ao workspace | Testes de TLS, ownership e domínio |
| Identidade | Verificação de e-mail configurável e reset de senha anti-enumeração | Testes de cadastro, login e reset |
| API v1 | Bearer token com hash, prefixo indexado, scopes, expiração e revogação | Testes de token e escopo |
| Idempotência API | Replay persistido por `Idempotency-Key` e hash de payload | Testes de replay e conflito |
| Workspaces | Tenant, membros, papéis owner/admin/member/viewer e seleção de contexto | Testes de criação, convite e isolamento |
| Campanhas | Domínio, título, tags, UTMs e encaminhamento de query | Testes Premium e ownership |
| Gestão de links | Atualização do destino no painel e na API via PATCH sem mudar o slug | Teste do contrato PATCH do Shlink |
| Analytics | Dashboard orientado a campanha e exportação CSV filtrada | Testes de dashboard e exportação |
| Conversões | `POST /api/v1/events` com escopo `events`, idempotência e hashes de rede | Migration e contrato API |
| QR | Geração SVG local por ownership | Testes de QR e headers |

## Modelo de dados novo

As migrations `2026_08_26_000005` a `2026_08_26_000010` adicionam os seguintes controles:

1. `stripe_event_deliveries` impede processamento duplicado de webhooks e preserva a marca temporal do evento.
2. `free_link_reservations` separa reserva, commit e liberação da quota Free.
3. `api_tokens` guarda somente hash e prefixo indexado do token.
4. `api_idempotencies` permite replay determinístico e rejeita o reuso de chave com payload diferente.
5. `workspaces` e `workspace_members` criam a base multi-tenant e fazem backfill para usuários e links existentes.
6. `conversion_events` registra conversões sem armazenar IP ou User-Agent em claro.

Toda migration foi executada com sucesso em SQLite efêmero. O rollout em MariaDB deve usar `php artisan migrate --force` depois de backup e validação do espaço disponível.

## API v1

A API usa `Authorization: Bearer <token>` e não reutiliza a sessão web. Os scopes são `read`, `write`, `analytics` e `events`. Tokens são emitidos pelo painel ou por `php artisan melink:api-token`, aparecem uma única vez e devem ser armazenados em secret manager.

### Criar uma conversão

```bash
curl -X POST https://me.vr766.com/api/v1/events \
  -H 'Authorization: Bearer mlk_live_...' \
  -H 'Idempotency-Key: checkout-123' \
  -H 'Content-Type: application/json' \
  -d '{
    "event_type": "purchase",
    "event_id": "order-123",
    "short_code": "campanha-verao",
    "properties": {"value": 199.90, "currency": "BRL"}
  }'
```

A resposta é `201` na primeira criação e `200` no replay idempotente. O endpoint nunca retorna IP ou User-Agent; esses valores, quando disponíveis, são armazenados somente como HMAC com a chave da aplicação.

### Atualizar um destino

```bash
curl -X PATCH https://me.vr766.com/api/v1/links/123 \
  -H 'Authorization: Bearer mlk_live_...' \
  -H 'Idempotency-Key: link-123-update-1' \
  -H 'Content-Type: application/json' \
  -d '{"long_url":"https://cliente.com/nova-oferta"}'
```

## Variáveis obrigatórias de produção

Antes da publicação, conferir as seguintes variáveis sem registrar seus valores em logs:

| Variável | Regra |
|---|---|
| `APP_KEY` | Manter a chave atual; nunca regenerar durante rollout comum |
| `PANEL_HOST` | `me.vr766.com` |
| `SHLINK_REDIRECT_BASE_URL` | URL interna Docker do Shlink, por exemplo `http://shlink:8080` |
| `TRUSTED_PROXIES` | IPs ou CIDRs reais do proxy; nunca `*` em produção |
| `PANEL_REQUIRE_EMAIL_VERIFICATION` | `true` quando mail estiver configurado |
| `MAIL_*` | Configuração real e testada de envio transacional |
| `STRIPE_SECRET` e `STRIPE_WEBHOOK_SECRET` | Segredos reais, com preços/IDs ativos |
| `PANEL_API_TOKEN_EXPIRY_DAYS` | Valor finito e compatível com política de rotação |
| `PANEL_LOGIN_RATE_LIMIT` | Limite por IP adequado ao tráfego esperado |
| `PANEL_REGISTER_RATE_LIMIT` | Limite por IP adequado ao abuso esperado |
| `PANEL_HEALTH_RATE_LIMIT` | Alto o suficiente para monitoramento, baixo o suficiente para evitar amplificação |
| `PANEL_API_RATE_LIMIT` | Definido por capacidade e plano |

## Rollout controlado

O rollout deve seguir a sequência: backup do banco MariaDB, snapshot do checkout e do ambiente, validação do checksum do artefato, aplicação do código, instalação do `vendor` compatível, migration, cache de configuração/rotas/views, recriação somente do painel, health interno, smoke público e observação de logs.

Não executar migração destrutiva, regenerar `APP_KEY`, trocar senha do banco, reiniciar Shlink ou alterar firewall como parte deste release. Em caso de falha, restaurar o checkout e o container anterior; restaurar banco somente se uma migration tiver alterado dados de forma incompatível.

## Gate de qualidade

O gate local desta versão passou com:

- `12 testes` e `373 asserções` na suíte completa, com warnings conhecidos do harness por leituras de arquivos de ambiente;
- migrations completas executadas com sucesso em SQLite;
- Blade views compiladas por `php artisan view:cache`;
- Pint aprovado nos `32` arquivos alterados;
- `composer audit` sem advisories conhecidos;
- rotas API v1 registradas com scopes e idempotência.

## Limitações assumidas

Esta versão ainda não é uma garantia de nível enterprise. Ficam para releases posteriores: PHP-FPM com Nginx em vez de `artisan serve`, filas e scheduler formais, WAF/CDN, 2FA, SSO, exportação de relatórios agendada, conversões agregadas no dashboard, bulk import/export, white-label completo, SDKs, integrações Zapier/Make e alta disponibilidade multi-região.

A régua “10/10” só deve ser declarada após validar esses itens em clientes reais, com métricas de ativação, conversão, retenção, disponibilidade, tempo de resposta e incidentes. Feature sem uso medido é apenas decoração com boleto.
