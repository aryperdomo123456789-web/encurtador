# Operação do Shlink no SaaS

## Estado atual do ambiente

- `api-shlink.vr766.com`: repositório principal do projeto e base do painel Laravel.
- `me.vr766.com`: host ativo do painel e também host dos slugs públicos, com split por caminho na borda.
- `/www/wwwroot/me.vr766.com`: diretório reservado para o futuro segundo site, hoje sem uso no deploy atual.

## Endereços

- `api-shlink.vr766.com`: API do Shlink
- `me.vr766.com`: painel administrativo e host oficial dos slugs públicos
- `slug-host.a-definir`: domínio público alternativo de links, se você quiser separar ainda mais o tráfego no futuro

## aaPanel / Nginx

Para o painel atual, o `me.vr766.com` precisa receber duas classes de rotas:

- rotas administrativas e assets do Laravel vão para o painel em `127.0.0.1:8001`;
- qualquer caminho que nao seja do painel vai para o Shlink em `127.0.0.1:8080`, para permitir `me.vr766.com/{slug}`.

```nginx
server {
    listen 80;
    listen 443 ssl;
    server_name me.vr766.com;
    location ^~ /healthz { proxy_pass http://127.0.0.1:8001; }
    location ^~ /health/ready { proxy_pass http://127.0.0.1:8001; }
    location ^~ /tls/allow { proxy_pass http://127.0.0.1:8001; }
    location ^~ /login { proxy_pass http://127.0.0.1:8001; }
    location ^~ /logout { proxy_pass http://127.0.0.1:8001; }
    location ^~ /links { proxy_pass http://127.0.0.1:8001; }
    location ^~ /domains { proxy_pass http://127.0.0.1:8001; }
    location ^~ /billing { proxy_pass http://127.0.0.1:8001; }
    location ^~ /analytics { proxy_pass http://127.0.0.1:8001; }
    location ^~ /build { proxy_pass http://127.0.0.1:8001; }
    location ^~ /favicon.ico { proxy_pass http://127.0.0.1:8001; }
    location ^~ /robots.txt { proxy_pass http://127.0.0.1:8001; }
    location ^~ /up { proxy_pass http://127.0.0.1:8001; }

    location / {
        proxy_pass http://127.0.0.1:8080;
    }
}
```

### Paths que precisam de escrita

- `/www/wwwroot/api-shlink.vr766.com/panel/laravel/storage`
- `/www/wwwroot/api-shlink.vr766.com/panel/laravel/bootstrap/cache`
- `/www/wwwroot/api-shlink.vr766.com/panel/laravel/database/database.sqlite`

### O que precisa existir para nao dar 404 ou 500

- `me.vr766.com` com rotas reservadas para o painel e fallback para o Shlink;
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

No deploy com `compose.prod.yml`, o painel sobe em `127.0.0.1:8001` e o Shlink continua em `127.0.0.1:8080`.
O painel também entra na rede Docker compartilhada do stack do Shlink e resolve o host interno `shlink` para conseguir consultar `/rest/health` sem depender do `localhost` do host.
O banco do painel usa o host interno `panel-db` para nao colidir com o alias `db` do stack do Shlink nessa mesma rede.
O Nginx do host faz a divisão por caminho:

- rotas administrativas vão para o painel;
- qualquer `dominio-do-cliente.tld/{slug}` vai direto para o Shlink;
- o painel expõe `/tls/allow` para permitir on-demand TLS em proxies que suportam essa política.

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
