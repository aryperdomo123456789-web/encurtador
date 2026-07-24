# TLS automatico para dominios customizados

## Topologia

O deploy oficial usa **aaPanel + Nginx + certbot** no host `me.vr766.com`
(painel) e no upstream do contêiner Shlink. Ver
[`docs/topology.md`](topology.md) para a regra completa.

## Como o certificado e emitido

O painel **nao** emite certificados TLS. A emissao e feita pelo proxy reverso
publico (aaPanel/Nginx + certbot, ou equivalente) que fica na frente do motor
Shlink, usando Lets Encrypt automaticamente para qualquer host que resolva
para o IP do servidor.

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
- Habilitar emissao automatica de certificado on-demand para hosts nao
  pre-configurados (no aaPanel/Nginx isso e feito com certbot + verificacao
  do host contra `GET https://me.vr766.com/api/tls/allow?host={host}`).
- Encaminhar o host recebido para o container `shlink` na porta 8080.
- Nao interceptar `me.vr766.com` nas rotas administrativas listadas em
  `docs/topology.md`; qualquer outro path do host `me.vr766.com` (inclusive
  `/{slug}`) vai direto ao Shlink.

Alternativas historicas como Caddy (`on_demand_tls`) ou Traefik
(`certResolvers.letsencrypt`) fazem o mesmo trabalho conceitual, mas **nao**
sao dependencias do deploy oficial.

## Como o painel reage a erros de TLS

- `tls_status = pending`: sonda encontrou erro de SSL/handshake. Provavel
  causa: Lets Encrypt ainda emitindo o certificado. Aguardar alguns
  minutos.
- `tls_status = error`: sonda falhou por outro motivo (DNS mudou, proxy
  fora do ar). O detalhe fica em `tls_last_error` e aparece na tela
  `/domains`.
- `tls_status = active`: HTTPS respondendo. O selo aparece em azul no
  painel.
