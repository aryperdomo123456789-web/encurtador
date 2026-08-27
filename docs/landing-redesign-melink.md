# Redesign da landing page do MElink

**Status:** implementado localmente, ainda sem publicação  
**Arquivo principal:** `panel/laravel/resources/views/landing.blade.php`  
**Layout alterado:** `panel/laravel/resources/views/layouts/app.blade.php`  
**Asset original:** `panel/laravel/public/assets/melink-hero-team.png`

## Objetivo

Reposicionar a home do MElink de um painel técnico para uma landing de produto SaaS orientada a resultado. O foco da mensagem passa de “encurtar uma URL” para “criar, compartilhar, medir e melhorar campanhas”. A página foi inspirada em padrões públicos de conversão observados em plataformas de links, especialmente a arquitetura de navegação e prova de valor da [landing brasileira da Rebrandly](https://www.rebrandly.com/pt-br), mas não reproduz textos, marcas, logotipos, imagens ou código proprietários.

## Arquitetura de conversão

| Ordem | Seção | Objetivo |
|---:|---|---|
| 1 | Announcement bar | Fixar a promessa em uma frase curta e levar ao produto. |
| 2 | Navegação | Dar acesso rápido a Produto, Como funciona, Para quem é, Planos, FAQ, Entrar e Cadastro. |
| 3 | Hero | Explicar a transformação, mostrar o produto visualmente e oferecer dois caminhos: começar ou entender. |
| 4 | Prova de capacidades | Tornar visíveis domínio próprio, QR, analytics e UTM. |
| 5 | Produto | Conectar cada recurso a uma decisão de marketing. |
| 6 | Como funciona | Reduzir a percepção de complexidade em três etapas: criar, compartilhar e aprender. |
| 7 | Módulos | Apresentar Marca, Campanha, Métrica e Escala como uma plataforma única. |
| 8 | Casos de uso | Falar com marketing/tráfego, agências/clientes e creators/negócios. |
| 9 | Planos | Mostrar Free e Premium com valor e limites honestos, sem preço inventado. |
| 10 | Prova conceitual | Reforçar a ideia de que o link é o começo da conversa, não o fim. |
| 11 | FAQ | Tratar objeções sobre encurtador, domínio, UTMs, QR e primeiro passo. |
| 12 | CTA final e rodapé | Fechar com uma ação clara e caminhos de login/cadastro. |

## Direção visual

A paleta combina azul elétrico para ação, índigo para profundidade, amarelo para destaque e superfícies claras para legibilidade. O layout usa cards arredondados, contrastes fortes, bastante espaço negativo e uma seção escura para criar ritmo visual. O hero usa uma imagem original gerada para o MElink, com pessoas não identificáveis, sem logotipo de terceiros e sem texto legível. O card de analytics sobreposto é explicitamente marcado como “Exemplo de campanha” e “Prévia do painel”, evitando apresentar números fictícios como prova social real.

## CTAs e rotas

Os CTAs principais usam as rotas existentes `register` e `login`; nenhum endpoint novo foi criado para a landing. As âncoras internas são `#produto`, `#como-funciona`, `#para-quem`, `#planos` e `#faq`. A navegação operacional do painel deixou de aparecer na home visitante por meio da seção de layout `marketing_page`, mas continua sendo renderizada normalmente nas páginas internas.

## Acessibilidade e qualidade

A página possui um único `h1`, hierarquia contínua de `h2`/`h3`, textos alternativos nas duas imagens, `aria-label` na navegação e descrição no visual hero. O FAQ usa `details`/`summary` nativos e os links de âncora foram verificados contra os IDs existentes. O layout possui breakpoints para tablet e mobile, com CTAs em largura total e grids que colapsam para uma coluna.

## Critérios de aceite

A landing deve retornar HTTP 200 com o host de painel configurado, compilar via `php artisan view:cache`, conter apenas um `h1`, não apresentar referências textuais à Rebrandly, não possuir âncoras internas quebradas e não conter imagens sem `alt`. Os CTAs devem apontar para rotas existentes e a página precisa manter o painel autenticado sem alteração funcional.

## Publicação controlada

Antes de publicar, executar `git diff --check`, `php artisan view:cache`, o smoke test da home e uma verificação visual desktop/mobile. Na produção, confirmar que o asset está dentro do artefato ou volume público, limpar cache de views após o rollout e testar `/`, `/login`, `/register`, `/healthz` e `/health/ready`. A publicação deve ser feita com backup e rollback; não reutilizar o servidor embutido do PHP como solução definitiva de escala, pois a evolução correta é PHP-FPM/Nginx ou servidor HTTP de produção equivalente.
