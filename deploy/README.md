# Deploy de produção

Stack containerizada com **Caddy + Painel Laravel + Shlink + MariaDB**.

## Componentes

| Serviço | Papel | Exposto |
|---|---|---|
| `caddy` | Proxy reverso, TLS On-Demand (Let's Encrypt) | 80/443 público |
| `panel` | Painel administrativo Laravel | Interno (porta 8000) |
| `shlink` | Motor de encurtamento | Interno (porta 8080) |
| `db` | MariaDB 11 | Interno |

Somente o Caddy escuta na Internet. Painel e Shlink ficam em rede interna do compose.

## Pré-requisitos

- VPS Linux com Docker 24+ e Docker Compose plugin
- Portas 80 e 443 livres e alcançáveis pela Internet (necessário para Let's Encrypt)
- DNS já apontando `PANEL_HOST` e `SHLINK_DEFAULT_DOMAIN` para o IP do servidor

## Passo a passo

```bash
# 1. Copiar variáveis
cp deploy/.env.example deploy/.env
# editar deploy/.env com segredos reais

# 2. Gerar APP_KEY do Laravel
docker compose -f deploy/compose.prod.yml --env-file deploy/.env \
    run --rm panel php artisan key:generate --show
# copiar o valor para APP_KEY em deploy/.env

# 3. Subir a stack
docker compose -f deploy/compose.prod.yml --env-file deploy/.env up -d

# 4. Acompanhar
docker compose -f deploy/compose.prod.yml logs -f caddy panel
```

Na primeira subida o Caddy emite o certificado do `PANEL_HOST` e do
`SHLINK_DEFAULT_DOMAIN` automaticamente. Certificados de clientes só saem
depois que o cliente:

1. Registra o domínio no painel (`/domains`)
2. Aponta o DNS conforme instrução exibida
3. Clica em **Verificar DNS**
4. Acessa `https://<dominio-do-cliente>` — o Caddy consulta
   `GET /api/tls/allow?domain=...` no painel; se autorizado, emite o cert
   e serve o slug via Shlink.

## Rollback

```bash
docker compose -f deploy/compose.prod.yml --env-file deploy/.env down
# restaurar dump do banco (se necessário)
docker compose -f deploy/compose.prod.yml --env-file deploy/.env up -d
```

## Backup mínimo (banco)

```bash
docker compose -f deploy/compose.prod.yml exec db \
    mariadb-dump -uroot -p"$DB_ROOT_PASSWORD" "$DB_NAME" > backup-$(date +%F).sql
```

Recomendado agendar via cron/systemd-timer no host.

## Rotação de certificados

Caddy renova automaticamente ~30 dias antes do vencimento. Volume
`caddy_data` guarda contas ACME e certs — **não deletar** entre deploys.

## Observabilidade rápida

- Logs Caddy: `docker compose logs caddy`
- Logs painel: `docker compose logs panel`
- Sonda TLS: o comando agendado `panel:tls:refresh` roda a cada 15 min via
  scheduler do Laravel (habilite com `docker compose exec panel php artisan schedule:work` em um sidecar, ou instale um cron no host apontando para `php artisan schedule:run`).

## Endpoint público consultado pelo Caddy

`GET /api/tls/allow?domain=<host>` — responde `200 {allowed:true}` se o
domínio está registrado (`pending_dns` ou `active`) e `404` caso contrário.
Fica intencionalmente fora do domain guard do painel para responder em
qualquer host chamado pelo Caddy dentro da rede interna.
