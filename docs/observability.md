# Observabilidade

## Endpoints

| Rota | Uso | Autenticacao | Resposta |
|---|---|---|---|
| `GET /healthz` | Liveness (Docker/Caddy/uptime) | Publico | 200 sempre que o processo PHP responde |
| `GET /health/ready` | Readiness (DB + motor de links) | Publico | 200 quando tudo OK; 503 quando qualquer dependencia falha |
| `GET /health/release` | Identidade do artefato publicado | Publico | JSON mínimo com `release` e `built_at` |
| `GET /api/v1/openapi.json` | Contrato público da API v1 | Publico | OpenAPI 3.1 sem credenciais |
| `GET /up` | Health padrao do Laravel 11 | Publico | Curto-circuito antes do bootstrap do app |

Ambos `/healthz` e `/health/ready` sao registrados **fora** do `Route::domain(PANEL_HOST)`
para que o balanceador e o uptime externo possam bater neles pelo IP do servico
sem depender do host publico do painel.

## Logs estruturados

Todos os requests passam por `App\Http\Middleware\AttachRequestContext`, que:

- Aceita `X-Request-Id` somente quando ele usa caracteres seguros e até 128 posições; caso contrário, gera um UUID novo.
- Injeta `request_id`, `user_id`, `ip`, `method`, `path` no contexto do log via `Log::withContext(...)`.
- Devolve `X-Request-Id` no header da resposta para correlacao ponta a ponta.
- Acrescenta `status` e `duration_ms` ao contexto depois do processamento, permitindo investigar latência e erros sem capturar corpo ou credenciais.

Em producao, defina no `.env`:

```env
LOG_CHANNEL=stderr
LOG_STDERR_FORMATTER=Monolog\Formatter\JsonFormatter
```

Assim cada linha de log vira JSON com o contexto anexado, pronto para ser
agregado por `docker logs`, Loki, CloudWatch, etc.

## Convencao de eventos

Logs de negocio usam mensagens em snake case com prefixo do dominio, para
facilitar busca:

- `panel.health.database_fail`
- `panel.health.shlink_fail`
- `panel.tls.refresh_ok`
- `panel.quota.reset_ok`
- `panel.billing.webhook_received`

## Monitoramento sintético

O workflow `.github/workflows/synthetic.yml` executa a cada cinco minutos o script `deploy/scripts/public-synthetic-check.sh`. O probe valida home, liveness, readiness, manifesto de release e OpenAPI; exige HTTP 200, ausência de `Set-Cookie` nos endpoints operacionais, presença de `X-Request-Id`, formato de release e ausência de padrões de segredo no contrato.

A execução também pode ser disparada manualmente pelo GitHub Actions. O probe não usa conta de cliente, token, pagamento ou escrita de dados.

## Proximos passos

- Integrar Sentry (P2 seguinte) usando o mesmo `request_id` para stacktraces.
- Publicar métrica básica (`links_created_total`, `links_free_quota_exceeded_total`) via `/metrics` para Prometheus.
- Criar alertas com limiares de P95, 5xx, fila, TLS e falha consecutiva do probe.
