# MElink — Plano de implementação do Produto Top 1

**Autor:** Manus AI  
**Status:** pacote de fundação implementado localmente e validado  
**Branch de trabalho:** `feat/melink-product-foundation`  
**Base:** `origin/p2/app-me-routing-prod`  
**Commit-base:** `1731b01e63e52c47ba44090a91ed494e4c230bce`  
**Regra operacional:** nenhuma publicação, alteração de produção ou mudança de credenciais foi feita nesta etapa.

## 1. Objetivo de produto

O MElink deve evoluir de um encurtador operacional para uma plataforma SaaS de **links de marca orientados a campanhas**. O primeiro link precisa ser rápido de criar; o redirect precisa sobreviver a scanners e indisponibilidade parcial; o painel precisa explicar performance; e os recursos premium precisam gerar valor comercial perceptível para creators, negócios e agências.

A referência estratégica combina a simplicidade de TinyURL e Encurtador.com.br, a marca e analytics de Rebrandly, a operação em equipe de Short.io, a conversão de Replug, o tracking de Dub/PixelMe, o smart routing da Branch e o fluxo de página/creator de Linktree. A decisão é não copiar telas: o diferencial do MElink será uma experiência enxuta em português, com domínio próprio, campanha, UTM, QR, analytics operacional e caminho claro para agência.

## 2. Pacote implementado

| Área | Implementação | Arquivos principais | Resultado esperado |
|---|---|---|---|
| Redirect | Origem configurável para o fallback, paths de scanner bloqueados, limites de tamanho, timeouts curtos, headers seguros e rate limit dedicado | `config/shlink.php`, `PublicRedirectController.php`, `AppServiceProvider.php`, `routes/web.php` | Scanner não derruba o painel; fallback não deve recursar pelo proxy público quando `SHLINK_REDIRECT_BASE_URL` aponta para a rede Docker |
| Campanhas | Título interno, tags normalizadas, cinco parâmetros UTM, domínio ativo e encaminhamento de query | `LinkController.php`, `LinkProvisioner.php`, `links/premium.blade.php` | Premium cria campanhas rastreáveis e associadas ao domínio verificado do próprio usuário |
| Analytics | Dashboard com KPIs, período, contexto da campanha, países visíveis e mensagem de recuperação quando o Shlink falha | `AnalyticsController.php`, `analytics/show.blade.php` | O usuário recebe decisão operacional, não um payload JSON bruto |
| QR | Geração local de QR em SVG com correção de erro alta, headers de cache privado e ownership | `QrCodeController.php`, `routes/web.php`, `composer.json` | Compartilhamento e impressão sem enviar a URL do cliente a serviço externo |
| Links | Atalhos de abrir, analytics, QR, excluir e visualização de título/tags | `links/index.blade.php` | Histórico vira centro de operação da conta |
| Billing | Checkout e portal não expõem exceção Stripe; configuração ausente produz mensagem segura e log correlacionado | `BillingController.php` | Falhas de integração não vazam detalhes internos nem confundem o usuário |
| Testes | Casos para redirect, campanha/UTM/domínio, analytics resiliente e QR/ownership; CSRF desabilitado somente no harness de teste | `tests/Feature/...`, `tests/TestCase.php` | Regressão detectável em cada alteração |

## 3. Contrato de configuração

A API de gestão continua usando `SHLINK_BASE_URL`. O fallback público deve usar uma origem interna quando o painel e o Shlink compartilham rede Docker:

```dotenv
SHLINK_BASE_URL=https://api-shlink.vr766.com
SHLINK_REDIRECT_BASE_URL=http://shlink
SHLINK_API_KEY=...
SHLINK_REDIRECT_CONNECT_TIMEOUT=1.0
SHLINK_REDIRECT_TIMEOUT=3.0
SHLINK_REDIRECT_MAX_PATH_LENGTH=191
SHLINK_REDIRECT_RATE_LIMIT=120
SHLINK_REDIRECT_RATE_DECAY=60
```

`SHLINK_REDIRECT_BASE_URL` é o ponto crítico. Se o painel chamar `api-shlink.vr766.com` com `Host: me.vr766.com`, o proxy pode devolver a chamada ao Laravel e criar a cascata observada na auditoria. Em produção, o nome `http://shlink` deve corresponder ao serviço real da rede Docker ou ser substituído pela origem interna efetiva. Essa alteração de ambiente ainda não foi aplicada no servidor.

## 4. Fluxos de produto

### 4.1 Criação de campanha premium

O usuário autenticado abre **Links → Criar campanha**. Informa a URL de destino, escolhe um slug, seleciona um domínio ativo da própria conta, nomeia a campanha, adiciona tags, configura UTMs e define expiração opcional. O servidor valida URL, slug, domínio por `user_id` e status `active`, normaliza tags e anexa os parâmetros UTM sem apagar query já existente.

O provisionador envia os atributos compatíveis ao Shlink e grava o `customer_domain_id` no espelho local. O estado da conta continua sendo a fonte de ownership; o motor permanece a fonte de verdade do redirect e das visitas.

### 4.2 Analytics

A tela de analytics primeiro localiza o `ShortLink` pelo usuário autenticado. Esse detalhe impede que um usuário descubra métricas de outro e também evita resolver o cliente Shlink antes da guarda de ownership. Depois, consulta o motor com domínio, período, paginação e exclusão de bots. Se o motor falhar, a tela permanece utilizável e informa que o link continua ativo.

### 4.3 QR

A rota autenticada `/links/{link}/qr` verifica proprietário, status ativo e URL curta persistida. O SVG é gerado localmente com a URL curta real, correção de erro alta e cache privado curto. Não há endpoint público para converter URLs arbitrárias em QR, reduzindo abuso e vazamento de dados.

## 5. Critérios de aceite executados

| Critério | Resultado |
|---|---|
| Paths de scanner são rejeitados sem chamada ao Shlink | Passou |
| 404 do upstream é preservado e timeout vira erro controlado | Passou |
| `HEAD` preserva status e headers sem corpo | Passou |
| Rate limit do fallback é isolado das rotas administrativas | Implementado; validar em staging com carga controlada |
| Campanha premium aceita domínio ativo, tags, UTMs e query original | Passou |
| Domínio de outro usuário é rejeitado | Coberto pela regra de validação/ownership |
| Analytics mostra KPIs e não despeja JSON bruto | Passou |
| Analytics indisponível mostra recuperação segura | Passou |
| QR gera SVG e bloqueia outro usuário | Passou |
| Billing não expõe mensagem de exceção Stripe | Implementado; checkout real ainda não foi executado |
| Nenhum segredo novo foi adicionado ao Git | Passou na varredura do diff |
| Suíte completa do Laravel | **82 testes, 285 asserções, 0 falhas** |
| Estilo dos arquivos alterados | Passou no Pint para os 13 arquivos verificados |
| Estilo do repositório inteiro | Há 40 problemas legados em 96 arquivos; não foram reformateados para evitar ruído |

## 6. Publicação controlada

A publicação deve ser feita somente depois de revisão humana do diff. O primeiro rollout recomendado é em janela controlada, com backup verificado e plano de rollback. A sequência sugerida é:

1. Criar ou confirmar a rede Docker compartilhada entre painel e Shlink e identificar o nome real do serviço do motor.
2. Configurar `SHLINK_REDIRECT_BASE_URL` para essa origem interna; manter `SHLINK_BASE_URL` apontando para a API de gestão.
3. Instalar as dependências Composer, incluindo `endroid/qr-code`, sem executar `composer update` amplo em produção.
4. Publicar a imagem/artefato da branch, rodar migrations somente se o diff aprovado exigir, limpar caches e validar `healthz`, readiness, login, criação premium, analytics, QR e slug inexistente.
5. Observar logs de `shlink.redirect_unavailable`, latência, 502, 404, conexões do worker e uso de memória durante a janela.
6. Se o fallback continuar recursando ou o painel saturar, reverter o artefato e restaurar a configuração anterior; não mascarar o problema com timeout alto.

Nenhum desses passos foi executado automaticamente. O acesso root deve ser removido do servidor ao finalizar a janela de manutenção, caso não seja necessário para a próxima etapa.

## 7. Próximas entregas para competir por categoria

A fundação agora permite evoluir por valor, não por quantidade de telas. A próxima ordem recomendada é implementar runtime HTTP com múltiplos workers e worker/scheduler explícitos; tornar o webhook Stripe idempotente com tabela de eventos; criar exportação CSV e agregação diária de analytics; adicionar pixels/retargeting com consentimento; criar QR com identidade visual e download; introduzir workspaces, membros, papéis e limites por plano; e construir white-label para agências.

A expansão comercial deve vir depois de um staging saudável. O produto inicial pode ser vendido com três degraus: **Free** para provar o fluxo, **Pro** para campanhas, domínio, QR e analytics, e **Agency** para workspaces, membros, white-label, limites maiores e suporte. A métrica central não é número de links criados; é **visita útil por campanha ativa**, complementada por retenção de contas, conversão Free→Pro e taxa de links com UTM configurada.

## 8. Limitações conhecidas

O pacote não implementa ainda edição de destino, exportação CSV, agregação histórica própria, múltiplos workers, fila real, scheduler dentro do Compose, webhook Stripe idempotente por tabela, consentimento de pixels, workspaces ou deploy. Também não altera a configuração do servidor de produção. O objetivo desta entrega é transformar o núcleo em algo confiável e comercialmente demonstrável, não declarar que o produto já venceu o mercado antes de medir uso real.
