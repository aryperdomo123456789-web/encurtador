# Checklist de Producao

Este arquivo organiza o trabalho do Lovable em um fluxo visual com tres estados:

- `[ ]` `pending`: ainda nao iniciado
- `[-]` `doing`: em execucao
- `[x]` `done`: concluido e validado

## Resumo executivo

| Status | Area | Item |
|---|---|---|
| [ ] | Arquitetura | Travar responsabilidades entre `api-shlink.vr766.com`, `app.me.vr766.com` e `me.vr766.com` |
| [ ] | Base | Garantir que Laravel, MariaDB e variaveis de ambiente sobem sem erro |
| [ ] | Regras | Implementar limite free de 5 links por mes e validade de 7 dias |
| [ ] | Integração | Fechar fluxo com Shlink, incluindo dominio proprio e analytics |
| [ ] | Interface | Entregar dashboard, links, dominios e metricas |
| [ ] | Infra | Definir proxy, TLS, backup e logs |
| [ ] | Qualidade | Passar testes criticos e validacoes de producao |

## Legenda de andamento

### Arquitetura

- [ ] Confirmar que `api-shlink.vr766.com` fica apenas com o motor Shlink.
- [ ] Definir se o painel fica em `app.me.vr766.com` ou em `/admin`.
- [ ] Definir se `me.vr766.com` sera dominio publico de links ou area institucional.
- [ ] Registrar a responsabilidade de cada host em um unico documento.

### Base da aplicacao

- [ ] Validar que o projeto Laravel abre sem erro.
- [ ] Validar conexao com MariaDB.
- [ ] Validar leitura das variaveis de ambiente principais.
- [ ] Garantir que `SHLINK_BASE_URL`, `SHLINK_API_KEY` e `SHLINK_API_VERSION` estejam documentadas.
- [ ] Garantir que `.env`, logs e arquivos gerados nao sejam versionados.

### Regras de negocio

- [ ] Usuario free pode criar ate 5 links por mes.
- [ ] Link free recebe expiracao padrao de 7 dias.
- [ ] Link free nao aceita `customSlug`.
- [ ] Link premium aceita `customSlug`.
- [ ] Link premium pode usar dominio proprio.
- [ ] Dominio de cliente precisa ser validado antes do cadastro no Shlink.

### Integracao com Shlink

- [ ] Toda chamada envia `X-Api-Key`.
- [ ] Toda chamada envia `Accept: application/json`.
- [ ] Criacao de short URL funciona com resposta valida.
- [ ] Consulta de visitas e analytics funciona.
- [ ] Registro de dominio funciona.
- [ ] Erros `400`, `401`, `403`, `404`, `409`, `422`, `429` e `5xx` sao tratados.

### Interface do painel

- [ ] Login e cadastro funcionam.
- [ ] Dashboard mostra resumo util.
- [ ] Tela de links lista criacoes e status.
- [ ] Tela de criar link aplica regra free/premium.
- [ ] Tela de dominios mostra estado de DNS e TLS.
- [ ] Tela de metricas mostra visitas e dimensoes principais.

### Infraestrutura

- [ ] Shlink sobe isolado em Docker.
- [ ] MariaDB persiste dados.
- [ ] Proxy reverso encaminha o host correto.
- [ ] TLS automatico esta definido para dominios de clientes.
- [ ] Backup minimo esta definido.
- [ ] Logs de erro e auditoria estao sendo gravados.

### Qualidade

- [ ] Testes unitarios passam.
- [ ] Testes de integracao passam.
- [ ] Testes de fluxo do painel passam.
- [ ] Testes de rota publica passam.
- [ ] Testes de dominio proprio passam.
- [ ] Testes de analytics passam.
- [ ] Testes de proxy/TLS passam.

### Liberacao

- [ ] Um usuario novo consegue se cadastrar.
- [ ] Um usuario free cria um link e ve a regra de 7 dias.
- [ ] Um usuario premium cria um link com slug customizado.
- [ ] Um dominio proprio pode ser cadastrado sem quebrar o fluxo.
- [ ] O redirecionamento publico continua rapido e estavel.
- [ ] Nenhuma rota administrativa colide com slugs publicos.
- [ ] A aplicacao pode ser entregue sem ajustes manuais nao documentados.

## Ordem recomendada para execucao

1. Travar arquitetura e dominios.
2. Fechar autenticacao e base do painel.
3. Ligar regras free e premium.
4. Ligar a integracao com Shlink.
5. Entregar telas do painel.
6. Fechar dominio proprio e TLS.
7. Executar a bateria de testes.
8. Preparar deploy e rollback.

## Regras de aprovacao

- Nao considerar pronto se houver conflito entre painel e rotas publicas.
- Nao considerar pronto se o dominio proprio nao estiver validado.
- Nao considerar pronto se os testes criticos nao estiverem verdes.
- Nao considerar pronto se houver segredo sensivel versionado.
