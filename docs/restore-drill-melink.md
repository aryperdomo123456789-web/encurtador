# Backup e restore drill do MElink

**Data da execução:** 26 de agosto de 2026, UTC  
**Ambiente:** produção como fonte de leitura + MariaDB 11.4 descartável  
**Status:** aprovado para o escopo exercitado

## Escopo

O drill gerou um dump MariaDB comprimido a partir do serviço `db`, um arquivo comprimido do volume persistente de storage e checksums SHA-256. O dump e o storage foram validados com `gzip -t`; o storage foi extraído em diretório de restauração separado para verificar legibilidade.

A restauração do banco foi executada em um container MariaDB temporário, com rede `none`, banco `restore_check` e usuário descartável com acesso somente ao banco de teste. O container foi removido automaticamente ao terminar. O banco de produção não recebeu comandos de restauração, `DROP`, migration ou alteração de dados durante o exercício.

## Evidência observada

| Verificação | Resultado |
|---|---:|
| Dump MariaDB criado | aprovado |
| Dump gzip íntegro | aprovado |
| Storage persistente arquivado | aprovado |
| Storage extraído em diretório separado | aprovado |
| Container MariaDB descartável pronto | aprovado |
| Import do dump no banco descartável | aprovado |
| Tabelas de migrations restauradas | 16 |
| Usuários restaurados no snapshot | 0 |
| Short links restaurados no snapshot | 0 |
| Serviços de produção reiniciados pelo drill | 0 |

O snapshot continha o schema e nenhum usuário ou link persistido no momento da execução. Isso não invalida o restore estrutural, mas significa que este exercício não comprova a recuperação de um registro comercial real. Um próximo drill deve usar uma cópia mascarada ou fixture controlada, com autorização explícita para validar conteúdo e relacionamentos.

## Correções realizadas

O script `deploy/scripts/backup-db.sh` passou a autenticar o dump com as credenciais já disponíveis no container e a usar socket local com TLS desativado para evitar negociação indevida entre processos no mesmo container. O script `deploy/scripts/restore-db.sh` passou a usar TCP local autenticado, alinhado ao healthcheck do MariaDB. Nenhuma senha é impressa ou registrada no resultado.

A operação também mantém backup de checkout, configuração, scripts e manifesto de release em diretórios datados no servidor. O endpoint `GET /health/release` permite correlacionar o estado público com o SHA promovido.

## Limitações e próximos passos

O drill não substitui backup automatizado diário, retenção testada, cópia fora do host, criptografia gerenciada, restauração periódica de storage com dados mascarados, teste de certificados e simulação de perda de host. Também não valida uma restauração destrutiva no banco de produção, que permanece proibida como procedimento automático.
