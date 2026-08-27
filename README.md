# MElink para `api-shlink.vr766.com`

Stack base para rodar o MElink como painel SaaS sobre o motor de redirecionamento.

## O que já existe

- motor de links com banco `MariaDB` persistente.
- `compose.yml` com o backend do motor exposto apenas em `127.0.0.1:8080`.
- `DEFAULT_DOMAIN` apontando para `api-shlink.vr766.com`.
- `INITIAL_API_KEY` para a primeira integração do painel.
- O acesso separado do proprietário está documentado em
  [`docs/admin-owner-access.md`](docs/admin-owner-access.md).
- documentação de arquitetura e schema do painel em `docs/` e `sql/`.

## Arquivos adicionados

- `sql/mariadb-schema.sql`
- `docs/architecture.md`
- `docs/operacao-shlink.md`
- `docs/plano-producao.md`
- `docs/plano-testes.md`
- `panel/php/*`
- `panel/laravel/*`

## Subir a stack

```bash
docker compose up -d
```

## Verificar saúde

```bash
docker compose ps
docker compose logs -f shlink
```

## API

- Base da API: `https://api-shlink.vr766.com`
- Autenticação: `X-Api-Key`
- Sempre envie `Accept: application/json`

## Observações importantes

- `GEOLITE_LICENSE_KEY` está vazio por padrão, então a geolocalização de visitas fica desativada até você preencher essa chave.
- O motor de links deve ficar isolado do painel administrativo.
- O painel administrativo fica em `me.vr766.com`.
- `me.vr766.com/{slug}` e domínios de cliente vão direto para o motor no proxy reverso.
- Se você quiser trocar a chave inicial, basta editar `INITIAL_API_KEY` e recriar o container.

## Guias de produção

- [Lovable entry point](docs/lovable/README.md)
- [Checklist de producao para Lovable](docs/lovable/checklist.md)
- [Seção futura para `me.vr766.com`](docs/sections/me-vr766-com.md)
