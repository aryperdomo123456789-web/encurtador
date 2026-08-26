# Guia completo do projeto MElink SaaS

Este documento consolida o estado atual do código, a arquitetura do produto e
os passos de produção. A ideia é servir como referência única para operação,
manutenção e evolução do painel.

## 1. Visão do produto

O sistema é um SaaS de encurtamento baseado na marca MElink, com três camadas:

1. `api-shlink.vr766.com`
   - motor de links;
   - expõe a API REST;
   - responde pelos redirecionamentos finais.
2. `me.vr766.com`
   - painel Laravel do SaaS;
   - login, cadastro, links, domínios, billing, analytics e admin do dono;
   - também atende slugs públicos via fallback da aplicação.
3. Banco da camada SaaS
   - guarda usuários, planos, assinaturas, domínios, links espelho e cota mensal.

## 2. Estado atual do código

O painel já entrega:

- autenticação com login, logout e cadastro;
- landing pública;
- dashboard do usuário;
- lista e criação de links free e premium;
- gestão de domínios próprios;
- billing Stripe;
- analytics de visitas;
- health checks;
- admin do dono;
- fallback público para slugs quando a borda encaminha a requisição ao Laravel.

### Arquivos principais

- Rotas: [`panel/laravel/routes/web.php`](/www/wwwroot/api-shlink.vr766.com/panel/laravel/routes/web.php)
- Model de usuário: [`panel/laravel/app/Models/User.php`](/www/wwwroot/api-shlink.vr766.com/panel/laravel/app/Models/User.php)
- Dashboard: [`panel/laravel/app/Http/Controllers/DashboardController.php`](/www/wwwroot/api-shlink.vr766.com/panel/laravel/app/Http/Controllers/DashboardController.php)
- Links: [`panel/laravel/app/Http/Controllers/LinkController.php`](/www/wwwroot/api-shlink.vr766.com/panel/laravel/app/Http/Controllers/LinkController.php)
- Domínios: [`panel/laravel/app/Http/Controllers/DomainController.php`](/www/wwwroot/api-shlink.vr766.com/panel/laravel/app/Http/Controllers/DomainController.php)
- Billing: [`panel/laravel/app/Http/Controllers/BillingController.php`](/www/wwwroot/api-shlink.vr766.com/panel/laravel/app/Http/Controllers/BillingController.php)
- Admin do dono: [`panel/laravel/app/Http/Controllers/Admin/UserAdminController.php`](/www/wwwroot/api-shlink.vr766.com/panel/laravel/app/Http/Controllers/Admin/UserAdminController.php)
- Fallback público: [`panel/laravel/app/Http/Controllers/PublicRedirectController.php`](/www/wwwroot/api-shlink.vr766.com/panel/laravel/app/Http/Controllers/PublicRedirectController.php)
- Observabilidade: [`panel/laravel/app/Http/Middleware/AttachRequestContext.php`](/www/wwwroot/api-shlink.vr766.com/panel/laravel/app/Http/Middleware/AttachRequestContext.php)

## 3. Banco de dados

### Ambiente local

- `sqlite`
- arquivo: [`panel/laravel/database/database.sqlite`](/www/wwwroot/api-shlink.vr766.com/panel/laravel/database/database.sqlite)

### Produção

- MariaDB 11.4 via [`deploy/compose.prod.yml`](/www/wwwroot/api-shlink.vr766.com/deploy/compose.prod.yml)
- banco do painel: `panel-db`
- variáveis obrigatórias:
  - `DB_NAME`
  - `DB_USER`
  - `DB_PASSWORD`
  - `DB_ROOT_PASSWORD`

### Tabelas relevantes

- `users`
- `plans`
- `subscriptions`
- `short_links`
- `customer_domains`
- `monthly_quota_usage`
- `link_event_log`

### Papel do usuário

- `role = owner`: conta do dono, com acesso a tudo.
- `role = user`: conta comum.

O dono pode:

- acessar premium sem assinatura;
- registrar domínios sem plano;
- usar o admin `/admin`;
- gerenciar contas comuns;
- resetar senha de usuários comuns.

## 4. Fluxos de negócio

### Cadastro e login

1. visitante acessa `/register`;
2. cria conta comum;
3. é autenticado automaticamente;
4. cai no dashboard.

### Fluxo free

- slug aleatório;
- validade de 7 dias;
- limite mensal de 5 links;
- sem custom slug;
- sem domínio próprio.

### Fluxo premium

- custom slug;
- expiração opcional;
- domínio próprio;
- link vitalício quando o plano permite.

### Fluxo do dono

- não depende de Stripe para operar;
- passa pelos gates premium e domínio;
- tem admin próprio em `/admin`.

## 5. Rotas do painel

### Rotas públicas

- `/`
- `/login`
- `/register`
- `/healthz`
- `/health/ready`
- `/tls/allow`

### Rotas autenticadas

- `/dashboard`
- `/links`
- `/links/create`
- `/links/premium`
- `/domains`
- `/billing`
- `/analytics/{shortCode}`
- `/admin`
- `/admin/users`
- `/admin/users/{user}`

### Fallback público

Quando a borda entrega um slug ao Laravel, o [`PublicRedirectController`](/www/wwwroot/api-shlink.vr766.com/panel/laravel/app/Http/Controllers/PublicRedirectController.php) repassa a requisição ao motor e devolve a resposta original.

## 6. Admin do dono

O admin foi desenhado para operação leve e segura.

### O que ele faz

- lista usuários;
- mostra resumo de contas, links, domínios e assinaturas;
- abre o detalhe de cada usuário;
- gera senha temporária para contas comuns.

### O que ele não faz

- não altera a conta do dono;
- não expõe ações destrutivas desnecessárias;
- não mexe em fluxos de cliente que já funcionam.

### Conta do dono

O seed não contém mais credenciais fixas. Em ambiente local ou de teste, defina
`SEED_OWNER_EMAIL` e `SEED_OWNER_PASSWORD` antes de executar o seed. Em
produção, crie a conta do dono por um procedimento seguro fora do repositório.

## 7. Integração com o motor

### Cliente HTTP

O cliente está em:

- [`panel/laravel/app/Support/Shlink/ShlinkClient.php`](/www/wwwroot/api-shlink.vr766.com/panel/laravel/app/Support/Shlink/ShlinkClient.php)

Ele centraliza:

- `X-Api-Key`;
- `Accept: application/json`;
- chamadas para `short-urls`, `domains` e `visits`.

### Provisionamento

O [`LinkProvisioner`](/www/wwwroot/api-shlink.vr766.com/panel/laravel/app/Support/Shlink/LinkProvisioner.php) aplica:

- cota free;
- `validUntil` de 7 dias no fluxo free;
- bloqueio de `customSlug` no fluxo free;
- `customSlug` no fluxo premium.

## 8. Observabilidade

### Endpoints

- `/healthz`
- `/health/ready`
- `/up`

### Request ID

O middleware [`AttachRequestContext`](/www/wwwroot/api-shlink.vr766.com/panel/laravel/app/Http/Middleware/AttachRequestContext.php) injeta:

- `request_id`
- `user_id`
- `ip`
- `method`
- `path`

Ele também devolve `X-Request-Id` na resposta.

### Logs

Em produção, o ideal é:

```env
LOG_CHANNEL=stderr
LOG_STDERR_FORMATTER=Monolog\Formatter\JsonFormatter
```

## 9. Scheduler e jobs

Os jobs já existem no código:

- [`panel:tls:refresh`](/www/wwwroot/api-shlink.vr766.com/panel/laravel/app/Console/Commands/RefreshDomainTlsStatus.php)
- [`panel:quota:reset`](/www/wwwroot/api-shlink.vr766.com/panel/laravel/app/Console/Commands/ResetMonthlyQuota.php)

O agendamento está em [`panel/laravel/routes/console.php`](/www/wwwroot/api-shlink.vr766.com/panel/laravel/routes/console.php).

### O que ainda precisa existir no servidor

O host precisa de um cron real para executar:

```bash
* * * * * cd /www/wwwroot/api-shlink.vr766.com/panel/laravel && php artisan schedule:run >> /dev/null 2>&1
```

Sem isso, os jobs ficam definidos no código, mas não rodam automaticamente.

## 10. Deploy

### Template de ambiente

O template está em:

- [`deploy/.env.example`](/www/wwwroot/api-shlink.vr766.com/deploy/.env.example)

### Compose de produção

O stack está em:

- [`deploy/compose.prod.yml`](/www/wwwroot/api-shlink.vr766.com/deploy/compose.prod.yml)

### Entrypoint

O boot do container do painel está em:

- [`deploy/panel-entrypoint.sh`](/www/wwwroot/api-shlink.vr766.com/deploy/panel-entrypoint.sh)

### Vhost

Exemplo de Nginx/aaPanel:

- [`deploy/nginx/me.vr766.com.conf.example`](/www/wwwroot/api-shlink.vr766.com/deploy/nginx/me.vr766.com.conf.example)

### Vhost real aplicado

Arquivo ativo no host:

- [`/www/server/panel/vhost/nginx/me.vr766.com.conf`](/www/server/panel/vhost/nginx/me.vr766.com.conf)

## 11. Segredos e variáveis

### Obrigatórios

- `APP_KEY`
- `DB_NAME`
- `DB_USER`
- `DB_PASSWORD`
- `DB_ROOT_PASSWORD`
- `INITIAL_API_KEY`
- `SHLINK_API_KEY`
- `STRIPE_KEY`
- `STRIPE_SECRET`
- `STRIPE_WEBHOOK_SECRET`
- `STRIPE_PREMIUM_PRICE_ID`
- `GEOLITE_LICENSE_KEY`

### Configuração de painel

- `PANEL_HOST`
- `PANEL_CUSTOM_DOMAIN_DNS_TARGET`

### Configuração do motor

- `SHLINK_BASE_URL`
- `SHLINK_DEFAULT_DOMAIN`
- `FREE_MONTHLY_LINK_LIMIT`

## 12. Backup e restore

Existem scripts de apoio no repositório para backup e restore da camada SaaS.

### Backup mínimo recomendado

Use o script:

- [`deploy/scripts/backup-db.sh`](/www/wwwroot/api-shlink.vr766.com/deploy/scripts/backup-db.sh)

### Restore mínimo recomendado

Use o script:

- [`deploy/scripts/restore-db.sh`](/www/wwwroot/api-shlink.vr766.com/deploy/scripts/restore-db.sh)

### O que precisa ser guardado

- banco MariaDB do painel;
- `.env` de produção fora do git;
- arquivos de vhost do aaPanel;
- certificados do host;
- histórico de logs relevante.

## 13. Rotação de logs

O código já emite contexto estruturado, e o template de produção passou a
priorizar `stderr` JSON. Para a borda, existe um exemplo de `logrotate` no
repositório.

### Recomendação

- `LOG_CHANNEL=stderr`;
- `LOG_STDERR_FORMATTER=Monolog\\Formatter\\JsonFormatter`;
- `deploy/logrotate/shlink-panel.conf.example` para logs da borda e dos arquivos
  locais restantes;
- retenção de 7 a 30 dias, conforme volume.

## 14. Checklist de produção segura

1. Aplicar migrations.
2. Criar ou validar a conta do dono.
3. Preencher `.env` de produção.
4. Rodar `php artisan optimize:clear`.
5. Garantir `php artisan schedule:run` via cron.
6. Validar `https://me.vr766.com/login`.
7. Validar `https://me.vr766.com/admin/users`.
8. Validar `https://me.vr766.com/abc123`.
9. Validar `https://api-shlink.vr766.com/rest/health`.
10. Confirmar backup e retenção de logs.

## 15. Gaps que ainda precisam de atenção

- backup e restore já existem como scripts;
- retenção/rotação de logs precisa ser aplicada no servidor usando o exemplo do
  repositório;
- os segredos de produção ainda precisam ser preenchidos fora do git;
- o cron do Laravel precisa ser habilitado no host;
- o deploy real precisa ser sempre redeployado após mudanças de rota ou cache.

## 16. Comandos de validação

```bash
php artisan test --testsuite=Feature
php artisan route:list --name=admin
php artisan route:list --name=public.redirect
php artisan optimize:clear
php artisan migrate
```

## 17. Nota de projeto

Com o estado atual do código, a base funcional está bem avançada, mas a
produção só fica segura quando:

- o vhost estiver alinhado;
- o cron estiver rodando;
- os segredos estiverem preenchidos;
- backup e logs estiverem operacionais.
