# Observabilidade

## Endpoints

| Rota | Uso | Autenticacao | Resposta |
|---|---|---|---|
| `GET /healthz` | Liveness (Docker/Caddy/uptime) | Publico | 200 sempre que o processo PHP responde |
| `GET /health/ready` | Readiness (DB + motor de links) | Publico | 200 quando tudo OK; 503 quando qualquer dependencia falha |
| `GET /up` | Health padrao do Laravel 11 | Publico | Curto-circuito antes do bootstrap do app |

Ambos `/healthz` e `/health/ready` sao registrados **fora** do `Route::domain(PANEL_HOST)`
para que o balanceador e o uptime externo possam bater neles pelo IP do servico
sem depender do host publico do painel.

## Logs estruturados

Todos os requests passam por `App\Http\Middleware\AttachRequestContext`, que:

- Le `X-Request-Id` do proxy reverso ou gera um UUID novo.
- Injeta `request_id`, `user_id`, `ip`, `method`, `path` no contexto do log via `Log::withContext(...)`.
- Devolve `X-Request-Id` no header da resposta para correlacao ponta a ponta.

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

## Proximos passos

- Integrar Sentry (P2 seguinte) usando o mesmo `request_id` para stacktraces.
- Publicar metrica basica (`links_created_total`, `links_free_quota_exceeded_total`)
  via `/metrics` para Prometheus.
