# Operação do Shlink no SaaS

## Estado atual do ambiente

- `api-shlink.vr766.com`: repositório principal do projeto e base do painel Laravel.
- `app.me.vr766.com`: host ativo do painel, apontando para `panel/laravel/public`.
- `/www/wwwroot/me.vr766.com`: diretório reservado para o futuro segundo site, hoje sem uso no deploy atual.

## Endereços

- `api-shlink.vr766.com`: API do Shlink
- `app.me.vr766.com`: painel administrativo
- `me.vr766.com`: domínio padrão de slugs da plataforma
- `slug-host.a-definir`: domínio público alternativo de links, se você quiser separar ainda mais o tráfego

## aaPanel / Nginx

Para o painel atual, o document root correto nao e `/www/wwwroot/me.vr766.com`.
O root deve apontar para o `public` do Laravel dentro do repo:

```nginx
server {
    listen 80;
    listen 443 ssl;
    server_name me.vr766.com;
    root /www/wwwroot/api-shlink.vr766.com/panel/laravel/public;
    index index.php index.html index.htm;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include enable-php-83.conf;
    }
}
```

### Paths que precisam de escrita

- `/www/wwwroot/api-shlink.vr766.com/panel/laravel/storage`
- `/www/wwwroot/api-shlink.vr766.com/panel/laravel/bootstrap/cache`
- `/www/wwwroot/api-shlink.vr766.com/panel/laravel/database/database.sqlite`

### O que precisa existir para nao dar 404 ou 500

- `root` apontando para `panel/laravel/public`;
- `try_files` redirecionando para `index.php`;
- permissao de escrita em `storage` e `bootstrap/cache`;
- banco SQLite ou MariaDB com permissão correta;
- migrations aplicadas;
- usuario inicial criado.

### O que nao precisa ser copiado para `/www/wwwroot/me.vr766.com`

- o repositorio inteiro;
- `vendor/`;
- `storage/` completo;
- `bootstrap/cache/`;
- `.env` real.

Se no futuro o host `me.vr766.com` virar um projeto separado, ai sim o docroot passa a ser outro, normalmente `.../public` do novo app.

## Variáveis de ambiente principais

- `SHLINK_DEFAULT_DOMAIN`
- `INITIAL_API_KEY`
- `DB_NAME`
- `DB_USER`
- `DB_PASSWORD`
- `DB_ROOT_PASSWORD`
- `GEOLITE_LICENSE_KEY`

## Regras do plano free

- máximo de 5 links por mês por usuário;
- `validUntil` obrigatório com +7 dias;
- slug gerado aleatoriamente;
- sem custom slug;
- sem domínio próprio.

## Regras do premium

- links ilimitados;
- slug customizado permitido;
- domínio próprio permitido;
- expiração opcional;
- link vitalício permitido.

## O que o Shlink faz sozinho

- redireciona no menor tempo possível;
- valida domínio e slug;
- guarda visitas;
- expõe relatórios via API.

## Roteamento de produção

No deploy com `compose.prod.yml`, a borda faz a divisão por host:

- `app.me.vr766.com` vai para o painel Laravel;
- `me.vr766.com/{slug}` e qualquer `dominio-do-cliente.tld/{slug}` vão direto para o Shlink;
- o Caddy consulta `/tls/allow` no painel antes de emitir certificados on-demand para domínios de cliente.

## O que o painel faz

- autentica usuários;
- controla assinatura;
- controla cota mensal free;
- cadastra domínio do cliente;
- chama a API do Shlink;
- consulta visitas e monta gráficos.

## Sequência sugerida para domínio próprio

1. cliente cadastra o domínio no painel;
2. painel valida DNS CNAME;
3. painel chama `POST /domains` no Shlink;
4. painel grava o domínio em `customer_domains`;
5. o proxy emite certificado automaticamente;
6. o cliente começa a criar links nesse domínio.

## Risco de negócio que precisa ser decidido

Se você realmente quiser o painel no mesmo host que os slugs, a aplicação administrativa precisa viver fora das rotas de shortcode.

Exemplo seguro:

- painel: `/admin`
- slugs: `/{slug}`

Exemplo mais seguro ainda:

- painel: `me.vr766.com`
- slugs: `slug-host.a-definir/{slug}`

## Próximo passo operacional

- subir a stack do Shlink;
- aplicar o schema MariaDB;
- conectar o painel ao client PHP;
- escolher o proxy reverso com ACME automático.

## Checklist rapido de deploy

1. Confirmar que `me.vr766.com` aponta para o `public/` do Laravel.
2. Confirmar permissao de escrita nas pastas do runtime.
3. Rodar as migrations.
4. Criar o usuario inicial do painel.
5. Validar `/login` no navegador.
6. Validar `php artisan test`.
7. Somente depois disso promover para produção.
