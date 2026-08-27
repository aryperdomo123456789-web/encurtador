# MElink 10/10 — especificação funcional

**Autor:** Manus AI  
**Data:** 26 de agosto de 2026  
**Status:** Requisitos de produto e UX para implementação.

## 1. Regra de experiência

O MElink deve sempre mostrar o próximo passo. A interface precisa reduzir decisões no primeiro contato, revelar complexidade gradualmente e provar valor com dados do próprio usuário. Nenhum fluxo crítico deve terminar em uma tela genérica sem ação recomendada.

## 2. Navegação principal

A navegação autenticada deve ser organizada em: Visão geral, Links, Campanhas, QR Codes, Analytics, Domínios, Equipe e Billing. Administração fica separada para owner. Cada página deve ter um CTA primário único, estado vazio útil, busca/filtro quando houver coleção e link contextual para ajuda.

O painel deve exibir workspace ativo, plano, uso atual, alertas, tarefas pendentes e último release somente onde isso ajudar o operador. Não transformar o dashboard em mural de métricas que não geram decisão.

## 3. Primeiro link

### Entrada

O usuário acessa “Criar link”, cola uma URL e vê a prévia do destino normalizado. O sistema mostra uma sugestão de slug, permite trocar domínio quando autorizado e oferece “Adicionar campanha” como próximo passo opcional.

### Campos

| Campo | Obrigatório | Regra |
|---|---:|---|
| URL de destino | Sim | HTTP/HTTPS, limite de tamanho, anti-SSRF e política de abuso |
| Domínio | Não no Free | Apenas domínio do workspace, verificado e ativo |
| Slug | Não no Free | Normalizado, único por domínio e sem rota reservada |
| Campanha | Não | Workspace/campanha do usuário |
| UTMs | Não | Template com override explícito e preview final |
| Expiração | Não | Limite conforme plano; não pode surpreender o usuário |
| Encaminhar query | Não | Default documentado e preview da URL final |
| Tags | Não | Unicode-safe, limite de quantidade e tamanho |

### Resultado

Ao publicar, mostrar URL curta, botão copiar, QR, campanha, destino, estado e CTA “Ver analytics”. O sistema deve informar se o link está em processamento; não afirmar sucesso antes de confirmar a resolução.

## 4. Gestão de links

A tabela deve mostrar slug, domínio, destino resumido, campanha, estado, cliques, último clique, data de criação e ações. As ações são Analytics, QR, Editar destino, Duplicar, Pausar, Arquivar e Excluir, conforme entitlements e permissões.

Edição de destino não deve mudar o slug. A tela deve exibir histórico de alterações com ator, data e versão. Excluir deve exigir confirmação, informar impacto no QR e oferecer arquivamento como alternativa. A operação deve ser idempotente e refletida no redirect.

## 5. Campanhas

Campanha possui nome, objetivo, período, tags, UTMs padrão, domínio, links e conversões. O usuário pode criar a campanha antes do link ou durante o builder. Templates devem ser reutilizáveis por workspace e conter convenções UTM como `utm_source`, `utm_medium`, `utm_campaign`, `utm_content` e `utm_term`.

A página de campanha deve responder: quais links existem, quantos cliques, quais canais, quais dispositivos, quais conversões e qual próximo movimento. O produto deve permitir duplicar uma campanha como rascunho, não copiar eventos históricos.

## 6. Analytics

O dashboard deve começar com período, link/campanha, cliques, tendência, cliques únicos aproximados, QR scans, conversões, receita atribuída, taxa de conversão, referer, dispositivo, país/região e UTMs. Cada métrica deve ter tooltip com definição e limitações.

Filtros não devem gerar chamadas sem ownership. Exportações devem ser assíncronas, com expiração, autorização e auditoria. O dashboard deve mostrar estado de coleta: “dados atualizados há X minutos”, atraso da fila e eventuais limitações de consentimento.

## 7. Conversões

O usuário escolhe um objetivo: lead, compra, cadastro, download ou evento customizado. O wizard entrega instrução para backend assinado ou script sujeito a consentimento. Uma conversão deve conter evento, timestamp, link/campanha, valor opcional, moeda e dedupe key.

A atribuição deve exibir janela, modelo e fonte. A plataforma não deve atribuir receita por inferência opaca. Se só houver clique, a UI deve dizer clique; se houver conversão confirmada, deve dizer conversão confirmada.

## 8. Domínio próprio

O wizard apresenta CNAME/A/AAAA esperado, valor atual observado, botão de rechecagem e estado: `pending_dns`, `dns_verified`, `tls_pending`, `active`, `error`, `paused`. Apenas `active` pode servir tráfego de produção.

A UX deve orientar provedores conhecidos sem exigir conhecimento de infraestrutura. Em caso de DNS incorreto, informar o registro sem vazar dados internos. Quando TLS estiver perto de vencer, o alerta aparece no workspace e no e-mail operacional.

## 9. QR Code

Cada link pode gerar QR dinâmico. O usuário escolhe formato, margem, correção de erro, cor, logo permitido e moldura/template. O preview deve informar que o destino é editável enquanto o link permanecer ativo.

O download deve oferecer SVG, PNG e PDF depois da validação de contraste. O scan de QR deve ser separado de clique convencional no analytics, sem duplicar evento por redirecionamento.

## 10. Workspaces e equipe

Ao criar conta, o usuário recebe um workspace pessoal. Pode criar workspace adicional se o plano permitir, nomear o cliente e convidar membros por e-mail. Papéis mínimos: Owner, Admin, Editor e Viewer.

| Ação | Owner | Admin | Editor | Viewer |
|---|---:|---:|---:|---:|
| Gerenciar billing | Sim | Opcional | Não | Não |
| Convidar/remover membros | Sim | Sim | Não | Não |
| Criar/editar links | Sim | Sim | Sim | Não |
| Excluir links | Sim | Sim | Por política | Não |
| Ver analytics | Sim | Sim | Sim | Sim |
| Gerenciar domínio | Sim | Sim | Não | Não |
| Exportar dados | Sim | Sim | Por política | Não |

Toda decisão de permissão deve ser testada no backend e refletida na UI apenas como conveniência.

## 11. API e integrações

A API v1 deve ter OpenAPI, autenticação por tokens escopados, rotação, revogação, rate limit, paginação, idempotency key e request ID. Endpoints mínimos: criar/editar/listar links, listar campanhas, consultar analytics agregado, gerar QR, domínios, conversões e webhooks.

Webhooks devem ter assinatura, timestamp, replay protection, retry exponencial e evento idempotente. Integrações iniciais recomendadas: Zapier/Make, Google Analytics, Meta Conversions API quando consentido, Shopify e WordPress. O produto não deve pedir uma chave global quando um escopo por workspace for suficiente.

## 12. Billing na interface

A página de planos deve mostrar limites atuais, uso, upgrade, downgrade, cancelamento, forma de pagamento, invoices e estado de cobrança. A UI deve explicar o que permanece funcionando no cancelamento. Falha de pagamento deve ter CTA para atualizar meio de pagamento, sem quebrar links publicados.

O upgrade deve refletir o entitlement somente após confirmação do webhook. O usuário deve poder abrir o portal de billing sem a aplicação expor detalhes internos do Stripe.

## 13. Acessibilidade e qualidade visual

Todos os fluxos devem funcionar por teclado, com foco visível, labels, mensagens de erro associadas, contraste suficiente e leitura por screen reader. Gráficos precisam de tabela ou resumo textual. Não depender apenas de cor para estado.

A linguagem deve ser português claro, sem claims absolutos. CTAs devem ser verbos de ação: “Criar meu primeiro link”, “Ver campanha”, “Conectar domínio”, “Exportar relatório”.

## 14. Eventos de produto

| Evento | Momento | Propriedades mínimas |
|---|---|---|
| `account_created` | Conta criada | workspace, origem, persona opcional |
| `first_link_started` | Builder aberto | workspace, plano |
| `link_created` | Link confirmado | link, campanha, domínio, plano |
| `link_first_click` | Primeiro clique | link, canal, consentimento |
| `analytics_viewed` | Dashboard aberto | link/campanha, período |
| `domain_connected` | DNS confirmado | workspace, domínio hash |
| `qr_generated` | QR criado | link, formato |
| `conversion_received` | Conversão aceita | link, campanha, valor opcional |
| `checkout_started` | Checkout aberto | plano, workspace |
| `subscription_active` | Webhook confirmado | plano, ciclo |
| `invite_sent` | Membro convidado | workspace, papel |

Propriedades devem ser minimizadas, sem tokens, URL completa sensível ou PII não necessária.

## 15. Critérios de aceite de experiência

O fluxo principal passa quando um novo usuário cria conta, publica um link Free, copia a URL, gera um QR, abre analytics e entende o próximo passo sem suporte. O fluxo Pro passa quando conecta domínio de teste, cria campanha com UTM, edita destino e confirma conversão em sandbox. O fluxo Agency passa quando cria workspace, convida membro, restringe acesso e exporta relatório com marca do cliente.

Nenhum fluxo é aceito se houver 500, autorização apenas visual, sucesso falso, dado de outra conta, cookie desnecessário em endpoint público, mensagem de erro interna ou caminho sem rollback.
