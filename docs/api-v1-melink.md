# API v1 do MElink

**Status:** implementada em desenvolvimento; publicar somente após executar migrations e validar a configuração de produção.

## Objetivo

A API v1 permite que clientes, agências e automações criem e operem links sem compartilhar senha de usuário. A autenticação usa `Authorization: Bearer <token>`; o segredo é mostrado uma única vez no momento da emissão e apenas seu hash SHA-256 é persistido.

## Endpoints

| Método | Caminho | Scope | Descrição |
|---|---|---|---|
| `GET` | `/api/v1/me` | `read` | Retorna identidade e entitlements da conta. |
| `GET` | `/api/v1/links` | `read` | Lista links do usuário com paginação e filtro de status. |
| `GET` | `/api/v1/links/{id}` | `read` | Retorna um link pertencente à conta autenticada. |
| `POST` | `/api/v1/links` | `write` | Cria link Free ou Premium conforme entitlements. |
| `DELETE` | `/api/v1/links/{id}` | `write` | Exclui o link no Shlink e no espelho local. |
| `GET` | `/api/v1/links/{id}/analytics` | `analytics` | Consulta visitas do link com filtros de período. |

Todas as respostas são JSON. Os erros usam `error` estável, por exemplo `unauthorized`, `insufficient_scope`, `premium_required`, `analytics_unavailable` e `link_creation_failed`.

## Emissão e revogação

Há duas formas de emitir tokens. O caminho operacional recomendado para administradores é:

```bash
php artisan melink:api-token 42 "Integração CRM" --scope=read,write,analytics --expires=365
```

O painel também oferece `/settings/api-tokens` para usuários autenticados emitirem e revogarem tokens próprios. O segredo não é recuperável depois da criação; se for perdido, o procedimento correto é revogar o token e emitir outro.

O token é formatado como `mlk_live_...`. A tabela mantém `token_prefix`, `token_hash`, scopes, data do último uso, expiração e revogação. O middleware faz lookup por prefixo e comparação constante do hash, evitando varredura completa da tabela em cada request.

## Scopes

`read` permite consultar conta e links. `write` permite criar e excluir links. `analytics` permite consultar métricas de visitas. Um token pode acumular scopes, mas a recomendação comercial é criar o menor conjunto necessário para cada integração.

## Criação de links

Exemplo mínimo:

```bash
curl -X POST https://me.vr766.com/api/v1/links \
  -H 'Authorization: Bearer mlk_live_REDACTED' \
  -H 'Content-Type: application/json' \
  -H 'Idempotency-Key: crm-lead-2026-0001' \
  -d '{"long_url":"https://cliente.exemplo/oferta"}'
```

O payload aceita `custom_slug`, `domain`, `title`, `tags`, `forward_query`, `valid_until` e UTMs. Campos Premium exigem entitlement Premium; a API não deve transformar falha de plano em chamada remota ao Shlink.

## Limites e operação

O rate limit padrão é `PANEL_API_RATE_LIMIT=120` requests por minuto por IP. Em produção, deve ser substituído por limiter distribuído em Redis antes de múltiplas réplicas. Logs não podem registrar o Bearer token nem URLs com dados pessoais; o request ID deve ser usado para correlação.

Os endpoints de escrita devem usar `Idempotency-Key` do cliente. A próxima evolução obrigatória é persistir a chave por usuário, método, rota e payload para garantir replay determinístico após timeout entre a aplicação e o Shlink.

## Critérios de aceite

A API só pode ser considerada pronta para clientes externos quando houver documentação OpenAPI publicada, testes de contrato, rate limit distribuído, idempotência persistida para escrita, rotação/revogação funcionando, alertas de erro e uma política de retenção de logs compatível com LGPD.

## Idempotência de escritas

`POST` e `DELETE` exigem `Idempotency-Key` com até 80 caracteres alfanuméricos ou os símbolos `.`, `_`, `:` e `-`. A chave é vinculada ao usuário, método, rota e hash do payload por 24 horas. Repetir a mesma chave com o mesmo payload reproduz a resposta original; reutilizar a chave com payload diferente retorna `409`; uma operação concorrente retorna `409` enquanto está em andamento.

Essa camada reduz duplicação quando a conexão entre cliente, painel e Shlink cai depois da criação do recurso. Em múltiplas réplicas, a tabela deve permanecer em banco compartilhado e o rate limit deve migrar para Redis.

## Identidade do painel

Em produção, `PANEL_REQUIRE_EMAIL_VERIFICATION=true` direciona contas novas e usuários não verificados para o fluxo de confirmação. Recuperação de senha usa o broker padrão do Laravel e resposta neutra, sem confirmar se o e-mail informado existe. A entrega de e-mail deve usar um provedor transacional configurado, com SPF, DKIM e DMARC do domínio do produto.

## Eventos de conversão

A API v1 oferece `POST /api/v1/events`, protegido pelo scope `events` e pelo middleware de idempotência. O endpoint aceita `event_type`, `event_id`, `short_code`, `workspace_id`, `occurred_at` e `properties`. O `event_id` deve ser estável no sistema do cliente para que retries retornem a mesma entrega sem duplicar a conversão.

Os campos de rede não são persistidos em claro: o IP e o User-Agent são transformados em HMAC com a chave da aplicação. O consumidor deve enviar somente propriedades necessárias para atribuição, evitando dados pessoais, tokens, e-mails ou documentos.

## Atualização de destino

`PATCH /api/v1/links/{id}` exige scope `write` e `Idempotency-Key`. O slug permanece estável; somente o destino longo é enviado ao Shlink e atualizado no espelho local depois de sucesso remoto.

Consulte também `docs/release-plataforma-melink-10-10-v1.md` para o runbook de produção, variáveis obrigatórias e critérios de aceite.
