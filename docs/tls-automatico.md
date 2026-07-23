# TLS automatico para dominios customizados

## Como o certificado e emitido

O painel **nao** emite certificados TLS. A emissao e feita pelo proxy reverso
publico (Caddy ou Traefik) que fica na frente do motor Shlink, usando
Lets Encrypt automaticamente para qualquer host que resolva para o IP do
servidor.

Fluxo:

1. O usuario registra o dominio no painel e aponta o DNS para
   `PANEL_CUSTOM_DOMAIN_DNS_TARGET`.
2. `DomainController::verify` confirma o DNS e chama o Shlink.
3. Na primeira requisicao HTTPS, o proxy reverso solicita o certificado
   ao Lets Encrypt via HTTP-01 e comeca a servir HTTPS.
4. O comando `panel:tls:refresh` (agendado a cada 15 minutos em
   `routes/console.php`) sonda `https://<dominio>/` e atualiza
   `tls_status` (`pending`, `active`, `error`) no `CustomerDomain`.
5. O botao **Testar HTTPS** em `/domains` roda a mesma sonda sob demanda.

## Requisitos no proxy reverso

- Escutar 80 e 443 no IP publico apontado pelo `PANEL_CUSTOM_DOMAIN_DNS_TARGET`.
- Habilitar auto-HTTPS/on-demand TLS (Caddy: `on_demand_tls`; Traefik:
  `certResolvers.letsencrypt`).
- Encaminhar o host recebido para o container `shlink` na porta 8080.
- Nao interceptar `PANEL_HOST`, que continua servindo o painel Laravel.

O deploy real do proxy reverso vive no PR seguinte deste backlog
(observabilidade e docker-compose de producao).

## Como o painel reage a erros de TLS

- `tls_status = pending`: sonda encontrou erro de SSL/handshake. Provavel
  causa: Lets Encrypt ainda emitindo o certificado. Aguardar alguns
  minutos.
- `tls_status = error`: sonda falhou por outro motivo (DNS mudou, proxy
  fora do ar). O detalhe fica em `tls_last_error` e aparece na tela
  `/domains`.
- `tls_status = active`: HTTPS respondendo. O selo aparece em azul no
  painel.
