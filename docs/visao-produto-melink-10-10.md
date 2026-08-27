# MElink 10/10 — visão de produto e régua de maturidade

**Autor:** Manus AI  
**Data:** 26 de agosto de 2026  
**Status:** Documento de direção para implementação  
**Escopo:** Produto, engenharia, dados, segurança, operação, monetização e crescimento.

> **Tese central.** O MElink não deve tentar vencer os líderes sendo apenas mais um encurtador. Deve vencer no mercado brasileiro como a camada simples, confiável e mensurável que transforma qualquer URL em um ativo de marca, campanha e conversão.

## 1. Definição de produto

O MElink é uma plataforma de branded links para marketing. O encurtamento é a porta de entrada; o produto completo administra domínios, slugs, destinos, QR Codes, campanhas, UTMs, eventos, analytics, conversões, equipes e integrações.

A promessa comercial precisa ser menor do que a capacidade técnica — nunca maior. A frase de posicionamento recomendada é:

> **Transforme qualquer URL em um link de marca, acompanhe cada clique e saiba quais campanhas realmente geram resultado.**

O produto deve atender três perfis sem criar três sistemas diferentes. Gestores de tráfego e agências precisam de padrão, exportação, domínios e colaboração. Pequenos negócios precisam de rapidez, QR, WhatsApp e uma leitura simples. Creators e afiliados precisam de bio links, destinos editáveis, tags e atribuição. Todos devem usar o mesmo núcleo de link, domínio, evento e analytics.

## 2. Benchmark competitivo

A pesquisa comparou Bitly, Rebrandly, Short.io, BL.INK, Dub, Replug, Branch, Linktree, TinyURL e PixelMe. Os players diretos vendem domínio próprio, edição de destino, QR, analytics, campanhas e API; os adjacentes adicionam conversão, pixels, afiliados, link-in-bio, e-commerce ou deep linking. As fontes oficiais estão listadas nas referências [1]–[10].

| Camada de valor | Padrão observado nos líderes | Decisão para o MElink |
|---|---|---|
| Entrada | Primeiro link em poucos passos e sem cartão em planos self-serve | Criar conta, gerar primeiro link e ver o resultado em menos de dois minutos |
| Marca | Domínio próprio, alias, preview social e QR | Fazer o domínio ser o centro do plano Pro |
| Operação | Tags, pastas, campanhas, filtros, bulk e edição de destino | Construir gestão operacional antes de inventar recursos decorativos |
| Medição | Clique, origem, dispositivo, geografia, conversão, receita e exportação | Evoluir de analytics técnico para analytics de decisão |
| Colaboração | Workspaces, RBAC, SSO, auditoria e white-label | Criar um pacote Agency que realmente aumente ticket |
| Plataforma | API, SDK, webhooks, integrações e dados exportáveis | Publicar uma API estável depois do núcleo estar observável |
| Expansão | A/B, smart routing, pixels, link-in-bio, afiliados e deep linking | Adicionar por evidência de uso, não por vaidade de roadmap |
| Confiança | SLA, segurança, privacidade, suporte e status público | Vender confiança com controles reais, não com selo decorativo |

A oportunidade do MElink está na combinação de **português, simplicidade, suporte local, domínio próprio, analytics compreensível e operação para agências pequenas**. Não é recomendável competir imediatamente com Branch em atribuição mobile ou com BL.INK em compliance enterprise; é melhor ocupar uma faixa que esses produtos atendem com mais complexidade e custo.

## 3. Régua 10/10

A nota 10/10 aqui não significa possuir todas as features do mercado. Significa entregar uma experiência confiável, rápida, compreensível e economicamente sustentável para o ICP escolhido, com maturidade técnica compatível com a promessa.

| Nível | Estado observável |
|---:|---|
| 0–2 | Protótipo, promessa sem operação, dados frágeis e fluxos quebrados |
| 3–4 | MVP funcional, mas sem cobrança, retenção ou operação confiável |
| 5–6 | Produto vendável para pequenos negócios, com criação, marca, QR e analytics básico |
| 7 | Produto repetível, com billing, conversões, exportação, edição de destino e operação estável |
| 8 | Produto forte para agências, com workspaces, RBAC, API, white-label e relatórios |
| 9 | Plataforma madura, com SLA, segurança formal, alta disponibilidade, integrações e atribuição avançada |
| 10 | Liderança comprovada por retenção, escala, suporte, dados de uso, confiabilidade e diferenciação sustentável |

Após o redesign da landing, o MElink tem uma vitrine próxima de 7/10, mas o produto completo ainda está aproximadamente em 5/10. A discrepância é um risco: a página não pode prometer uma plataforma de atribuição se o painel ainda entrega apenas uma fração dessa experiência.

## 4. Princípios de produto

**Valor antes de configuração.** O usuário deve alcançar o primeiro link antes de ser empurrado para domínio, pixel, workspace ou configuração avançada.

**Cada link é um ativo de campanha.** O recurso precisa possuir dono, estado, destino, domínio, slug, tags, UTMs, regras, eventos, QR, histórico e política de retenção.

**Analytics deve responder decisões.** “Quantos cliques?” é o começo. O produto precisa ajudar a responder “qual canal, anúncio, oferta ou cliente merece mais investimento?”.

**Configuração avançada em camadas.** O usuário básico não deve enfrentar a complexidade de uma operação enterprise; o usuário avançado deve encontrar profundidade quando precisar.

**Privacidade como funcionalidade.** O MElink deve minimizar dados, documentar finalidade, controlar retenção, permitir exclusão e solicitar consentimento quando houver pixels ou identificação não essencial.

**Não inventar prova.** Screenshots, números e depoimentos demonstrativos devem ser marcados como prévias ou substituídos por evidência real de clientes.

**O redirect é sagrado.** O caminho público deve ser isolado do painel e continuar funcionando mesmo com analytics, billing ou admin degradados.

## 5. Jornada ideal

| Momento | Experiência desejada | Métrica de aceite |
|---|---|---|
| Descoberta | Landing comunica resultado, não tecnologia | CTA principal entendido em teste qualitativo |
| Entrada | Cadastro simples, sem cartão e com verificação de e-mail | Time to first link ≤ 2 min |
| Primeiro valor | Usuário cria link e recebe URL/QR imediatamente | Ativação ≥ 40% como hipótese inicial |
| Aprendizado | Primeiro clique aparece com contexto de campanha | Analytics adoption ≥ 30% |
| Marca | Domínio próprio guiado por DNS e TLS automático | Domain attach rate ≥ 10% no Free |
| Conversão | Evento de lead/venda é instalado com instrução clara | Pelo menos um evento configurado por conta Pro |
| Expansão | Usuário convida equipe ou conecta cliente | Convites/workspace medidos por coorte |
| Renovação | Cliente entende valor e uso antes da cobrança | Churn e falha de pagamento monitorados |

As metas são hipóteses para instrumentação inicial, não promessas públicas. Devem ser recalibradas com dados reais após as primeiras coortes.

## 6. Pacotes comerciais

| Plano | Quem compra | Valor percebido | Requisitos mínimos |
|---|---|---|---|
| Free | Pessoa testando ou negócio pequeno | Primeiro link, QR básico e analytics curto | Sem cartão, limite antifraude e onboarding rápido |
| Pro | Profissional, creator e pequeno e-commerce | Domínio, campanhas, UTMs, QR dinâmico, histórico e exportação | Stripe, suporte, edição de destino e analytics consistente |
| Growth | Gestor de tráfego e agência pequena | Workspaces, conversões, regras, API e relatórios | RBAC, limites por workspace, webhooks e auditoria |
| Agency | Agência com múltiplos clientes | White-label, clientes, bulk e relatórios compartilháveis | Isolamento forte, permissões, faturamento por workspace e suporte |
| Enterprise | Operação regulada ou volumosa | SLA, SSO, retenção, segurança e suporte dedicado | Alta disponibilidade, DPA, auditoria, incident response e contrato |

A faixa inicial de preço deve ser validada com entrevistas, uso e conversão; a hipótese anterior de R$29–49 Pro, R$99–199 Growth e R$299–599 Agency é ponto de partida, não verdade de mercado.

## 7. Definição de pronto do produto líder

O MElink só deve se declarar pronto para competir no nível 8/10 quando houver: primeiro link em menos de dois minutos; criação, edição e exclusão idempotentes; domínio e TLS guiados; QR customizável e rastreável; analytics com conversões e exportação; Stripe testado e reconciliável; workspaces e RBAC; API documentada; logs e métricas; backup com restauração testada; runtime profissional; e uma política de abuso, privacidade e suporte que possa ser exibida ao cliente.

Para 9/10 e 10/10, a exigência deixa de ser uma lista de features e passa a ser operação comprovada: P95 de redirect monitorado, incidentes com post-mortem, SLA cumprido, baixa taxa de abuso, retenção saudável, expansão por conta e suporte capaz de resolver problemas sem depender do fundador.

## 8. O que não fazer

Não copiar layout, texto, screenshots, marca ou fotos de Rebrandly e de outros players. Não usar claims de uptime, número de clientes, aumento de CTR ou certificações sem comprovação própria. Não implementar cloaking enganoso, evasão de revisão de anúncios, coleta de dados sem consentimento ou pixels silenciosos. Não adicionar afiliados, marketplace ou deep linking mobile antes de estabilizar criação, billing, analytics e suporte.

## Referências

[1]: https://bitly.com/ — Bitly, plataforma de conexões e links.  
[2]: https://www.rebrandly.com/ — Rebrandly, branded links, analytics e QR Codes.  
[3]: https://short.io/ — Short.io, links com domínios próprios e analytics.  
[4]: https://www.bl.ink/ — BL.INK, link management enterprise.  
[5]: https://dub.co/ — Dub, links, atribuição e plataforma developer-first.  
[6]: https://replug.io/ — Replug, marketing de links, CTAs, pixels e white-label.  
[7]: https://www.branch.io/products/activation/ — Branch, deep linking e ativação mobile.  
[8]: https://linktr.ee/ — Linktree, link-in-bio, audiência e commerce.  
[9]: https://tinyurl.com/ — TinyURL, encurtamento e branded links.  
[10]: https://www.carbon6.io/pixelme — PixelMe/Carbon6, retargeting e atribuição para e-commerce.  
[11]: https://github.com/aryperdomo123456789-web/encurtador — Repositório MElink.  
[12]: https://github.com/aryperdomo123456789-web/encurtador/blob/p2/app-me-routing-prod/docs/architecture.md — Arquitetura documentada do MElink.  
[13]: https://github.com/aryperdomo123456789-web/encurtador/blob/p2/app-me-routing-prod/docs/plano-testes.md — Plano de testes do MElink.
