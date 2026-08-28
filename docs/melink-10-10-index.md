# MElink 10/10 — índice de implementação

**Autor:** Manus AI  
**Data:** 26 de agosto de 2026  
**Objetivo:** organizar a documentação necessária para transformar o MElink em uma plataforma de links de marca, campanhas e atribuição competitiva.

## Documentos

| Documento | Uso | Ordem recomendada |
|---|---|---:|
| [`visao-produto-melink-10-10.md`](visao-produto-melink-10-10.md) | Posicionamento, régua de maturidade, princípios, jornada e planos | 1 |
| [`matriz-gaps-melink-10-10.md`](matriz-gaps-melink-10-10.md) | Estado atual, lacunas, prioridade, dependências e evidência de pronto | 2 |
| [`arquitetura-alvo-melink-10-10.md`](arquitetura-alvo-melink-10-10.md) | Serviços, dados, eventos, redirect, analytics, billing, TLS e escala | 3 |
| [`especificacao-funcional-melink-10-10.md`](especificacao-funcional-melink-10-10.md) | Fluxos, telas, estados, eventos e critérios de aceite | 4 |
| [`backlog-melink-10-10.md`](backlog-melink-10-10.md) | Épicos, histórias, dependências, esforço e releases | 5 |
| [`seguranca-operacao-melink-10-10.md`](seguranca-operacao-melink-10-10.md) | Threat model, secrets, LGPD, SLO, backup, incidentes e CI/CD | 6 |
| [`go-to-market-melink-10-10.md`](go-to-market-melink-10-10.md) | ICP, pricing, funil, métricas, canais, suporte e validação comercial | 7 |
| [`plano-implementacao-produto-top1.md`](plano-implementacao-produto-top1.md) | Pacote já implementado de confiabilidade, campanhas, analytics, QR e billing | Referência |
| [`landing-redesign-melink.md`](landing-redesign-melink.md) | Redesign da home, copy, assets, CTAs e acessibilidade | Referência |
| [`runtime-fpm-nginx.md`](runtime-fpm-nginx.md) | PHP-FPM, Nginx, healthcheck, scheduler, queue e rollback | 8 |
| [`ci-quality.md`](ci-quality.md) | CI, testes, Pint, Composer audit e secret scan | 9 |
| [`api-v1-melink.md`](api-v1-melink.md) | Contrato OpenAPI, autenticação, scopes, idempotência e operação | 10 |
| [`privacy-lgpd-melink.md`](privacy-lgpd-melink.md) | Exportação LGPD, minimização e escopo de dados | 11 |
| [`restore-drill-melink.md`](restore-drill-melink.md) | Evidência de backup, storage e restore em ambiente descartável | 12 |
| [`admin-plan-catalog.md`](admin-plan-catalog.md) | Catálogo owner-only, preços, limites e checkout por plano | 13 |

## Sequência de execução

1. Fechar P0 de segurança, redirect, dados, billing, runtime e backup.
2. Instrumentar onboarding e funil.
3. Entregar conversões, exportação, edição de destino, QR completo e domínio/TLS.
4. Introduzir workspaces, RBAC, clientes e relatório de agência.
5. Publicar API documentada, webhooks, SDK e integrações.
6. Só depois priorizar IA, deep linking mobile, afiliados e marketplace.

## Fontes do benchmark

O benchmark primário preservado em [`/home/ubuntu/melink_benchmark_top10_sources.md`](file:///home/ubuntu/melink_benchmark_top10_sources.md) compara Bitly, Rebrandly, Short.io, BL.INK, Dub, Replug, Branch, Linktree, TinyURL e PixelMe. A documentação final mantém referências públicas e não trata claims comerciais dos concorrentes como garantia do MElink.

## Critério de encerramento do programa

O programa 10/10 termina quando o produto demonstrar, em dados reais e não em intenção: ativação repetível, retenção saudável, billing reconciliável, redirect dentro do SLO, analytics de conversão, isolamento de dados, backup restaurado, incidentes operáveis, workspaces funcionais, API documentada e suporte capaz de atender clientes sem intervenção manual do fundador.

## Entregas adicionais da release de plataforma

- [`api-v1-melink.md`](api-v1-melink.md): autenticação Bearer, scopes, emissão, revogação, idempotência e contrato OpenAPI público da API v1.
- `GET /api/v1/openapi.json`: contrato OpenAPI 3.1 público, validado por teste de estrutura e scan de conteúdo sensível.
- [`privacy-lgpd-melink.md`](privacy-lgpd-melink.md): exportação JSON autenticada, minimização de dados e lacunas de ciclo de vida LGPD.
- [`restore-drill-melink.md`](restore-drill-melink.md): dump, storage, checksums e restore estrutural em MariaDB descartável, sem tocar a produção.
- Fluxos de identidade: verificação de e-mail condicional em produção e recuperação de senha com resposta anti-enumeração.
- Hardening P0: rate limits por finalidade, trusted proxies explícitos, health sem sessão, headers globais e webhook Stripe idempotente.
- Integridade de links: reserva atômica de quota Free, reconciliação do espelho e proteção contra corridas.
- Catálogo comercial owner-only: planos Free, Start e Pro com preço em centavos, limites e status Stripe.
- Checkout multi-plano: servidor resolve o `plan_id` para Price ID; webhook mapeia assinaturas pelo preço e rejeita preço desconhecido com segurança.

## Release de plataforma v1

- [`release-plataforma-melink-10-10-v1.md`](release-plataforma-melink-10-10-v1.md): dependências corrigidas, segurança, billing, quota, workspaces, API, edição, exportação, conversões e rollout.
- `POST /api/v1/events`: ingestão idempotente de conversões por workspace, com hashing de IP/User-Agent.
- `PATCH /api/v1/links/{id}`: edição do destino sem alterar o slug.
- `GET /analytics/{shortCode}/export`: exportação CSV filtrada de visitas.
- Upgrade validado: Laravel 12.68.0, Guzzle 7.15.5 e CommonMark 2.10.0.
