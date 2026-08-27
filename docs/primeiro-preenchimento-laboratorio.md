# Primeiro preenchimento do laboratório

Este documento registra exatamente os valores usados no primeiro `deploy/.env`
de laboratório e mostra como trocar cada segredo depois, sem adivinhar nada.

## O que foi preenchido agora

Arquivo principal do laboratório:

- [`deploy/.env`](/www/wwwroot/api-shlink.vr766.com/deploy/.env)

Arquivo de referência para uso local fora do Compose:

- [`panel/laravel/.env.example`](/www/wwwroot/api-shlink.vr766.com/panel/laravel/.env.example)

## Valores usados no laboratório

### `deploy/.env`

```env
APP_URL=https://me.vr766.com
PANEL_HOST=me.vr766.com
PANEL_CUSTOM_DOMAIN_DNS_TARGET=me.vr766.com
APP_KEY=<generate-with-php-artisan-key-generate>

DB_NAME=<local-database-name>
DB_USER=<local-database-user>
DB_PASSWORD=<local-database-password>
DB_ROOT_PASSWORD=<local-database-root-password>

SHLINK_BASE_URL=http://shlink:8080
SHLINK_API_KEY=<local-shlink-api-key>
SHLINK_DEFAULT_DOMAIN=me.vr766.com

INITIAL_API_KEY=<local-initial-api-key>
GEOLITE_LICENSE_KEY=<local-geolite-license-key>
TRUSTED_PROXIES=127.0.0.1,::1
LOG_CHANNEL=stderr
LOG_STACK=single
LOG_STDERR_FORMATTER=Monolog\Formatter\JsonFormatter

STRIPE_KEY=<stripe-test-public-key>
STRIPE_SECRET=<stripe-test-secret-key>
STRIPE_WEBHOOK_SECRET=<stripe-test-webhook-secret>
STRIPE_PREMIUM_PRICE_ID=<stripe-test-price-id>
```

### `panel/laravel/.env.example`

O arquivo de exemplo local agora tem os mesmos campos de Stripe para você não
esquecer nenhum deles quando rodar o Laravel fora do Compose:

```env
GEOLITE_LICENSE_KEY=
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
STRIPE_PREMIUM_PRICE_ID=
```

## O que cada valor faz

- `APP_URL`: URL pública do painel.
- `PANEL_HOST`: host permitido para rotas do painel.
- `PANEL_CUSTOM_DOMAIN_DNS_TARGET`: alvo de DNS para domínio próprio.
- `APP_KEY`: chave criptográfica do Laravel.
- `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_ROOT_PASSWORD`: banco MariaDB do laboratório.
- `SHLINK_BASE_URL`: endpoint interno do motor no Compose.
- `SHLINK_API_KEY`: chave que o painel usa para falar com o motor.
- `SHLINK_DEFAULT_DOMAIN`: domínio padrão dos links encurtados.
- `INITIAL_API_KEY`: chave inicial que o painel entrega ao motor.
- `GEOLITE_LICENSE_KEY`: licença MaxMind para geolocalização.
- `TRUSTED_PROXIES`: proxies confiáveis atrás do Nginx.
- `LOG_CHANNEL`, `LOG_STACK`, `LOG_STDERR_FORMATTER`: logs de produção.
- `STRIPE_KEY`: chave pública da Stripe.
- `STRIPE_SECRET`: chave secreta da Stripe.
- `STRIPE_WEBHOOK_SECRET`: segredo do webhook da Stripe.
- `STRIPE_PREMIUM_PRICE_ID`: price id do plano premium.

## Valores ativos neste laboratório

Use esta lista como referência rápida quando for trocar tudo por valores reais:

- `APP_KEY`, credenciais MariaDB e chaves Shlink: gerar somente no ambiente local seguro ou no secret manager.
- `GEOLITE_LICENSE_KEY`: usar apenas se houver licença válida, armazenada fora do Git.
- `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET` e `STRIPE_PREMIUM_PRICE_ID`: copiar do ambiente de teste da Stripe sem registrar valores no repositório.

## O que já está consistente agora

- `APP_KEY` está válida para o Laravel.
- `INITIAL_API_KEY` e `SHLINK_API_KEY` estão alinhadas.
- O banco pode ser mantido sem recriação.
- O painel já pode subir com o `deploy/.env` atual.

## O que ainda é credencial de troca futura

- Stripe continua como valores de laboratório.
- GeoLite continua como valor de laboratório.
- Se você sair do laboratório, esses campos devem ser substituídos por chaves reais.

## Como trocar os dados depois

### Ordem recomendada

1. Troque primeiro `APP_KEY`.
2. Troque depois o banco: `DB_PASSWORD` e `DB_ROOT_PASSWORD`.
3. Troque em seguida o motor: `INITIAL_API_KEY` e `SHLINK_API_KEY`.
4. Troque a Stripe: `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`, `STRIPE_PREMIUM_PRICE_ID`.
5. Troque a MaxMind: `GEOLITE_LICENSE_KEY`.
6. Reinicie a stack.
7. Limpe os caches do Laravel.
8. Valide login, admin e fallback de slugs.

### Passo a passo prático

1. Abra [`deploy/.env`](/www/wwwroot/api-shlink.vr766.com/deploy/.env).
2. Substitua os valores antigos pelos novos.
3. Salve o arquivo.
4. Suba de novo a stack:

```bash
docker compose --env-file deploy/.env -f deploy/compose.prod.yml up -d --build
```

5. Limpe cache do Laravel:

```bash
cd /www/wwwroot/api-shlink.vr766.com/panel/laravel
php artisan optimize:clear
```

6. Teste os pontos principais:

```bash
curl -I https://me.vr766.com/login
curl -I https://me.vr766.com/admin/users
curl -I https://me.vr766.com/qualquer-slug
curl -I https://api-shlink.vr766.com/rest/health
```

## Como pensar em cada troca

- Se o problema é login ou painel quebrado, revise `APP_KEY` primeiro.
- Se o problema é banco, revise as credenciais MariaDB e o container `db`.
- Se o problema é criar links ou ver slugs, revise `SHLINK_API_KEY` e `SHLINK_BASE_URL`.
- Se o problema é cobrança, revise os quatro campos da Stripe.
- Se o problema é geolocalização, revise `GEOLITE_LICENSE_KEY`.

## Regra de ouro

Quando trocar segredo em produção, troque uma família por vez e valide antes de
seguir para a próxima. Assim você sabe exatamente o que quebrou, se quebrar.

## Onde trocar a marca no admin

Depois de entrar como dono, abra:

- `Admin`
- `Marca`

Ali você troca:

- a logo do topo;
- o favicon da aba;
- a imagem usada nas prévias sociais, como WhatsApp e Telegram.

Por padrão, o laboratório já usa a mesma arte base para os três usos. Se você
quiser mudar tudo de uma vez, basta subir a mesma imagem em cada campo.
