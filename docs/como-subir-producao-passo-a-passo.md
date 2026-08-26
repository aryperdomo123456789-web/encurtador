# Como subir em produção passo a passo

Este guia explica exatamente como preencher os segredos de produção e subir o
painel sem depender de adivinhação.

Se você quiser ver um exemplo de primeiro preenchimento feito de ponta a ponta,
consulte também:

- [`docs/primeiro-preenchimento-laboratorio.md`](/www/wwwroot/api-shlink.vr766.com/docs/primeiro-preenchimento-laboratorio.md)

## Quando usar este guia

Use este documento quando você já tiver:

- o servidor pronto;
- o host `me.vr766.com` apontando para o servidor;
- o motor de links disponível;
- as chaves da Stripe, MaxMind e do motor em mãos;
- acesso ao terminal do servidor.

## Regra principal

Para produção com Docker Compose, o arquivo que manda é:

- [`deploy/.env`](/www/wwwroot/api-shlink.vr766.com/deploy/.env)

O arquivo:

- [`panel/laravel/.env.example`](/www/wwwroot/api-shlink.vr766.com/panel/laravel/.env.example)

serve como referência para desenvolvimento local. Em produção, o compose usa o
`deploy/.env`.

---

## 1. Criar o arquivo de produção

Dentro da raiz do projeto:

```bash
cp deploy/.env.example deploy/.env
```

Depois abra o arquivo `deploy/.env` e preencha os valores.

---

## 2. Gerar `APP_KEY`

A `APP_KEY` é obrigatória para o Laravel funcionar.

Rode:

```bash
cd /www/wwwroot/api-shlink.vr766.com/panel/laravel
php artisan key:generate --show
```

Copie a saída, que começa com `base64:`, e cole em:

```env
APP_KEY=base64:...
```

---

## 3. Preencher os segredos do banco

No `deploy/.env`, preencha:

```env
DB_NAME=shlink_panel
DB_USER=shlink_panel
DB_PASSWORD=uma_senha_forte
DB_ROOT_PASSWORD=outra_senha_forte
```

Se você já criou o banco no servidor, use os valores reais dele.

Esses campos são usados pelo container MariaDB do painel.

---

## 4. Preencher as chaves do motor

No mesmo `deploy/.env`, preencha:

```env
INITIAL_API_KEY=chave_do_shlink
SHLINK_API_KEY=chave_do_shlink
SHLINK_BASE_URL=http://shlink:8080
SHLINK_DEFAULT_DOMAIN=me.vr766.com
```

### Importante

- No `deploy/compose.prod.yml`, o painel recebe `SHLINK_API_KEY` a partir de
  `INITIAL_API_KEY`.
- Então, para produção com Compose, o campo essencial é `INITIAL_API_KEY`.
- `SHLINK_API_KEY` também pode ficar preenchido no `deploy/.env` por clareza,
  mas o compose já faz o repasse.

---

## 5. Preencher a Stripe

Se billing estiver ativo, preencha:

```env
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_PREMIUM_PRICE_ID=price_...
```

Esses valores vêm do painel da Stripe.

Se você ainda não vai usar billing, o painel pode continuar funcionando com
essa parte incompleta, mas a tela de assinatura não vai operar corretamente.

---

## 6. Preencher o MaxMind

Se você quiser geolocalização de visitas, preencha:

```env
GEOLITE_LICENSE_KEY=...
```

Essa chave vem da conta MaxMind.

Se ela ficar vazia, o painel continua funcionando, mas sem essa camada de
geolocalização.

---

## 7. Conferir variáveis do painel

Revise também:

```env
APP_URL=https://me.vr766.com
PANEL_HOST=me.vr766.com
PANEL_CUSTOM_DOMAIN_DNS_TARGET=me.vr766.com
TRUSTED_PROXIES=*
LOG_CHANNEL=stderr
LOG_STDERR_FORMATTER=Monolog\Formatter\JsonFormatter
```

Esses valores controlam:

- host do painel;
- validação de host;
- alvo DNS dos domínios próprios;
- logs em produção.

---

## 8. Subir a stack

Na raiz do projeto, suba o compose de produção:

```bash
docker compose --env-file deploy/.env -f deploy/compose.prod.yml up -d --build
```

Se o seu servidor já usa o compose com outra forma de carregar variáveis, o
arquivo continua sendo o mesmo: `deploy/.env`.

---

## 9. O que acontece no primeiro boot

O `panel-entrypoint.sh` faz automaticamente:

- `composer install --no-dev`;
- limpa caches do Laravel;
- roda migrations;
- cria `storage` e `bootstrap/cache`;
- liga o `storage:link`;
- inicia o servidor PHP do painel.

Referência:

- [`deploy/panel-entrypoint.sh`](/www/wwwroot/api-shlink.vr766.com/deploy/panel-entrypoint.sh)

---

## 10. Criar ou confirmar a conta do dono

O seed não cria mais uma conta privilegiada com senha fixa. Para ambiente local
ou de teste, defina `SEED_OWNER_EMAIL` e `SEED_OWNER_PASSWORD` antes de
executar:

```bash
cd /www/wwwroot/api-shlink.vr766.com/panel/laravel
php artisan db:seed
```

Em produção, crie a conta do dono por um procedimento seguro fora do Git.

---

## 11. Ajustar a borda do Nginx

O host `me.vr766.com` precisa apontar para o painel e para o fallback de slug
conforme o vhost exemplo:

- [`deploy/nginx/me.vr766.com.conf.example`](/www/wwwroot/api-shlink.vr766.com/deploy/nginx/me.vr766.com.conf.example)

Arquivo real no servidor:

- `/www/server/panel/vhost/nginx/me.vr766.com.conf`

Depois de alterar, teste e recarregue:

```bash
nginx -t
nginx -s reload
```

---

## 12. Configurar o cron do Laravel

O scheduler precisa rodar uma vez por minuto.

Adicione no crontab do usuário que administra o host:

```bash
* * * * * cd /www/wwwroot/api-shlink.vr766.com/panel/laravel && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Esse cron executa:

- `panel:tls:refresh`
- `panel:quota:reset`

---

## 13. Validar o deploy

Depois de subir, valide nesta ordem:

```bash
curl -I https://me.vr766.com/login
curl -I https://me.vr766.com/admin/users
curl -I https://me.vr766.com/abc123
curl -I https://api-shlink.vr766.com/rest/health
```

### O que esperar

- `/login` deve responder o painel;
- `/admin/users` deve redirecionar para login se você não estiver autenticado;
- `/abc123` deve cair no fallback e chegar ao motor;
- `/rest/health` deve responder 200.

---

## 14. Backup e logs

Scripts já disponíveis:

- backup: [`deploy/scripts/backup-db.sh`](/www/wwwroot/api-shlink.vr766.com/deploy/scripts/backup-db.sh)
- restore: [`deploy/scripts/restore-db.sh`](/www/wwwroot/api-shlink.vr766.com/deploy/scripts/restore-db.sh)
- logrotate: [`deploy/logrotate/shlink-panel.conf.example`](/www/wwwroot/api-shlink.vr766.com/deploy/logrotate/shlink-panel.conf.example)

### O que ainda precisa ser feito no host

- agendar o backup;
- aplicar o logrotate do exemplo;
- confirmar retenção dos arquivos antigos.

---

## 15. O que ainda falta depois disso

Se você preencher tudo acima, o que sobra é operação normal:

- manter os segredos guardados fora do git;
- rodar backup com regularidade;
- observar logs e health;
- renovar Stripe e MaxMind quando necessário.

Ou seja: **não falta mais nada de código crítico** para colocar o sistema de pé.
O que falta é configurar o ambiente com os valores reais e garantir que o
host esteja rodando o cron, o Nginx e o backup.
