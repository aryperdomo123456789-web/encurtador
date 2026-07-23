# Billing Stripe (PR #12)

Fluxo de assinatura Premium do painel Shlink usando Stripe Checkout e Billing Portal.
A fonte da verdade do estado do plano e o webhook `billing/webhook`; a UI de
`/billing` apenas exibe o que o webhook ja gravou.

## Variaveis de ambiente

Em `panel/laravel/.env`:

```
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_PREMIUM_PRICE_ID=price_...
```

Chaves de teste ficam em https://dashboard.stripe.com/test/apikeys.
Nunca commitar valores reais; `.env` esta no `.gitignore`.

## Rotas

| Metodo | Rota | Descricao |
|---|---|---|
| GET  | `/billing` | Tela de planos, status atual, botao Assinar/Gerenciar |
| POST | `/billing/checkout` | Cria Checkout Session e redireciona para o Stripe |
| POST | `/billing/portal` | Redireciona para o Billing Portal do cliente |
| GET  | `/billing/success` | Callback pos-pagamento, exibe aviso |
| GET  | `/billing/cancel` | Callback de cancelamento do checkout |
| POST | `/billing/webhook` | Webhook Stripe (fora do CSRF, HMAC) |

## Eventos tratados

- `checkout.session.completed` -> ativa Premium, grava `stripe_customer_id` e `stripe_subscription_id`
- `customer.subscription.updated` -> promove Premium se `active`/`trialing`, rebaixa se `canceled`/`unpaid`
- `customer.subscription.deleted` -> rebaixa para Free
- `invoice.payment_failed` -> apenas loga (Stripe reprocessa)

Idempotencia: cada evento e gravado em `subscriptions.stripe_event_id` e
duplicatas sao ignoradas.

## Como testar localmente

1. Instale `stripe/stripe-cli`.
2. `stripe listen --forward-to https://me.vr766.com/billing/webhook`
3. Copie o `whsec_...` mostrado para `STRIPE_WEBHOOK_SECRET`.
4. `stripe trigger checkout.session.completed` para simular.

## Modelo de dados

- `plans.stripe_price_id` -> Price recorrente no Stripe.
- `users.stripe_customer_id` -> Customer criado sob demanda.
- `subscriptions.stripe_subscription_id` / `stripe_event_id` -> rastreio + idempotencia.

## Gate premium

`User::isPremium()` continua sendo a unica fonte de gating para
custom slug, dominio proprio e cota ilimitada. O webhook e o unico
componente autorizado a manter o estado de assinatura em `subscriptions`
e `stripe_customer_id`.
