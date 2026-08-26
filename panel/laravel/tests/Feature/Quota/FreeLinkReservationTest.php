<?php

namespace Tests\Feature\Quota;

use App\Models\Plan;
use App\Models\User;
use App\Repositories\EloquentFreeLinkQuotaRepository;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class FreeLinkReservationTest extends TestCase
{
    use RefreshDatabase;

    private EloquentFreeLinkQuotaRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        Plan::query()->updateOrCreate(['code' => 'free'], [
            'name' => 'Free',
            'description' => 'Free',
            'is_free' => true,
            'monthly_short_url_limit' => 1,
            'allow_custom_slug' => false,
            'allow_custom_domain' => false,
            'allow_custom_expiration' => false,
            'allow_lifetime_links' => false,
            'is_active' => true,
        ]);

        $this->repository = new EloquentFreeLinkQuotaRepository;
    }

    public function test_reserva_e_liberacao_devolvem_o_slot_mensal(): void
    {
        $user = User::factory()->create();
        $reservationId = $this->repository->reserveFreeLinkCreation($user->id, 1);

        $this->assertDatabaseHas('monthly_quota_usage', [
            'user_id' => $user->id,
            'free_links_created' => 1,
        ]);
        $this->assertDatabaseHas('free_link_reservations', [
            'id' => $reservationId,
            'status' => 'reserved',
        ]);

        $this->repository->releaseFreeLinkCreation($user->id, $reservationId);

        $this->assertDatabaseHas('monthly_quota_usage', [
            'user_id' => $user->id,
            'free_links_created' => 0,
        ]);
        $this->assertDatabaseHas('free_link_reservations', [
            'id' => $reservationId,
            'status' => 'released',
        ]);
    }

    public function test_reserva_rejeita_o_segundo_slot_acima_do_limite(): void
    {
        $user = User::factory()->create();
        $this->repository->reserveFreeLinkCreation($user->id, 1);

        $this->expectException(DomainException::class);
        $this->repository->reserveFreeLinkCreation($user->id, 1);
    }

    public function test_commit_cria_espelho_e_fecha_a_reserva(): void
    {
        $user = User::factory()->create();
        $reservationId = $this->repository->reserveFreeLinkCreation($user->id, 1);

        $this->repository->recordFreeLinkCreation($user->id, [
            'reservationId' => $reservationId,
            'shortCode' => 'free123',
            'shortUrl' => 'https://me.vr766.com/free123',
            'longUrl' => 'https://example.com',
            'domain' => 'me.vr766.com',
            'validUntil' => now()->addDays(7),
            'createdAt' => now(),
            'payload' => ['longUrl' => 'https://example.com'],
            'response' => ['shortCode' => 'free123'],
        ]);

        $this->assertDatabaseHas('short_links', [
            'user_id' => $user->id,
            'shlink_short_code' => 'free123',
            'is_free_link' => true,
        ]);
        $this->assertSame('committed', DB::table('free_link_reservations')->where('id', $reservationId)->value('status'));
    }
}
