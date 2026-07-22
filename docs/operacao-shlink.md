# Operação do Shlink no SaaS

## Endereços

- `api-shlink.vr766.com`: API do Shlink
- `me.vr766.com`: domínio público de links
- `app.me.vr766.com` ou `/admin`: painel administrativo, se você quiser evitar colisão de rotas

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

- painel: `app.me.vr766.com`
- slugs: `me.vr766.com/{slug}`

## Próximo passo operacional

- subir a stack do Shlink;
- aplicar o schema MariaDB;
- conectar o painel ao client PHP;
- escolher o proxy reverso com ACME automático.
