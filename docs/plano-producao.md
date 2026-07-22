# Plano de Produção do SaaS Shlink

Este documento é o roteiro oficial para levar o sistema do estado atual até o estado de produção completo.

## Objetivo final

Ao concluir este plano, o produto terá:

- motor Shlink isolado em `api-shlink.vr766.com`;
- painel SaaS em `me.vr766.com` ou subdomínio administrativo separado;
- cadastro, login, assinatura e gestão de links;
- plano gratuito com limite mensal de 5 links e validade de 7 dias;
- plano premium com slug customizado, domínio próprio e validade flexível;
- métricas de visitas com cliques, países, dispositivos, browsers e referrers;
- TLS automático para domínios de clientes no proxy reverso;
- testes e validações suficientes para considerar o sistema pronto para uso real.

## Estado atual do projeto

Já existe:

- stack Docker do Shlink;
- schema MariaDB da camada SaaS;
- documentação de arquitetura;
- camada PHP inicial para consumir a API do Shlink;
- notas técnicas com suposições e decisões.

Ainda falta:

- construir o painel frontend completo;
- implementar autenticação e sessão do usuário;
- conectar o fluxo de planos e assinaturas;
- criar a tela de criação e gestão de links;
- automatizar o cadastro de domínios próprios;
- fechar a configuração do proxy reverso com TLS;
- criar suíte de testes e validação de produção.

## Definição de pronto

O sistema só pode ser considerado 100% pronto quando todos os itens abaixo forem verdadeiros:

1. usuário consegue se cadastrar e autenticar;
2. usuário free cria link com slug aleatório, limite mensal e expiração de 7 dias;
3. usuário premium cria link com slug customizado e expiração opcional;
4. domínio próprio pode ser cadastrado, validado e registrado no Shlink;
5. visitas e métricas aparecem no painel;
6. redirecionamento acontece sem bloquear o usuário final;
7. TLS automático funciona nos domínios dos clientes;
8. a aplicação passa nos testes críticos;
9. backup e logs mínimos estão configurados;
10. o painel não colide com as rotas públicas de slug.

## Fase 0 - Travar arquitetura

### Objetivo

Eliminar ambiguidade antes de codar o painel.

### Decisões obrigatórias

- painel administrativo vai ficar em `app.me.vr766.com` ou em `/admin`;
- domínio de slug vai continuar em `me.vr766.com` ou em domínios de cliente;
- Shlink continua isolado como motor de redirect.

### Resultado esperado

- nenhuma rota administrativa compete com `/{slug}`;
- o proxy está desenhado para encaminhar o host certo para o Shlink.

### Critério de aceite

- documentado e aprovado;
- nenhuma rota crítica do painel usa o mesmo caminho dos slugs.

## Fase 1 - Base do painel Laravel

### Objetivo

Criar a aplicação principal do SaaS.

### Tarefas

1. criar projeto Laravel;
2. configurar banco MariaDB;
3. instalar starter kit de autenticação;
4. configurar layout inicial;
5. ligar `.env` ao Shlink.

### Exemplo de implementação

```bash
composer create-project laravel/laravel me-vr766
php artisan breeze:install
php artisan migrate
```

```php
// config/services.php
'shlink' => [
    'base_url' => env('SHLINK_BASE_URL', 'https://api-shlink.vr766.com'),
    'api_key' => env('SHLINK_API_KEY'),
    'api_version' => 3,
],
```

### Critério de aceite

- login e cadastro funcionando;
- app inicial abre sem erro;
- conexão com banco e variáveis de ambiente funcionando.

## Fase 2 - Estrutura de dados e regras de negócio

### Objetivo

Ligar o schema já criado ao painel.

### Tarefas

1. criar migrations equivalentes ao schema;
2. criar models e relacionamentos;
3. criar serviço de cota mensal;
4. criar serviço de criação de link;
5. gravar auditoria de operações.

### Exemplo de implementação

```php
final class FreeLinkQuotaService
{
    public function canCreateFreeLink(User $user): bool
    {
        return $this->quotaRepository->countFreeLinksForPeriod(
            (string) $user->id,
            now()->startOfMonth()->utc(),
            now()->startOfMonth()->addMonth()->utc()
        ) < 5;
    }
}
```

```php
ShortLink::create([
    'user_id' => $user->id,
    'domain' => 'me.vr766.com',
    'long_url' => $request->long_url,
    'status' => 'queued',
]);
```

### Critério de aceite

- os dados básicos são persistidos corretamente;
- a quota free é calculada no banco;
- o painel consegue saber quem é free, premium e owner.

## Fase 3 - Integração com Shlink

### Objetivo

Criar, consultar e auditar links via API do Shlink.

### Tarefas

1. integrar autenticação `X-Api-Key`;
2. criar link free com `validUntil` de 7 dias;
3. criar link premium com `customSlug`;
4. registrar domínio próprio no Shlink;
5. consultar visitas e estatísticas.

### Exemplo de implementação

```php
$response = $shlinkClient->createShortUrl([
    'longUrl' => $longUrl,
    'domain' => 'me.vr766.com',
    'customSlug' => $slugAleatorio,
    'validUntil' => now()->addDays(7)->toAtomString(),
]);
```

```php
$domainService->ensureRegistered('links.cliente.com');
```

### Critério de aceite

- um link criado no painel aparece no Shlink;
- o link redireciona corretamente;
- a expiração respeita a regra de negócio;
- o domínio de cliente é criado e aparece no Shlink.

## Fase 4 - Painel administrativo

### Objetivo

Entregar as telas que o cliente usa de verdade.

### Telas mínimas

1. login e registro;
2. dashboard com resumo;
3. lista de links;
4. criar link;
5. editar link;
6. domínios;
7. assinaturas;
8. métricas.

### Exemplo de implementação

```php
public function store(CreateLinkRequest $request, LinkProvisioner $provisioner)
{
    $link = $provisioner->provision(
        (string) $request->user()->id,
        $request->string('long_url')->toString(),
        $request->validated()
    );

    return back()->with('status', 'Link criado com sucesso');
}
```

### Critério de aceite

- usuário consegue criar link sem sair do painel;
- usuário visualiza histórico;
- usuário vê status, domínio e validade.

## Fase 5 - Domínio próprio e TLS

### Objetivo

Permitir domínios de clientes com segurança.

### Tarefas

1. validar apontamento DNS CNAME;
2. registrar domínio no Shlink;
3. armazenar o domínio na base do painel;
4. provisionar TLS automático no proxy reverso;
5. tratar domínio suspenso e domínio inválido.

### Exemplo de implementação

```php
if (!dns_get_record('links.cliente.com', DNS_CNAME)) {
    throw new DomainException('O domínio precisa apontar por CNAME para me.vr766.com');
}

$domainService->ensureRegistered('links.cliente.com');
```

### Critério de aceite

- domínio do cliente responde e redireciona;
- certificado é emitido automaticamente;
- painel mostra status claro do domínio.

## Fase 6 - Métricas e analytics

### Objetivo

Exibir dados úteis sem duplicar o motor de analytics do Shlink.

### Tarefas

1. consumir endpoint de visits;
2. agregar por país, device, browser e referrer;
3. renderizar gráficos no painel;
4. permitir filtro por data e por link.

### Exemplo de implementação

```php
$visits = $analyticsService->getShortUrlVisits($shortCode, [
    'startDate' => now()->subDays(7),
    'endDate' => now(),
]);

$summary = $analyticsService->summarizeVisits($visits);
```

### Critério de aceite

- gráficos carregam corretamente;
- filtros funcionam;
- dados batem com os totais do Shlink.

## Fase 7 - Rotas públicas e proxy

### Objetivo

Garantir redirecionamento rápido e isolamento.

### Tarefas

1. configurar proxy reverso do Shlink;
2. encaminhar `Host` e IP real do visitante;
3. validar coexistência de painel e slugs;
4. remover qualquer rota conflitante;
5. documentar a topologia final.

### Exemplo de configuração conceitual

```nginx
server {
    server_name api-shlink.vr766.com;
    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

### Critério de aceite

- o acesso ao slug redireciona em baixa latência;
- o painel não quebra ao acessar URLs públicas;
- domínios de cliente continuam funcionando.

## Fase 8 - Testes e validação final

### Objetivo

Garantir que a entrega esteja pronta para produção.

### Tarefas

1. testes unitários;
2. testes de integração;
3. testes de fluxo de negócio;
4. testes de rotas públicas;
5. testes de domínio próprio;
6. testes de expiração de link;
7. testes de limite mensal;
8. teste de fallback de erro da API.

### Exemplo de teste

```php
public function test_free_user_can_only_create_five_links_per_month(): void
{
    for ($i = 0; $i < 5; $i++) {
        $response = $this->postJson('/links', [
            'long_url' => 'https://example.com/article-' . $i,
        ]);

        $response->assertOk();
    }

    $this->postJson('/links', [
        'long_url' => 'https://example.com/overflow',
    ])->assertStatus(422);
}
```

### Critério de aceite

- pipeline de testes verde;
- cenários críticos aprovados;
- documentação atualizada.

## Fase 9 - Go-live

### Objetivo

Colocar o sistema em produção com segurança.

### Checklist final

- `APP_ENV=production`;
- `APP_DEBUG=false`;
- backup do banco configurado;
- logs e alertas básicos ativos;
- domínio principal resolvendo;
- painel acessível;
- Shlink respondendo;
- TLS automático validado;
- seeds iniciais aplicados.

## Ordem recomendada de execução

1. arquitetura e rotas;
2. Laravel e autenticação;
3. schema e models;
4. integração Shlink;
5. telas do painel;
6. domínio próprio;
7. analytics;
8. proxy e TLS;
9. testes;
10. go-live.

## Resultado esperado

Quando este plano estiver concluído, o sistema estará pronto para operação real de SaaS, com painel, encurtamento, domínio próprio, métricas e isolamento do motor Shlink.
