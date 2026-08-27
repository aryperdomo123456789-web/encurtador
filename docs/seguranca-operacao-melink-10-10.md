# MElink 10/10 — segurança, privacidade e operação

**Autor:** Manus AI  
**Data:** 26 de agosto de 2026  
**Status:** Norma operacional para implementação e publicação.

## 1. Objetivo

Este documento define os controles que tornam a promessa do MElink defensável. A auditoria anterior encontrou credenciais fixas em seeder público, autorização horizontal incompleta em analytics, inconsistência de persistência, quota sujeita a corrida, billing sem idempotência forte, TLS permissivo, `php artisan serve` em produção, ausência de worker/scheduler explícito, backups incompletos e dependências com advisories. Parte desses itens já recebeu correções na branch de implementação e no rollout, mas a maturidade 10/10 exige que a correção vire processo permanente.

## 2. Modelo de ameaça

| Ator | Objetivo | Controles principais |
|---|---|---|
| Scanner/bot | Consumir workers, enumerar caminhos ou gerar ruído | WAF, rate limit, cache, bloqueio de paths, logs e circuit breaker |
| Usuário autenticado curioso | Ler ou alterar ativos de outro workspace | Ownership no banco, RBAC e testes horizontais |
| Conta abusiva | Distribuir phishing, malware ou spam | Denúncia, análise, bloqueio, reputação de domínio e termos de uso |
| Atacante de conta | Tomar conta, sessão ou token | MFA, recuperação segura, rotação, alerta e menor privilégio |
| Atacante de integração | Abusar API, webhook, preview ou SSRF | Escopos, assinatura, allowlist, egress control e validação de URL |
| Falha operacional | Derrubar redirect ou perder dados | SLO, backup, restore, canário, rollback e incident response |
| Fornecedor externo | Indisponibilidade de Stripe/Shlink/DNS | Circuit breaker, filas, reconciliação e degradação segura |

## 3. Controles obrigatórios antes de escalar

### 3.1 Segredos

Nenhum segredo deve aparecer em Git, documentação, fixture, imagem Docker ou log. O seeder deve criar dados de demonstração sem credencial fixa ou exigir variáveis de ambiente de desenvolvimento. Qualquer valor que tenha aparecido em repositório público deve ser considerado comprometido e rotacionado, mesmo que a conta pareça inativa.

A produção deve usar secret manager ou arquivos com owner root, permissão 0600 e diretório separado do checkout. O processo da aplicação não deve ter acesso a segredos que não usa. APP_KEY, chaves Stripe, chave Shlink, tokens de API, credenciais de banco e chaves SMTP precisam de procedimento de rotação testado.

### 3.2 Identidade e sessão

Implementar verificação de e-mail, recuperação de senha por token de uso único, MFA para owner e planos superiores, rotação de sessão no login, invalidação de dispositivos, limite de tentativas e alerta de login anômalo. Respostas de login e cadastro não devem revelar se um e-mail existe.

Cookies devem ser `Secure`, `HttpOnly` e `SameSite` coerentes com o fluxo. Endpoints públicos de health não devem iniciar sessão nem emitir cookies de aplicação. O logout precisa invalidar sessão e token CSRF.

### 3.3 Autorização

Ownership deve ser resolvido em query/repository policy, nunca em condição de view. Todo controller que recebe ID, slug, short code, domínio, campanha, export ou QR deve aplicar a fronteira de workspace antes de chamar qualquer serviço externo. Testar matriz owner/admin/editor/viewer e acesso cruzado com IDs, slugs e domínios conhecidos.

### 3.4 URLs, preview e SSRF

Aceitar somente `http` e `https`, bloquear esquemas de arquivo, rede local, loopback, link-local, metadata cloud, ranges privados e resolução DNS suspeita. Preview remoto deve usar egress proxy controlado, limite de bytes, timeout, redirect limitado, MIME permitido e sandbox. Revalidar IP e host no momento da conexão para reduzir DNS rebinding/TOCTOU.

### 3.5 Abuso

Todo link deve ter mecanismo de denúncia. A triagem precisa registrar motivo, evidência, estado, analista e decisão. Bloquear rapidamente destinos de malware/phishing conforme sinais disponíveis e manter recurso de revisão. Não implementar técnicas para ocultar abuso, burlar políticas de anúncios ou falsificar destino.

## 4. Dados e LGPD

O mapa de dados precisa identificar cada campo, finalidade, base legal, retenção, subprocessador, acesso e caminho de exclusão. Cliques devem usar agregação e anonimização adequada; IP bruto não deve ficar mais tempo do que a finalidade exige. UTM, referer, dispositivo, localização aproximada e pixels podem constituir dados pessoais quando combinados.

O produto deve oferecer política de privacidade clara, contato de direitos, exportação por workspace, exclusão por workspace, correção quando aplicável e retenção configurável nos planos compatíveis. A exclusão precisa cobrir banco, filas, storage, exports, backups conforme política e caches expirados.

Pixels de Meta, Google, TikTok ou outras plataformas não podem ser carregados sem consentimento quando não forem estritamente necessários. A UI deve explicar finalidade, fornecedor, evento enviado e como revogar.

## 5. Billing e confiança comercial

O banco local deve ter `stripe_event_id` único. A entrada do webhook deve verificar assinatura no corpo bruto, gravar delivery antes de processar, aceitar replay idempotente e manter status de processamento. Eventos precisam ser ordenados por timestamp/versão da assinatura; um evento antigo não pode sobrescrever estado mais novo.

Entitlements devem ser uma projeção local da assinatura. O acesso ao Premium não pode depender do browser retornar da página de sucesso. Tratar `trialing`, `active`, `past_due`, `unpaid`, `paused`, `canceled` e `incomplete` com uma política visível ao cliente. Links publicados não devem quebrar por falha de pagamento; o produto deve preservar acesso de leitura e informar grace period.

Pricing, limites e histórico devem ser testados com moeda BRL, impostos, cupom, upgrade, downgrade, proration, cancelamento e refund. Nenhum plano deve anunciar recurso não disponível no painel.

## 6. Infraestrutura segura

A operação alvo deve usar imagem imutável, usuário não-root quando compatível, filesystem de aplicação somente leitura e volumes explícitos para storage. Remover bind mount do código em produção. Web, queue, scheduler e redirect devem ser processos/serviços separáveis, com limites de CPU/memória, restart policy e healthcheck.

Nginx/CDN deve remover fingerprinting desnecessário, aplicar CSP progressiva, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, HSTS e proteção de framing. Rate limits precisam existir na borda e no app; limites de login, cadastro, health, API, preview, TLS e fallback devem ter chaves e respostas observáveis.

Acesso SSH de rotina não deve usar root. A chave de auditoria deve ser temporária, registrada, com expiração e removida depois do trabalho. Ações administrativas devem usar contas nominativas, MFA e trilha de auditoria.

## 7. Backups e disaster recovery

O backup mínimo inclui banco, storage de branding/preview, configuração sem segredos expostos, manifests, certificados ou referências de emissão e scripts de restore. Segredos devem ser recuperáveis pelo procedimento de secret manager, não copiados em texto aberto.

| Controle | Frequência | Evidência |
|---|---:|---|
| Dump transacional | Diário | Arquivo comprimido, checksum e duração |
| Snapshot de storage | Diário | Lista e tamanho de objetos |
| Cópia fora do host | Diário/semanal | ID da cópia e retenção |
| Restore banco | Semanal | Log de restore e contagens |
| Restore completo | Mensal | Ambiente efêmero navegável |
| Teste de perda de host | Trimestral | RTO/RPO medidos |

O restore deve exigir confirmação explícita, validar arquivo, bloquear path traversal, usar usuário limitado quando possível e executar verificações pós-restore. Definir RPO e RTO por plano; não prometer zero perda se o pipeline não suporta isso.

## 8. Observabilidade

Todos os requests devem carregar `request_id`, `release_id`, host, rota, método, status, duração, classe de erro e resultado de cache. Logs de erro devem ser redigidos. Métricas devem separar painel, redirect, Shlink, billing, domínio/TLS, fila e banco.

Criar dashboards para redirect P50/P95/P99, 4xx/5xx, cache hit, upstream latency, workers, fila, webhook, criação de link, conversão, abuso, domínio pendente e expiração TLS. Alertas devem ter runbook e dono; alerta sem ação é decoração.

## 9. SLOs iniciais

| SLO | Meta inicial | Ação de violação |
|---|---:|---|
| Disponibilidade de redirect | ≥ 99,9% mensal | Incidente e análise de causa |
| Redirect P95 cache hit | < 250 ms | Investigar edge/origem |
| Redirect 5xx | < 0,5% | Rollback ou circuit breaker |
| Painel P95 | < 800 ms | Profiling e fila |
| Webhook processado | < 2 min | Retry, DLQ e reconciliação |
| TLS próximo do vencimento | 0 domínios < 14 dias sem alerta | Prioridade operacional |
| Restore mensal | 100% sucesso | Bloquear claims de confiabilidade |

## 10. Incident response

O incidente deve ter classificação, comandante, canal, timeline, impacto, mitigação, comunicação, causa raiz, ações corretivas e post-mortem. Para indisponibilidade de redirect, a prioridade é preservar a resolução; analytics, billing e admin devem degradar antes do caminho público.

Runbooks mínimos: painel 5xx, Shlink indisponível, DNS/TLS, fila acumulada, webhook duplicado, vazamento de segredo, abuse report, banco corrompido, rollback e restore. Cada runbook deve ser ensaiado em ambiente não produtivo.

## 11. Quality gates de CI/CD

O pipeline deve bloquear merge quando houver falha de teste, lint, migration incompatível, secret detectado, advisory crítico sem exceção, imagem sem digest, ausência de changelog operacional ou contrato quebrado. A publicação exige aprovação, backup, smoke, janela de observação e rollback conhecido.

O gate público deve verificar home, login, cadastro, health, readiness, redirect válido, slug 404, domínio customizado em ambiente de teste, QR, analytics, webhook de teste e headers de segurança. Testes que retornam 419 por harness mal configurado não podem ser classificados como cobertura verde.
