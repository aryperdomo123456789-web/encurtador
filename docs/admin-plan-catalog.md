# Catálogo de planos owner-only do MElink

## Objetivo

O painel administrativo do proprietário passa a concentrar a hipótese comercial inicial do MElink. A área fica disponível em `admin.me.vr766.com/admin/plans`, no mesmo container Laravel já utilizado pelo painel, e não cria um segundo projeto nem altera o motor `api-shlink.vr766.com`.

Os valores abaixo são uma hipótese de lançamento para laboratório e piloto. Eles não representam promessa de disponibilidade, SLA ou preço definitivo.

| Plano | Preço mensal | Domínios próprios | Links/mês | Pool global de cliques/mês | Destaque |
|---|---:|---:|---:|---:|---|
| Free | R$ 0,00 | Apenas `me.vr766.com` | 5 | 1.000 | Ideal para teste rápido |
| Start | R$ 19,90 | 1 | 25 | 5.000 | Para creators e pequenas lojas |
| Pro | R$ 49,90 | 3 | 100 | 25.000 | Para gestores de tráfego e agências |

O valor é armazenado como inteiro em centavos: R$ 19,90 é `1990`. O navegador nunca define o valor que será cobrado.

## Operação no painel

O owner pode criar, editar, ordenar, publicar, destacar e arquivar planos. O arquivamento é lógico (`is_active=false` e `is_public=false`), preservando referências históricas de assinaturas. O plano Free não pode ser arquivado porque é o fallback de segurança.

A tabela administrativa exibe o MRR teórico, os assinantes ativos, os limites e o estado do vínculo Stripe. O MRR é uma projeção simples da soma do preço mensal dos planos associados a assinaturas `active` ou `trialing`; não é receita reconhecida, caixa recebido nem previsão financeira.

## Checkout

O checkout público recebe apenas `plan_id`. O Laravel valida que o plano está ativo, público e pago e então resolve no banco o `stripe_price_id` correspondente. Não é permitido receber `amount` ou `price_id` arbitrário do navegador.

O checkout inclui `plan_id` e `plan_code` em metadata. O webhook resolve o plano pelo `price_id` dos itens da assinatura; preço desconhecido não promove a conta silenciosamente. Assinaturas legadas sem item expandido continuam usando o plano Premium legado como fallback de compatibilidade.

## Stripe

O catálogo local pode ser preparado antes de existir um vínculo Stripe. Um plano pago sem `stripe_price_id` aparece como “Checkout em preparação” e não inicia cobrança. Os IDs `prod_...` e `price_...` são identificadores, não segredos; chaves privadas e segredo de webhook permanecem apenas no ambiente seguro do aaPanel.

A criação automática de Product/Price via API Stripe e o suporte a cobrança anual ainda são etapas seguintes. A primeira versão organiza o catálogo e aceita vínculo mensal explícito para evitar criação silenciosa de preços a cada checkout.

## Limitações honestas da primeira versão

O catálogo já guarda as métricas de links, cliques e domínios e expõe esses valores ao owner e ao usuário. A aplicação existente ainda precisa completar contadores mensais atômicos de cliques, enforcement quantitativo para todos os planos e fluxos de upgrade/downgrade. Esses itens devem ser fechados e testados antes de tratar os limites como contrato comercial definitivo.
