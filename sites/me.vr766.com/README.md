# `me.vr766.com`

Base reservada para o futuro site ou painel que vai morar em `me.vr766.com`.

## Objetivo

Manter este host separado do motor Shlink e organizar aqui tudo que o Lovable precisar para assumir o ambiente depois.

## Caminho recomendado

1. Ler [docs/lovable/README.md](../../docs/lovable/README.md).
2. Seguir [docs/lovable/checklist.md](../../docs/lovable/checklist.md).
3. Usar esta pasta como esqueleto do segundo ambiente.

## Estrutura

```text
sites/me.vr766.com/
  README.md
  app/
  docs/
  public/
  resources/
  routes/
  storage/
```

## O que vai aqui

- interface publica;
- painel administrativo, se este host for o escolhido;
- assets e componentes do frontend;
- documentacao de deploy;
- arquivos de apoio para o Lovable.

## O que nao vai aqui

- segredos reais;
- logs;
- cache;
- arquivos gerados de build;
- dependencias instaladas;
- dados temporarios de producao.

## Regra principal

Se houver duvida, trate esta area como um projeto separado do `api-shlink.vr766.com`.
