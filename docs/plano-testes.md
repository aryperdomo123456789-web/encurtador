# Plano de Testes do SaaS MElink

Este documento define os testes que precisam existir para considerar o sistema confiável em produção.

## Objetivo

Validar:

- autenticação e autorização;
- criação de links free e premium;
- limite mensal de 5 links;
- expiração de 7 dias no plano free;
- domínio próprio;
- métricas;
- comportamento do proxy;
- erros da API do motor.

## Camadas de teste

### 1. Testes unitários

Validam regras isoladas.

Casos:

- gerar slug aleatório;
- calcular `validUntil` em 7 dias;
- bloquear domínio customizado em link free;
- validar plano premium;
- normalizar domínio.

### Exemplo

```php
public function test_free_links_generate_seven_day_expiration(): void
{
    $provisioner = $this->makeProvisioner();

    $response = $provisioner->createFreeLink(1, 'https://example.com');

    $this->assertArrayHasKey('validUntil', $response);
}
```

### 2. Testes de integração

Validam conversa com o motor e banco.

Casos:

- criar short URL com API key válida;
- registrar domínio;
- buscar visits;
- tratar `401`, `403`, `409`, `422`;
- persistir o link no MariaDB.

### Exemplo

```php
$response = $shlinkClient->createShortUrl([
    'longUrl' => 'https://example.com',
    'domain' => 'me.vr766.com',
    'validUntil' => now()->addDays(7)->toAtomString(),
]);

$this->assertArrayHasKey('shortUrl', $response);
```

### 3. Testes de fluxo do painel

Validam a jornada do usuário.

Casos:

1. registro e login;
2. criação de link free;
3. bloqueio no 6º link do mês;
4. criação de link premium;
5. cadastro de domínio próprio;
6. visualização de métricas.

### Exemplo

```php
public function test_free_user_hits_monthly_limit(): void
{
    // criar 5 links
    // tentar 6º
    // esperar erro claro de limite excedido
}
```

### 4. Testes de rota pública

Validam redirecionamento.

Casos:

- `me.vr766.com/slug` redireciona;
- `links.cliente.com/slug` redireciona;
- rota administrativa não colide com slug;
- 404 e expiração se comportam corretamente.

### 5. Testes de domínio próprio

Casos:

- domínio com CNAME correto;
- domínio sem CNAME;
- domínio já cadastrado;
- domínio desativado.

### Exemplo

```php
public function test_custom_domain_must_have_dns_ready_state(): void
{
    $this->expectException(DomainException::class);

    $this->domainService->ensureRegistered('links.cliente.com');
}
```

### 6. Testes de analytics

Casos:

- total de cliques;
- países;
- devices;
- browsers;
- referrers;
- filtro por intervalo de datas.

### Exemplo

```php
$summary = $analyticsService->summarizeVisits($visits);

$this->assertArrayHasKey('countries', $summary);
$this->assertArrayHasKey('devices', $summary);
```

### 7. Testes de proxy/TLS

Casos:

- `Host` original chega ao motor;
- IP do visitante é preservado;
- certificado é emitido automaticamente;
- domínio novo não derruba o serviço.

## Matriz de cenários críticos

| Cenário | Esperado |
|---|---|
| Free cria 1 link | Sucesso |
| Free cria 6º link no mês | Bloqueio |
| Premium cria slug customizado | Sucesso |
| Premium usa domínio próprio | Sucesso |
| Domínio sem CNAME | Erro claro |
| Link free após 7 dias | Expirado |
| API do motor retorna 409 | Mensagem tratada |
| Visit report | Gráficos corretos |

## Tipos de erro que devem ser testados

- `400` payload inválido;
- `401` API key inválida;
- `403` acesso negado;
- `404` recurso não encontrado;
- `409` conflito de slug;
- `422` validação;
- `429` rate limit;
- `5xx` erro upstream.

## Comandos esperados

### Laravel

```bash
php artisan test
php artisan migrate:fresh --seed
```

### PHP isolado

```bash
php -l panel/php/ShlinkClient.php
php -l panel/php/LinkProvisioner.php
php -l panel/php/AnalyticsService.php
php -l panel/php/DomainService.php
```

## Critério de aceite dos testes

O sistema só entra em produção quando:

- os testes unitários estão verdes;
- os testes de integração validam o fluxo principal;
- os testes de domínio próprio passam;
- os testes de quota free passam;
- o painel não tem regressão nas rotas públicas;
- a documentação está consistente com a implementação.
