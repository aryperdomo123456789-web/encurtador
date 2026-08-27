# Acesso separado do proprietário

## Arquitetura

O projeto mantém uma única aplicação Laravel e separa os acessos por host:

| Host | Uso | Acesso |
| --- | --- | --- |
| `https://me.vr766.com` | Painel dos clientes e links públicos | Usuários comuns; o dono também pode usar o painel operacional |
| `https://admin.me.vr766.com` | Administração da operação | Somente usuários com `role=owner` |
| `https://api-shlink.vr766.com` | Motor/API Shlink | Nenhum login humano |

O host administrativo não é uma segunda aplicação. Ele usa o container `panel`
existente em `127.0.0.1:8001`. O diretório `/www/wwwroot/admin.me.vr766.com`
é somente o cadastro do site no aaPanel e não deve receber código do projeto.

## Variável de produção

Em `deploy/.env`, mantenha:

```env
PANEL_HOST=me.vr766.com
PANEL_ADMIN_HOST=admin.me.vr766.com
```

O middleware do painel aceita somente esses hosts. No host administrativo:

- `/login` mostra a tela de proprietário;
- cadastro de usuários é bloqueado;
- o login de uma conta que não seja `owner` é recusado;
- todas as rotas autenticadas exigem `owner`;
- a sessão continua host-only, sem compartilhar cookie com outros domínios.

No host público, usuários comuns continuam usando `/login` e `/register`.
As áreas `/admin/*` continuam protegidas pelo papel `owner`.

## Cloudflare

Criar um registro DNS:

```text
Tipo: A
Nome: admin
Valor: IP público do servidor
Proxy: Proxied (laranja)
```

Usar SSL/TLS `Full (strict)` e manter um certificado válido no servidor de
origem para `admin.me.vr766.com`.

## aaPanel/Nginx

Adicionar `admin.me.vr766.com` como site para que o aaPanel emita e mantenha o
certificado. O vhost deve encaminhar todas as requisições para:

```nginx
proxy_pass http://127.0.0.1:8001;
```

Não encaminhar para `api-shlink.vr766.com`, não usar PHP separado e não criar
outro container.

Após alterações no vhost:

```bash
nginx -t
nginx -s reload
```

## Verificação

```bash
curl -fsS https://admin.me.vr766.com/healthz
curl -I https://admin.me.vr766.com/login
curl -I https://me.vr766.com/login
```

O teste sem assinatura abaixo deve retornar `400 invalid signature`; isso
confirma que o endpoint do Stripe chegou ao Laravel:

```bash
curl -sS -X POST https://me.vr766.com/billing/webhook \
  -H 'Content-Type: application/json' \
  --data '{}'
```

## Deploy e rollback

O deploy deste projeto, sem afetar outros Compose, é:

```bash
cd /www/wwwroot/api-shlink.vr766.com/deploy
docker compose --env-file .env -f compose.prod.yml up -d --force-recreate panel queue scheduler
```

Validar os serviços:

```bash
docker compose --env-file .env -f compose.prod.yml ps
docker compose --env-file .env -f compose.prod.yml exec -T panel php artisan route:list --path=billing
```

Se for necessário voltar somente a aplicação, restaure o commit anterior e
recrie `panel`, `queue` e `scheduler`. Não remova volumes e não execute
`down -v`, pois os volumes contêm o banco e os dados persistentes.

## Segurança

- Nunca versionar `deploy/.env` ou qualquer chave Stripe.
- Rotacionar imediatamente uma `STRIPE_SECRET` que tenha sido compartilhada.
- Usar `STRIPE_WEBHOOK_SECRET` gerado pelo endpoint real da Stripe.
- Usar um `STRIPE_PREMIUM_PRICE_ID` real do mesmo modo (`test` ou `live`) das chaves.
- O webhook continua público por necessidade, mas é autenticado pelo HMAC da Stripe.
