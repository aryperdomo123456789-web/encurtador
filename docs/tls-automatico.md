# TLS automatico para dominios customizados

## Como o certificado e emitido

O painel **nao** emite certificados TLS. No host atual, o aaPanel/Nginx faz a
camada de borda e cada vhost de dominio proprio aponta direto para o motor
Shlink na porta 8080.

Fluxo:

1. O usuario registra o dominio no painel e aponta o DNS para
   `PANEL_CUSTOM_DOMAIN_DNS_TARGET`.
2. `DomainController::verify` confirma o DNS e chama o Shlink.
3. O vhost do dominio proprio encaminha tudo direto ao Shlink, sem passar
   pelo Laravel.
4. O comando `panel:tls:refresh` (agendado a cada 15 minutos em
   `routes/console.php`) sonda `https://<dominio>/` e atualiza
   `tls_status` (`pending`, `active`, `error`) no `CustomerDomain`.
5. O botao **Testar HTTPS** em `/domains` roda a mesma sonda sob demanda.

## Requisitos no proxy reverso

- Escutar 80 e 443 no IP publico apontado pelo `PANEL_CUSTOM_DOMAIN_DNS_TARGET`.
- Cada dominio cadastrado precisa ter vhost que encaminhe `location /`
  direto para o container `shlink` na porta 8080.
- `me.vr766.com` deve reservar o painel em `/`, `/healthz`, `/health/ready`,
  `/tls/allow`, `/login`, `/links`, `/domains`, `/billing`, `/analytics`,
  `/build/`, `/storage/`, `/favicon.ico`, `/robots.txt` e `/up`.
- O fallback de HTTP para hosts sem vhost dedicado também vai direto ao
  Shlink.

## Como o painel reage a erros de TLS

- `tls_status = pending`: a sonda ainda nao encontrou HTTPS valido.
- `tls_status = error`: a sonda falhou por outro motivo (DNS mudou, proxy
  fora do ar). O detalhe fica em `tls_last_error` e aparece na tela
  `/domains`.
- `tls_status = active`: HTTPS respondendo. O selo aparece em azul no
  painel.
