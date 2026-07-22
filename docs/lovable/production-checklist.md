# Checklist de Producao para Lovable

Este checklist define a ordem recomendada para o Lovable finalizar o projeto com criterio de producao.

## 1. Escopo e arquitetura

- [ ] Confirmar que `api-shlink.vr766.com` fica responsavel apenas pelo motor Shlink.
- [ ] Confirmar que o painel administrativo nao compete com rotas de slug.
- [ ] Definir se o painel vai ficar em `app.me.vr766.com` ou em `/admin`.
- [ ] Definir se `me.vr766.com` sera dominio publico de links ou apenas area institucional.
- [ ] Registrar em documento unico quais dominios pertencem a cada responsabilidade.

## 2. Base de aplicacao

- [ ] Validar que o projeto Laravel abre sem erro.
- [ ] Validar conexao com MariaDB.
- [ ] Validar leitura das variaveis de ambiente principais.
- [ ] Garantir que `SHLINK_BASE_URL`, `SHLINK_API_KEY` e `SHLINK_API_VERSION` estejam documentadas.
- [ ] Garantir que `.env`, logs e arquivos gerados nao sejam versionados.

## 3. Regras de negocio

- [ ] Usuario free pode criar ate 5 links por mes.
- [ ] Link free recebe expiração padrao de 7 dias.
- [ ] Link free nao aceita `customSlug`.
- [ ] Link premium aceita `customSlug`.
- [ ] Link premium pode usar dominio proprio.
- [ ] Dominios de clientes precisam ser validados antes do cadastro no Shlink.

## 4. Integracao com Shlink

- [ ] Toda chamada usa `X-Api-Key`.
- [ ] Toda chamada usa `Accept: application/json`.
- [ ] Criacao de short URL funciona com resposta valida.
- [ ] Consulta de visitas e analytics funciona.
- [ ] Registro de dominio funciona.
- [ ] Erros `400`, `401`, `403`, `404`, `409`, `422`, `429` e `5xx` sao tratados.

## 5. Interface do painel

- [ ] Login e cadastro funcionam.
- [ ] Dashboard mostra resumo util.
- [ ] Tela de links lista criacoes e status.
- [ ] Tela de criar link aplica regra free/premium.
- [ ] Tela de dominios mostra estado de DNS e TLS.
- [ ] Tela de metricas mostra visitas e dimensoes principais.

## 6. Infraestrutura

- [ ] Shlink sobe isolado em Docker.
- [ ] MariaDB persiste dados.
- [ ] Proxy reverso encaminha host correto.
- [ ] TLS automatico esta definido para dominios de clientes.
- [ ] Backup minimo esta definido.
- [ ] Logs de erro e auditoria estao sendo gravados.

## 7. Qualidade

- [ ] Testes unitarios passam.
- [ ] Testes de integracao passam.
- [ ] Testes de fluxo do painel passam.
- [ ] Testes de rota publica passam.
- [ ] Testes de dominio proprio passam.
- [ ] Testes de analytics passam.
- [ ] Testes de proxy/TLS passam.

## 8. Criterio de liberacao

- [ ] Um usuario novo consegue se cadastrar.
- [ ] Um usuario free cria um link e ve a regra de 7 dias.
- [ ] Um usuario premium cria um link com slug customizado.
- [ ] Um dominio proprio pode ser cadastrado sem quebrar o fluxo.
- [ ] O redirecionamento publico continua rapido e estavel.
- [ ] Nenhuma rota administrativa colide com slugs publicos.
- [ ] A aplicacao pode ser entregue sem depender de ajustes manuais nao documentados.

## 9. Ordem recomendada para o Lovable

1. Travar arquitetura e dominios.
2. Fechar autenticao e base do painel.
3. Ligar regras free e premium.
4. Ligar a integracao com Shlink.
5. Entregar telas do painel.
6. Fechar dominio proprio e TLS.
7. Executar a bateria de testes.
8. Preparar deploy e rollback.

## 10. Regras de aprovacao

- Nao considerar pronto se houver conflito entre painel e rotas publicas.
- Nao considerar pronto se o dominio proprio nao estiver validado.
- Nao considerar pronto se os testes criticos nao estiverem verdes.
- Nao considerar pronto se houver segredo sensivel versionado.
