# Privacidade e exportação de dados do MElink

**Status:** exportação v1 implementada e publicada na release `b5611ba`.

## Objetivo

O MElink oferece ao usuário autenticado uma cópia estruturada dos dados associados à própria conta. O fluxo está disponível no painel em `/settings/privacy` e no endpoint autenticado `GET /settings/privacy/export`.

A resposta é um arquivo JSON com `Content-Disposition: attachment`, `Cache-Control: no-store, private` e o identificador de formato `melink-data-export-v1`. O endpoint fica dentro do grupo de autenticação do painel e não é acessível por visitante anônimo.

## Escopo exportado

| Grupo | Conteúdo |
|---|---|
| Conta | ID, nome, e-mail, papel, verificação e data de criação |
| Links | Destino, domínio, slug, status, vigência, origem e timestamps dos links do usuário |
| Domínios | Domínio, estado DNS/TLS, alvo DNS e timestamps |
| Assinaturas | Plano, provedor, estado, período corrente e cancelamento no fim do ciclo |
| Workspaces | Workspaces em que o usuário é membro e seu papel no workspace |
| Tokens API | Nome, prefixo, scopes, expiração, último uso, revogação e criação |
| Conversões | Evento, IDs de atribuição, propriedades e timestamps vinculados ao usuário |
| Quota | Uso mensal de links gratuitos e última criação |

O escopo é filtrado por `user_id` nas relações de conta, links, domínios, tokens, eventos e quota. Workspaces retornam somente os workspaces dos quais o usuário é membro; o arquivo não inclui a lista de outros membros.

## Minimização e exclusões

Senhas, `remember_token`, hash do token API, token completo, hash de IP, hash de User-Agent, payloads brutos do Shlink, respostas brutas do Shlink, metadados Stripe e arquivos `.env` não entram no export. O arquivo não deve ser armazenado em cache pelo cliente ou enviado a terceiros sem necessidade.

As propriedades de eventos de conversão são dados fornecidos pelo consumidor da API; integrações devem evitar e-mails, documentos, credenciais e outros dados pessoais não necessários para atribuição.

## Operação e testes

O teste de feature comprova que uma conta recebe apenas os próprios links, que uma conta diferente não aparece no JSON, que a resposta baixa como anexo e que hashes de token não são incluídos. O teste também roda no quality gate do GitHub junto com Pint, lint, migrations em SQLite, suite completa, Composer audit e secret scan.

Para validar manualmente em ambiente autenticado, acesse **Privacidade** no menu do painel e use **Baixar meus dados**. O procedimento não cria estado comercial, não altera assinatura e não executa exclusão.

## Lacunas restantes

A exportação v1 não substitui um fluxo completo de ciclo de vida LGPD. Permanecem como próximos itens a exclusão/anonimização mediante confirmação forte, política de retenção automatizada, registro de consentimento, atendimento de solicitação por prazo, revisão jurídica da base legal e exportação de objetos adicionais caso novos módulos sejam adicionados.
