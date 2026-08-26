# Seção futura de `me.vr766.com`

Esta seção reserva o espaco do repositório para tudo que pertencer ao futuro ambiente de `me.vr766.com`.

## Objetivo

Separar claramente o que e do motor `api-shlink.vr766.com` e o que e do site/painel que vai viver em `me.vr766.com`.

## Estado atual

- O host `me.vr766.com` esta sendo usado hoje pelo painel Laravel do repo principal.
- O diretorio `/www/wwwroot/me.vr766.com` continua reservado para um futuro projeto separado.
- O site atual nao depende desse diretorio para funcionar.

## O que deve ficar aqui

- layout e interface publica do dominio;
- rota principal do site;
- componentes visuais e recursos do painel, se ele for hospedado neste host;
- documentacao de deploy do ambiente `me.vr766.com`;
- checklist de entrega e manutencao especifico deste dominio.

## O que nao deve ficar aqui

- variaveis secretas reais;
- arquivos gerados de build;
- dados temporarios;
- logs;
- cache;
- dependencias instaladas;
- qualquer coisa que pertena ao ambiente de producao sem necessidade.

## Estrutura sugerida

```text
me.vr766.com/
  README.md
  docs/
  app/
  public/
  resources/
  storage/
```

## Se o Lovable assumir esta area

1. Criar uma base limpa nesse diretorio.
2. Definir qual e a funcao do host.
3. Separar claramente rotas publicas e rotas administrativas.
4. Criar checklist proprio de producao.
5. Manter o deploy isolado do motor.

## Configuracao sugerida para o futuro host

Quando `me.vr766.com` virar um app proprio, o document root deve ser o `public/` do novo projeto, e nao o repositorio raiz.

Exemplo:

- codigo: `/www/wwwroot/me.vr766.com`
- document root: `/www/wwwroot/me.vr766.com/public`
- runtime: PHP 8.3
- escrita: `storage/` e `bootstrap/cache/`

Se o futuro app nao for Laravel, a mesma regra vale:

- root deve apontar para o diretorio publico do app;
- os arquivos de build nao devem ficar espalhados na raiz;
- dados sensiveis devem sair do git.

## Arquivo de apoio

- [Checklist de producao do Lovable](../lovable/checklist.md)
- [Operacao do motor no SaaS](../operacao-shlink.md)

## Regra principal

Se houver duvida, este dominio deve ser tratado como uma area independente do motor `api-shlink.vr766.com`.
