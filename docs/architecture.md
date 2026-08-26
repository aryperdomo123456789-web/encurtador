# Arquitetura SaaS MElink

## Visão geral

A solução foi desenhada em três camadas independentes:

1. `api-shlink.vr766.com`
   - roda apenas o motor de links em Docker;
   - expõe a API REST para o painel;
   - também recebe os acessos de redirecionamento quando o hostname do short link aponta para este host.

2. `me.vr766.com`
   - é o host do painel SaaS;
   - também é o host público de slugs do shortener;
   - o proxy reverso encaminha rotas administrativas para o Laravel e envia os demais caminhos ao Laravel, que usa fallback para repassar slugs ao motor.

3. Banco MariaDB do painel
   - guarda usuários, planos, assinaturas, domínios, quota mensal e espelho operacional dos links.

## Ponto importante de rota

Existe uma restrição prática importante:

- se o mesmo host servir painel e short links no mesmo nível de caminho, rotas como `/login`, `/dashboard` e `/slug` podem colidir;
- por isso, o desenho adotado aqui usa a borda para separar por caminho:
  - rotas administrativas reservadas vão para o Laravel;
  - qualquer outro caminho em `me.vr766.com` vai para o Laravel e o fallback do app repassa ao motor;
  - domínios de clientes encaminhados pela borda também vão diretamente ao motor.

No estado atual do projeto, a regra é fixa: painel em `me.vr766.com`, API em `api-shlink.vr766.com`.

## Fluxo de criação de link gratuito

1. O usuário não autenticado ou do plano `free` solicita a criação do link.
2. O painel confere a cota mensal na tabela `monthly_quota_usage`.
3. Se já tiver 5 links no mês, a criação é bloqueada.
4. O painel gera um slug aleatório.
5. O payload enviado ao motor inclui:
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
3. Se o domínio for novo, o painel registra o domínio no motor.
4. O payload de criação envia:
   - `longUrl`
   - `domain` quando aplicável
   - `customSlug` quando houver
   - `validUntil` apenas se o link não for vitalício
5. O link é persistido no banco do painel e o motor passa a responder aos acessos.

## Domínio próprio

O domínio do cliente deve apontar por CNAME para o hostname público do shortener.

Recomendação operacional:

- o painel recebe o domínio via cadastro;
- o painel valida a existência do CNAME;
- o painel chama a API do motor para registrar o domínio;
- o proxy reverso precisa emitir certificado automaticamente.

### Observação sobre TLS dinâmico

O motor não deve ser o responsável por emitir certificados para domínios de clientes.

O lugar correto para isso é o proxy reverso:

- Traefik com ACME on-demand;
- Caddy com emissão automática;
- ou Nginx + um componente de automação de certificados.

Se o objetivo for escalar para muitos domínios de clientes, Traefik ou Caddy tende a ser a escolha mais simples.

## Métricas

O motor fornece o histórico de visitas via endpoint de visits.

Estratégia recomendada no painel:

1. buscar as visitas do short link;
2. agregar os dados no backend do painel;
3. exibir gráficos de:
   - cliques totais;
   - países/regiões;
   - dispositivos;
   - navegadores;
   - referrers.

Isso evita duplicar lógica analítica que já existe no motor e mantém o painel leve.

## Segurança

- usar `Accept: application/json` em todas as chamadas;
- autenticar com `X-Api-Key`;
- separar a chave do painel da chave de operação manual;
- registrar logs de erro da API no `link_event_log`;
- não expor a API key do motor no frontend.

## Decisões assumidas

- o painel será implementado depois como Laravel ou Node;
- a camada abaixo já serve como base para ambos;
- o motor será o único responsável pelo redirecionamento final;
- o painel só provisiona e consulta dados.

## Matriz de responsabilidades por host

Fonte de verdade para separar o motor de links, o painel SaaS e o site público. Serve como referência para os itens P0 do backlog em `docs/lovable/checklist.md`.

| Host | Responsabilidade | Fora do escopo |
|---|---|---|
| `api-shlink.vr766.com` | Motor de links em Docker; expõe API REST consumida pelo painel; recebe hits de redirecionamento quando o hostname do short link aponta para este host. | Regras de negócio, autenticação de usuários, quota, cobrança, UI. |
| `me.vr766.com` | Host administrativo do painel SaaS em Laravel 12 / PHP 8.3 e, por roteamento de borda, host dos slugs públicos do motor via fallback do app. | Nada fora da topologia oficial; o proxy e o fallback do Laravel separam painel e redirect por caminho. |
| `slug-host.a-definir` | Domínio público alternativo de slugs curtos, caso o projeto decida separar o redirect do host do painel no futuro. | Rotas administrativas do painel. |
| `{cliente}.tld` (CNAME) | Domínio próprio de cliente premium, apontando por CNAME para o hostname público de slugs. TLS emitido pelo proxy reverso (Traefik/Caddy). | Regras de plano; validação de propriedade; UI. |

Regras derivadas:

- rota administrativa **nunca** compete com slugs no mesmo caminho sem um fallback explícito;
- o painel valida o `Host` da requisição via `PANEL_HOST` (`config/panel.php`);
- o motor não emite certificados TLS — quem emite é o proxy reverso;
- o painel é o único que fala com o banco MariaDB do SaaS;
- o motor e o painel não compartilham banco.
