# Runtime de produção: PHP-FPM + Nginx

## Objetivo

O painel MElink deixa de usar `php artisan serve` como servidor HTTP de produção e passa a executar PHP-FPM atrás de Nginx no mesmo container do painel. A porta externa permanece `127.0.0.1:8001`, portanto a borda existente continua compatível, mas o processo web passa a ter pool de workers, serving eficiente de arquivos estáticos, limite explícito de corpo e healthcheck nativo.

## Topologia

```text
Internet
  -> proxy/vhost do host me.vr766.com
  -> 127.0.0.1:8001
  -> Nginx :8000 no container panel
  -> PHP-FPM 127.0.0.1:9000
  -> Laravel public/index.php
```

O Nginx entrega arquivos estáticos diretamente e encaminha somente scripts PHP ao FPM. O fallback de slugs continua usando `SHLINK_REDIRECT_BASE_URL` na rede interna, sem alterar o serviço Shlink ou os domínios de clientes.

## Parâmetros

| Variável | Default | Critério |
|---|---:|---|
| `FPM_MAX_CHILDREN` | `12` | Limite de processos simultâneos do pool. Ajustar conforme CPU e memória. |
| `FPM_START_SERVERS` | `3` | Processos iniciais. Deve ser menor ou igual ao máximo. |
| `FPM_MIN_SPARE_SERVERS` | `2` | Mínimo de processos ociosos. |
| `FPM_MAX_SPARE_SERVERS` | `6` | Máximo de processos ociosos. |

O entrypoint rejeita valores não numéricos ou zero. O container executa `php-fpm -t` e `nginx -t` antes de iniciar o processo principal.

## Healthcheck

O Compose consulta `http://127.0.0.1:8000/healthz` a cada 15 segundos, com tolerância inicial de 45 segundos. O endpoint é público, não cria sessão e não retorna mensagens internas de exceção. O readiness continua verificando banco e Shlink e responde `200` somente quando os dois estão operacionais.

O endpoint `GET /health/release` devolve apenas `service`, `release` e `built_at`. Esses valores são lidos dos arquivos `deploy/RELEASE_COMMIT` e `deploy/RELEASE_BUILT_AT`, quando presentes, e passam por validação de formato antes de serem exibidos. O endpoint existe para correlacionar smoke tests, logs e incidentes com o commit realmente aplicado, sem retornar `.env`, versão de infraestrutura ou credenciais.

## Rollout controlado

1. Criar backup do checkout, ambiente, volume de `vendor`, volume de storage e dump MariaDB.
2. Gerar um archive imutável a partir de um SHA específico e registrar `deploy/RELEASE_COMMIT` e `deploy/RELEASE_BUILT_AT`.
3. Construir a imagem com o Dockerfile de FPM e Nginx, quando houver alteração de imagem.
4. Executar `php-fpm -t`, `nginx -t` e `docker compose config` dentro do artefato.
5. Recriar apenas o serviço alterado; não reiniciar `db` nem `shlink` sem motivo explícito.
6. Aguardar o healthcheck ficar saudável.
7. Validar `/healthz`, `/health/ready`, `/health/release`, `/login`, home, fallback, OpenAPI e API Shlink.
8. Conferir `release` contra o SHA promovido e observar logs e reinícios durante pelo menos cinco minutos.

## Rollback

Em caso de health vermelho, loop de restart, erro de conexão FPM ou aumento de 5xx, interromper o rollout e restaurar o snapshot anterior do checkout/volume. A imagem anterior deve ser retaggeada e o serviço `panel` recriado com o Compose anterior. Validar então `/health/release` para confirmar o SHA anterior. Banco e Shlink não devem sofrer rollback automático; migrations novas precisam ser compatíveis e reversíveis ou ter procedimento de restauração documentado.

## Limites conhecidos

PHP-FPM/Nginx no mesmo container melhora o runtime, mas não substitui alta disponibilidade. Para uma régua enterprise ainda são necessários filas e scheduler separados, cache compartilhado, observabilidade central, WAF/CDN, deploy blue-green ou canário, teste periódico de restauração e mais de uma réplica atrás de balanceador.
