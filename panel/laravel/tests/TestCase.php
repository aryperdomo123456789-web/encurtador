<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // O middleware permanece ativo em produção; os testes de aplicação
        // exercitam controllers sem depender de um token de sessão real.
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }
}
