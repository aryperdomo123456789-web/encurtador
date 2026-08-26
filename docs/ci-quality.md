# Quality gate do MElink

O workflow `.github/workflows/quality.yml` bloqueia regressões antes de merge ou release. Ele roda em pushes de `main`, `p2/**`, `feat/**` e `release/**`, além de pull requests para `main`, `p2/**` e `release/**`.

## Checks

| Check | O que protege |
|---|---|
| `composer validate --strict` | Manifesto inválido, constraints inconsistentes e metadata quebrado. |
| `composer install` | Lockfile que não reproduz o ambiente. |
| Laravel Pint | Estilo e diffs difíceis de revisar. |
| PHP lint | Erros de sintaxe em app, config, migrations, rotas e testes. |
| View/route compilation | Blade ou rota inválida antes do deploy. |
| PHPUnit | Contratos de autenticação, links, quota, billing, API, workspaces, health e redirect. |
| `composer audit --locked` | Advisories conhecidos nas dependências fixadas. |
| Secret pattern scan | Chaves comuns de Stripe, AWS, webhook e chaves privadas em arquivos rastreados. |

## Critério de merge

Um pull request só deve ser considerado pronto quando todos os jobs estiverem verdes, o diff tiver sido revisado e qualquer alteração de migration possuir plano de rollback ou compatibilidade retroativa. Warnings de testes que representem apenas limitações do ambiente devem ser eliminados do harness; não são autorização para ignorar falhas funcionais.

## Secrets

Credenciais ficam em secrets do ambiente de execução e nunca em `.env.example`, fixtures, documentação ou commits. Se um segredo aparecer no histórico, ele deve ser revogado e substituído; remover o texto do último commit não é suficiente.
