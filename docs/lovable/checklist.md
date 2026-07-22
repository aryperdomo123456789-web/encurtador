# Checklist de Producao

Use este arquivo como backlog executivo do Lovable.

Estados:

- `[ ]` `pending`
- `[-]` `doing`
- `[x]` `done`

## Prioridade P0

Itens que bloqueiam qualquer entrega segura.

| Pri | Status | Item |
|---|---|---|
| P0 | [x] | Travar responsabilidades entre `api-shlink.vr766.com`, `app.me.vr766.com` e `me.vr766.com` |
| P0 | [ ] | Garantir que Laravel, MariaDB e variaveis de ambiente sobem sem erro |
| P0 | [x] | Garantir que `.env`, logs e arquivos gerados nao sejam versionados |
| P0 | [x] | Fechar autenticacao e base do painel |
| P0 | [x] | Garantir que a rota administrativa nao colida com slugs publicos |
| P0 | [x] | Validar que o fluxo free respeita 5 links por mes e 7 dias de validade |
| P0 | [ ] | Confirmar que o motor Shlink sobe isolado em Docker |
| P0 | [ ] | Garantir que os testes criticos passam antes de liberar qualquer deploy |

## Prioridade P1

Itens essenciais para o produto funcionar de ponta a ponta.

| Pri | Status | Item |
|---|---|---|
| P1 | [ ] | Ligar a integracao com Shlink com `X-Api-Key` e `Accept: application/json` |
| P1 | [ ] | Criar links free com slug aleatorio e expiração de 7 dias |
| P1 | [ ] | Criar links premium com `customSlug` |
| P1 | [ ] | Registrar dominio proprio no Shlink depois da validacao |
| P1 | [ ] | Consultar visitas e analytics no painel |
| P1 | [ ] | Entregar dashboard, lista de links e tela de criacao |
| P1 | [ ] | Entregar telas de dominios e metricas |
| P1 | [ ] | Definir proxy reverso e TLS automatico para dominios de clientes |

## Prioridade P2

Itens de acabamento, confiabilidade e operacao.

| Pri | Status | Item |
|---|---|---|
| P2 | [ ] | Adicionar backup minimo e log de auditoria |
| P2 | [ ] | Tratar erros `400`, `401`, `403`, `404`, `409`, `422`, `429` e `5xx` |
| P2 | [ ] | Cobrir testes de dominio proprio, analytics e proxy/TLS |
| P2 | [ ] | Documentar deploy, rollback e operacao |
| P2 | [ ] | Consolidar a base futura de `me.vr766.com` como projeto separado |

## Ordem de execucao

1. Resolver P0.
2. Fechar P1.
3. Completar P2.
4. Rodar a bateria final de testes.
5. Fazer deploy somente com tudo verde.

## Regra de liberacao

- Nao promover para producao se qualquer item P0 estiver pendente.
- Nao promover para producao se o dominio proprio nao estiver validado.
- Nao promover para producao se houver segredo sensivel versionado.
- Nao promover para producao sem testes criticos verdes.
