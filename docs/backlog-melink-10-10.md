# MElink 10/10 — backlog executável

**Autor:** Manus AI  
**Data:** 26 de agosto de 2026  
**Prioridade:** P0 bloqueia venda/escala; P1 aumenta valor e retenção; P2 diferencia; P3 enterprise.

## Como usar este backlog

Cada item deve ser transformado em issue vinculada a uma release, com responsável, teste automatizado e evidência de aceite. A ordem não é negociável quando um item protege segurança, dados ou disponibilidade. O time não deve puxar P2 enquanto P0 estiver quebrado; isso seria pintar o para-choque de um carro sem motor.

| Campo | Regra |
|---|---|
| P0 | Sem isso, não vender ou escalar tráfego |
| P1 | Entrega valor mensurável e melhora retenção |
| P2 | Diferencia e aumenta ticket depois da fundação |
| P3 | Enterprise, mobile avançado ou ecossistema |
| Esforço S | Até 2 dias de uma pessoa |
| Esforço M | 3–7 dias |
| Esforço L | 1–3 semanas |
| Esforço XL | Mais de 3 semanas ou dependência multi-time |

## Épico E0 — confiabilidade e segurança de release

| ID | Prioridade | Entrega | Esforço | Dependência | Critério de aceite |
|---|---|---|---:|---|---|
| E0-01 | P0 | Migrar web de `artisan serve` para PHP-FPM + Nginx | L | Infra | Dois processos web saudáveis; health não depende de sessão; P95 e 5xx medidos |
| E0-02 | P0 | Separar redirect edge do painel | XL | E0-01 | Slug continua resolvendo com painel parado; teste de falha do painel passa |
| E0-03 | P0 | Cache de resolução e circuit breaker | M | E0-02 | Cache hit não chama origem; falha externa não cria cascata; TTL e invalidação documentados |
| E0-04 | P0 | Queue worker e scheduler formais | M | E0-01 | Analytics, TLS e billing não rodam no request web; backlog e retry visíveis |
| E0-05 | P0 | Scan de secrets e rotação | M | Nenhuma | CI bloqueia secret novo; tokens atuais rotacionados sem downtime |
| E0-06 | P0 | Backup e restore automatizados | M | Nenhuma | Restore em ambiente efêmero diário/semanal; checksum e duração registrados |
| E0-07 | P0 | Deploy canário com rollback | L | E0-01 | Release falha volta para imagem anterior; migration expand/contract documentada |
| E0-08 | P0 | Rate limit distribuído e proteção de abuso | M | E0-03 | Scanner não esgota web workers; limites por IP/host/conta e respostas observáveis |
| E0-09 | P1 | Status page e alertas | M | E0-07 | Incidente público, alertas de P95, 5xx, fila e TLS funcionando |

## Épico E1 — ativação e onboarding

| ID | Prioridade | Entrega | Esforço | Dependência | Critério de aceite |
|---|---|---|---:|---|---|
| E1-01 | P0 | Wizard de primeiro link | M | E0-08 | Usuário novo conclui destino → slug → publicação sem documentação externa |
| E1-02 | P0 | Verificação de e-mail e recuperação de senha | M | E0-05 | Tokens expiram, são de uso único e não enumeram contas |
| E1-03 | P0 | Dashboard vazio orientado à ação | S | E1-01 | CTA principal cria link; estado vazio explica valor e limites |
| E1-04 | P1 | Onboarding por persona | M | E1-01 | Agência, negócio e creator recebem exemplos/UTMs coerentes |
| E1-05 | P1 | Importação CSV com pré-validação | M | E2-03 | Erros por linha, idempotência, limite e relatório de importação |
| E1-06 | P1 | Centro de ajuda contextual | M | E1-01 | Cada etapa crítica tem artigo, busca e link correto |
| E1-07 | P1 | Checklist de ativação | S | E1-01 | Domínio, campanha, QR e analytics aparecem como próximos passos medíveis |

## Épico E2 — gestão de links e campanhas

| ID | Prioridade | Entrega | Esforço | Dependência | Critério de aceite |
|---|---|---|---:|---|---|
| E2-01 | P0 | Modelo de link com estados e ownership por workspace | L | E3-01 | Nenhuma consulta cruza workspace; estados e transições testados |
| E2-02 | P0 | Edição de destino com histórico | M | E2-01 | Alteração não perde métricas; versão anterior é auditável |
| E2-03 | P1 | Busca, filtros, tags e pastas | M | E2-01 | Usuário encontra link por slug, tag, campanha e status |
| E2-04 | P1 | Duplicar e arquivar link | S | E2-01 | Duplicação não replica eventos nem viola quota |
| E2-05 | P1 | Campanha reutilizável com UTM templates | M | E2-01 | Template gera UTM normalizada e permite override explícito |
| E2-06 | P1 | Bulk actions e importação | L | E2-03 | 100/1.000 itens com progresso, retry e relatório |
| E2-07 | P1 | Expiração, pausa e página de destino controlada | S | E2-01 | Estado é refletido no redirect sem 500 |
| E2-08 | P2 | A/B test e regras por dispositivo/país | L | E0-03 | Distribuição determinística, fallback e auditoria |
| E2-09 | P2 | Smart routing por horário/UTM | M | E2-08 | Prioridade clara, preview da regra e kill switch |

## Épico E3 — identidade, domínios e TLS

| ID | Prioridade | Entrega | Esforço | Dependência | Critério de aceite |
|---|---|---|---:|---|---|
| E3-01 | P0 | Workspace como fronteira de dados | L | E1-01 | Usuário pessoal migra sem perda; todas as queries usam workspace |
| E3-02 | P0 | Domínio com DNS wizard | M | E0-01 | Registro esperado, estado, rechecagem e erro acionável |
| E3-03 | P0 | TLS automático idempotente | L | E3-02 | Emissão/renovação em job, alerta antes de expirar e rollback |
| E3-04 | P1 | Vários domínios por workspace | M | E3-02 | Limite por entitlements, ownership e isolamento |
| E3-05 | P1 | Branding do link e social preview | M | E2-01 | OG title/image/description versionados e seguros |
| E3-06 | P2 | White-label de agência | L | E3-01 | Domínio, favicon, e-mail, login e dashboard personalizáveis |

## Épico E4 — analytics e conversões

| ID | Prioridade | Entrega | Esforço | Dependência | Critério de aceite |
|---|---|---|---:|---|---|
| E4-01 | P0 | Schema versionado de eventos | M | E2-01 | Evento tem versão, link, workspace, timestamp e dedupe key |
| E4-02 | P0 | Ingestão assíncrona | L | E0-04 | Redirect não espera analytics; retry e DLQ monitorados |
| E4-03 | P1 | Agregação diária e série temporal | M | E4-02 | Dashboard não varre evento bruto em consulta comum |
| E4-04 | P1 | Filtros por campanha, UTM, domínio e período | M | E4-03 | Filtros combinados retornam valores consistentes |
| E4-05 | P1 | Exportação CSV assíncrona | M | E4-03 | Arquivo temporário, autorização, expiração e auditoria |
| E4-06 | P1 | Evento de conversão assinado | L | E4-01 | Lead/venda só é aceito com assinatura, janela e idempotência |
| E4-07 | P1 | Receita atribuída e janela configurável | L | E4-06 | Moeda, modelo, fonte e confiança aparecem no relatório |
| E4-08 | P1 | Comparação de campanhas e alertas | M | E4-03 | Comparativo e anomalia têm definição documentada |
| E4-09 | P2 | Pixels com consentimento e CMP | L | E4-06 | Sem consentimento, não há pixel não essencial; exclusão funciona |
| E4-10 | P2 | Relatórios compartilháveis/white-label | L | E3-06 | Token revogável, expiração, escopo e marca do cliente |

## Épico E5 — QR e experiências pós-clique

| ID | Prioridade | Entrega | Esforço | Dependência | Critério de aceite |
|---|---|---|---:|---|---|
| E5-01 | P0 | QR ligado ao link e aos mesmos eventos | M | E4-01 | Scan é distinguível de clique e aparece na campanha |
| E5-02 | P1 | QR visual com logo, cor, margem e templates | M | E5-01 | Contraste e correção de erro são validados automaticamente |
| E5-03 | P1 | Exportação PNG/SVG/PDF | M | E5-02 | Arquivo não vaza URL para terceiro; headers e nomes seguros |
| E5-04 | P1 | QR dinâmico com destino editável | M | E2-02 | QR impresso continua válido após troca de destino |
| E5-05 | P2 | Mini-hub/link-in-bio | L | E3-05 | Blocos acessíveis, temas, agendamento e analytics por bloco |
| E5-06 | P2 | Bridge page transparente | M | E5-05 | Aviso de destino, consentimento e denúncia de abuso |

## Épico E6 — billing e monetização

| ID | Prioridade | Entrega | Esforço | Dependência | Critério de aceite |
|---|---|---|---:|---|---|
| E6-01 | P0 | Catálogo de planos e entitlements | M | E3-01 | Limites centralizados, testáveis e sem lógica duplicada |
| E6-02 | P0 | Checkout Stripe em Test | M | E6-01 | Sessão associada a workspace e plano, sem confiar no browser |
| E6-03 | P0 | Webhook assinado e idempotente | M | E6-02 | Repetição do evento não duplica assinatura nem crédito |
| E6-04 | P0 | Portal, cancelamento e grace period | M | E6-03 | Links continuam com política documentada após cancelamento |
| E6-05 | P1 | Upgrade/downgrade/proration | M | E6-04 | Entitlements atualizados por webhook e auditáveis |
| E6-06 | P1 | Cupons, trials e recuperação | M | E6-03 | Falha de pagamento gera notificação e não quebra redirect |
| E6-07 | P1 | Medição de conversão do funil | S | E1-01 | Visitante → cadastro → primeiro link → Pro é mensurável |
| E6-08 | P2 | Billing por workspace/cliente | L | E3-01 | Agência vê custos e limites separados |

## Épico E7 — equipes, API e ecossistema

| ID | Prioridade | Entrega | Esforço | Dependência | Critério de aceite |
|---|---|---|---:|---|---|
| E7-01 | P1 | Convites e RBAC | L | E3-01 | Owner/admin/editor/viewer testados em cada endpoint |
| E7-02 | P1 | API v1 com tokens escopados | L | E2-01 | OpenAPI, rate limit, rotação, revogação e logs |
| E7-03 | P1 | Webhooks de link, clique e conversão | L | E4-01 | Retry, assinatura, replay seguro e idempotência |
| E7-04 | P1 | SDK mínimo TypeScript/Python | M | E7-02 | Exemplos executáveis e versionamento sem quebra |
| E7-05 | P2 | Integração Zapier/Make | M | E7-03 | Criar link e receber conversão sem credencial global |
| E7-06 | P2 | Integração Shopify/WordPress | L | E7-02 | Instalação, escopo e uninstall limpam credenciais |
| E7-07 | P3 | MCP/developer marketplace | L | E7-02 | Ações limitadas, consentidas e auditáveis |

## Épico E8 — segurança, privacidade e suporte

| ID | Prioridade | Entrega | Esforço | Dependência | Critério de aceite |
|---|---|---|---:|---|---|
| E8-01 | P0 | Política de privacidade e mapa de dados | M | E4-01 | Finalidade, retenção, base legal e subprocessadores documentados |
| E8-02 | P0 | Exportação e exclusão por workspace | M | E3-01 | Job assíncrono, confirmação, auditoria e verificação de remoção |
| E8-03 | P0 | 2FA e sessões/dispositivos | M | E1-02 | Ativação, recovery codes, revogação e alertas |
| E8-04 | P0 | SSRF e abuso de URL | M | E1-01 | Preview não acessa rede interna, metadata e esquemas proibidos |
| E8-05 | P1 | Sistema de denúncia e bloqueio | M | E0-08 | Abuse report, triagem, bloqueio e recurso auditáveis |
| E8-06 | P1 | Central de status/incidentes | S | E0-09 | SLO, incidentes, manutenção e contato visíveis |
| E8-07 | P1 | Pentest e threat modeling | L | E0-01 | Findings triados, reteste e exceções aprovadas |
| E8-08 | P2 | SOC 2 readiness | XL | E0-07 | Políticas, evidências, fornecedores e controles operacionais |

## Épico E9 — qualidade e crescimento

| ID | Prioridade | Entrega | Esforço | Dependência | Critério de aceite |
|---|---|---|---:|---|---|
| E9-01 | P0 | Testes de contrato de redirect | M | E0-02 | Host, slug, 404, expiração, regra e falha cobertos |
| E9-02 | P0 | Testes sintéticos públicos | M | E0-07 | Home, login, health, criação, redirect e QR monitorados |
| E9-03 | P1 | Teste A/B de landing com eventos | M | E6-07 | Experimento tem exposição, conversão e guarda de parada |
| E9-04 | P1 | Instrumentação de funil | M | E6-07 | Eventos sem PII desnecessária e coortes reproduzíveis |
| E9-05 | P1 | SEO e conteúdo por caso de uso | M | E1-04 | Páginas indexáveis, canônicas e sem claims sem fonte |
| E9-06 | P2 | In-app experiment platform | L | E9-04 | Feature flags auditáveis, rollout e kill switch |

## Ordem de releases

| Release | Conteúdo | Saída necessária |
|---|---|---|
| R0 | E0-01 a E0-08, E9-01, E9-02 | Redirect e painel suportam tráfego real com rollback |
| R1 | E1 completo, E2-01/E2-02, E6-01 a E6-04 | Primeiro link e primeiro pagamento funcionam sem intervenção manual |
| R2 | E3-02/E3-03, E4-01 a E4-05, E5-01 a E5-04 | Marca, QR e analytics geram valor recorrente |
| R3 | E2-03 a E2-07, E4-06 a E4-08, E6-05 a E6-07 | Cliente prova campanha e ROI |
| R4 | E7-01 a E7-04, E3-06, E4-10 | Agência consegue operar clientes e vender relatório |
| R5 | E2-08/E2-09, E4-09, E5-05/E5-06, E7-05/E7-06 | Diferenciação e expansão por integrações |
| R6 | E8-07/E8-08, E7-07 e E9-06 | Governança enterprise e ecossistema |

## Definition of Done da release

Uma release só está pronta quando o código passa lint e testes; a migration foi exercitada em banco limpo e cópia; o scan de secret e dependências está limpo ou com exceção documentada; os endpoints críticos têm smoke; logs não vazam dados; métricas e alertas existem; backup e rollback foram ensaiados; documentação está atualizada; e o release ID é rastreável até a imagem implantada.
