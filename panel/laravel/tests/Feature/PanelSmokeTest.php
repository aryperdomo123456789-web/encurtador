<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class PanelSmokeTest extends TestCase
{
    public function test_home_page_is_available(): void
    {
        $this->get('/')
            ->assertStatus(200)
            ->assertSee('Painel Shlink');
    }
}
