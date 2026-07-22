# Arquitetura SaaS Shlink

## Visão geral

A solução foi desenhada em três camadas independentes:

1. `api-shlink.vr766.com`
   - roda apenas o motor Shlink em Docker;
   - expõe a API REST para o painel;
   - também recebe os acessos de redirecionamento quando o hostname do short link aponta para este host.

2. `me.vr766.com`
   - é o domínio público do produto;
   - no desenho ideal, ele atende o usuário final do encurtamento;
   - o painel administrativo deve ficar em um host ou prefixo separado para não competir com rotas de slug.

3. Banco MariaDB do painel
   - guarda usuários, planos, assinaturas, domínios, quota mensal e espelho operacional dos links.

## Ponto importante de rota

Existe uma restrição prática importante:

- se o mesmo host servir painel e short links no mesmo nível de caminho, rotas como `/login`, `/dashboard` e `/slug` podem colidir;
- por isso, o desenho mais seguro é:
  - `me.vr766.com` ou outro host dedicado para os links curtos;
  - `app.me.vr766.com` ou `/admin` para o painel.

Se você quiser insistir em `me.vr766.com` como domínio de slug e também como site principal, o painel precisa ficar fora da árvore de rotas reservada ao Shlink. Na prática, isso significa usar um subdomínio administrativo ou um prefixo fixo bem protegido.

## Fluxo de criação de link gratuito

1. O usuário não autenticado ou do plano `free` solicita a criação do link.
2. O painel confere a cota mensal na tabela `monthly_quota_usage`.
3. Se já tiver 5 links no mês, a criação é bloqueada.
4. O painel gera um slug aleatório.
5. O payload enviado ao Shlink inclui:
   - `longUrl`
   - `domain` igual ao domínio padrão da plataforma
   - `customSlug` com slug aleatório
   - `validUntil` com `agora + 7 dias`
6. O painel grava o resultado em `short_links`.

## Fluxo de criação premium

1. O usuário premium escolhe:
   - slug customizado;
   - domínio próprio;
   - data de expiração opcional ou link vitalício.
2. O painel valida permissões do plano.
3. Se o domínio for novo, o painel registra o domínio no Shlink.
4. O payload de criação envia:
   - `longUrl`
   - `domain` quando aplicável
   - `customSlug` quando houver
   - `validUntil` apenas se o link não for vitalício
5. O link é persistido no banco do painel e o Shlink passa a responder aos acessos.

## Domínio próprio

O domínio do cliente deve apontar por CNAME para o hostname público do shortener.

Recomendação operacional:

- o painel recebe o domínio via cadastro;
- o painel valida a existência do CNAME;
- o painel chama a API do Shlink para registrar o domínio;
- o proxy reverso precisa emitir certificado automaticamente.

### Observação sobre TLS dinâmico

O Shlink não deve ser o responsável por emitir certificados para domínios de clientes.

O lugar correto para isso é o proxy reverso:

- Traefik com ACME on-demand;
- Caddy com emissão automática;
- ou Nginx + um componente de automação de certificados.

Se o objetivo for escalar para muitos domínios de clientes, Traefik ou Caddy tende a ser a escolha mais simples.

## Métricas

O Shlink fornece o histórico de visitas via endpoint de visits.

Estratégia recomendada no painel:

1. buscar as visitas do short link;
2. agregar os dados no backend do painel;
3. exibir gráficos de:
   - cliques totais;
   - países/regiões;
   - dispositivos;
   - navegadores;
   - referrers.

Isso evita duplicar lógica analítica que já existe no Shlink e mantém o painel leve.

## Segurança

- usar `Accept: application/json` em todas as chamadas;
- autenticar com `X-Api-Key`;
- separar a chave do painel da chave de operação manual;
- registrar logs de erro da API no `link_event_log`;
- não expor a API key do Shlink no frontend.

## Decisões assumidas

- o painel será implementado depois como Laravel ou Node;
- a camada abaixo já serve como base para ambos;
- o Shlink será o único responsável pelo redirecionamento final;
- o painel só provisiona e consulta dados.
