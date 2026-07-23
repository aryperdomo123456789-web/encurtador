# Arquitetura SaaS Shlink

## Visão geral

A solução foi desenhada em três camadas independentes:

1. `api-shlink.vr766.com`
   - roda apenas o motor Shlink em Docker;
   - expõe a API REST para o painel;
   - também recebe os acessos de redirecionamento quando o hostname do short link aponta para este host.

2. `me.vr766.com`
   - é o domínio padrão de slugs do shortener;
   - quando um link aponta para `me.vr766.com/{slug}`, o Shlink faz o redirect final;
   - domínios de cliente também apontam por CNAME para a borda que entrega o Shlink.

2. `app.me.vr766.com`
   - é o host administrativo do painel SaaS;
   - atende autenticação, billing, cadastro de domínios e criação de links;
   - não compete com rotas de redirecionamento.

3. Banco MariaDB do painel
   - guarda usuários, planos, assinaturas, domínios, quota mensal e espelho operacional dos links.

## Ponto importante de rota

Existe uma restrição prática importante:

- se o mesmo host servir painel e short links no mesmo nível de caminho, rotas como `/login`, `/dashboard` e `/slug` podem colidir;
- por isso, o desenho mais seguro é:
  - `app.me.vr766.com` para o painel administrativo;
  - `me.vr766.com` para slugs públicos do Shlink;
  - domínios de clientes encaminhados pela borda diretamente ao Shlink.

Se você quiser mover o painel para outro host no futuro, o `app.me.vr766.com` pode ser trocado sem mexer na topologia de slugs.

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

## Matriz de responsabilidades por host

Fonte de verdade para separar o motor Shlink, o painel SaaS e o site público. Serve como referência para os itens P0 do backlog em `docs/lovable/checklist.md`.

| Host | Responsabilidade | Fora do escopo |
|---|---|---|
| `api-shlink.vr766.com` | Motor Shlink em Docker; expõe API REST consumida pelo painel; recebe hits de redirecionamento quando o hostname do short link aponta para este host. | Regras de negócio, autenticação de usuários, quota, cobrança, UI. |
| `app.me.vr766.com` | Host administrativo do painel SaaS em Laravel 12 / PHP 8.3; autenticação; provisionamento e leitura via API do Shlink; regras de plano (free vs premium); persistência em MariaDB do painel. | Redirecionamento de slug; emissão de TLS para domínios de cliente. |
| `me.vr766.com` | Host público de slugs do Shlink. | UI administrativa, cobrança, cadastro de usuário. |
| `slug-host.a-definir` | Domínio público de slugs curtos. Encaminha o request ao Shlink para redirecionamento. | Rotas administrativas do painel. |
| `{cliente}.tld` (CNAME) | Domínio próprio de cliente premium, apontando por CNAME para o hostname público de slugs. TLS emitido pelo proxy reverso (Traefik/Caddy). | Regras de plano; validação de propriedade; UI. |

Regras derivadas:

- rota administrativa **nunca** vive no mesmo host de slugs — evita colisão com short codes;
- o painel valida o `Host` da requisição via `PANEL_HOST` (`config/panel.php`);
- o Shlink não emite certificados TLS — quem emite é o proxy reverso;
- o painel é o único que fala com o banco MariaDB do SaaS;
- o motor Shlink e o painel não compartilham banco.
