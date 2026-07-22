# Notas tecnicas e suposicoes

Este arquivo registra suposicoes necessarias para fechar a arquitetura sem extrapolar a documentacao oficial do Shlink.

## Suposicoes

1. `me.vr766.com` e o painel SaaS responsavel por usuarios, assinaturas, planos e cobranca.
2. O schema em `sql/mariadb-schema.sql` nao e o schema interno do Shlink; ele e exclusivo da camada SaaS.
3. A classificacao `free`, `premium` e `custom_domain` e uma convencao da SaaS.
4. A cota mensal de links gratuitos e contabilizada por mes UTC.
5. A fonte de verdade para metricas de visitas e o proprio Shlink; a SaaS usa cache e auditoria local apenas quando necessario.
6. A verificacao de posse de dominio proprio pode usar DNS TXT, desafio HTTP ou outro metodo que a aplicacao decidir. O Shlink nao define esse fluxo.
7. On-Demand TLS deve ficar no proxy reverso. O Shlink nao deve ser tratado como terminador de TLS.
8. O reverse proxy deve encaminhar o `Host` original e os cabecalhos de proxy necessarios para o Shlink resolver dominios nao padrao corretamente.

## Decisoes praticas

- Usar MariaDB separada ou schema separado para a camada SaaS.
- Guardar a contagem mensal em tabela propria para nao depender de consultas de alto custo no Shlink.
- Registrar cada tentativa relevante em `link_event_log` para suportar suporte, antifraude e auditoria.

## Ponto de revisao futuro

- Se o produto passar a usar varios ambientes ou varios provedores de pagamento, a tabela `subscriptions` pode ganhar campos adicionais de integracao sem mudar a estrategia de cota.
