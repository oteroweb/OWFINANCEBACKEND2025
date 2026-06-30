<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Entities\Jar;
use App\Models\Entities\Category;

/**
 * Módulo: Jars (Cántaros)
 * Cubre: CRUD, bulk-sync, balance, ajuste, retiros, transferencias,
 *        overrides mensuales, savings teórico, percent invariant.
 */
class JarsFullTest extends TestCase
{
    use RefreshDatabase;

    // ── CRUD básico ──────────────────────────────────────────────────────────

    public function test_jar_crud_flow()
    {
        $res = $this->postJson('/api/v1/jars/', [
            'name'    => 'Necesidades',
            'percent' => 55.0,
            'type'    => 'percent',
            'active'  => 1,
        ]);
        $res->assertStatus(200)->assertJson(['status' => 'OK']);
        $id = $res->json('data.id');

        $this->getJson('/api/v1/jars/' . $id)->assertStatus(200)
            ->assertJsonPath('data.name', 'Necesidades');

        $this->putJson('/api/v1/jars/' . $id, ['name' => 'Necesidades básicas', 'percent' => 55.0])
            ->assertStatus(200);

        $this->getJson('/api/v1/jars/active')->assertStatus(200);
        $this->getJson('/api/v1/jars/all')->assertStatus(200);

        $this->patchJson('/api/v1/jars/' . $id . '/status')->assertStatus(200);
        $this->deleteJson('/api/v1/jars/' . $id)->assertStatus(200);
    }

    // ── Invariant: suma de % no puede superar 100 ─────────────────────────

    public function test_percent_sum_invariant_blocks_over_100_percent()
    {
        // Crear jar con 70%
        $j1 = $this->postJson('/api/v1/jars/', ['name' => 'A', 'percent' => 70.0, 'type' => 'percent', 'active' => 1]);
        $j1->assertStatus(200);

        // Intentar crear otro con 40% (total 110) → debe fallar
        $j2 = $this->postJson('/api/v1/jars/', ['name' => 'B', 'percent' => 40.0, 'type' => 'percent', 'active' => 1]);
        $j2->assertStatus(422);
    }

    public function test_percent_sum_skipped_when_deactivating_jar()
    {
        // 2 jars con 50% cada uno → total = 100%, OK
        $j1 = $this->postJson('/api/v1/jars/', ['name' => 'X', 'percent' => 50.0, 'type' => 'percent', 'active' => 1]);
        $id1 = $j1->json('data.id');
        $this->postJson('/api/v1/jars/', ['name' => 'Y', 'percent' => 50.0, 'type' => 'percent', 'active' => 1]);

        // Desactivar el primero — no debe fallar por %
        $this->patchJson('/api/v1/jars/' . $id1 . '/status')->assertStatus(200);
    }

    // ── Bulk sync ────────────────────────────────────────────────────────────

    public function test_bulk_sync_creates_and_updates_jars()
    {
        $res = $this->postJson('/api/v1/jars/bulk-sync', [
            'jars' => [
                ['name' => 'Necesidades', 'percent' => 55.0, 'type' => 'percent', 'allow_negative_balance' => false, 'reset_cycle' => 'none', 'reset_cycle_day' => 1],
                ['name' => 'Diversión',   'percent' => 10.0, 'type' => 'percent', 'allow_negative_balance' => false, 'reset_cycle' => 'none', 'reset_cycle_day' => 1],
                ['name' => 'Ahorro',      'percent' => 10.0, 'type' => 'percent', 'allow_negative_balance' => false, 'reset_cycle' => 'none', 'reset_cycle_day' => 1],
            ],
        ]);

        $res->assertStatus(200)->assertJson(['status' => 'OK']);
        $this->assertGreaterThanOrEqual(3, Jar::count());
    }

    private function makeJar(string $name = 'TestJar', float $percent = 20.0): int
    {
        $res = $this->postJson('/api/v1/jars/', [
            'name'    => $name,
            'percent' => $percent,
            'type'    => 'percent',
            'active'  => 1,
        ]);
        $res->assertStatus(200);
        return $res->json('data.id');
    }

    // ── Balance y ajuste ──────────────────────────────────────────────────────

    public function test_jar_balance_endpoint_returns_data()
    {
        $id = $this->makeJar();

        $res = $this->getJson('/api/v1/jars/' . $id . '/balance');
        $res->assertStatus(200);
    }

    public function test_jar_all_balances_returns_all_active_jars()
    {
        $this->makeJar('J1', 5);
        $this->makeJar('J2', 5);
        $this->makeJar('J3', 5);

        $res = $this->getJson('/api/v1/jars/all-balances');
        $res->assertStatus(200);
    }

    public function test_jar_adjust_balance()
    {
        $id = $this->makeJar();

        $res = $this->postJson('/api/v1/jars/' . $id . '/adjust', [
            'target_balance' => 500.00,
            'description'    => 'Ajuste manual de prueba',
        ]);

        $res->assertStatus(200);
    }

    public function test_jar_adjustment_history()
    {
        $id = $this->makeJar();

        $res = $this->getJson('/api/v1/jars/' . $id . '/adjustments');
        $res->assertStatus(200);
    }

    // ── Retiros ───────────────────────────────────────────────────────────────

    public function test_jar_withdrawal()
    {
        $id = $this->makeJar();
        // Fund the jar: set target_balance=500 so withdrawal can proceed
        $this->postJson('/api/v1/jars/' . $id . '/adjust', [
            'target_balance' => 500.00,
            'reason'         => 'Fondeo para test',
        ])->assertStatus(200);

        $res = $this->postJson('/api/v1/jars/' . $id . '/withdraw', [
            'amount' => 200.00,
            'note'   => 'Gasto de emergencia',
        ]);

        $res->assertStatus(200);
    }

    public function test_jar_withdrawal_list()
    {
        $id = $this->makeJar();

        $this->getJson('/api/v1/jars/' . $id . '/withdrawals')->assertStatus(200);
    }

    // ── Transferencias entre cántaros ─────────────────────────────────────────

    public function test_jar_transfer_between_jars()
    {
        $j1 = $this->makeJar('Origen',  30);
        $j2 = $this->makeJar('Destino', 20);
        // Fund j1: set target_balance=500 so transfer can proceed
        $this->postJson('/api/v1/jars/' . $j1 . '/adjust', [
            'target_balance' => 500.00,
            'reason'         => 'Fondeo para test',
        ])->assertStatus(200);

        // Route {id} = destination jar; from_jar_id in body = source jar
        $res = $this->postJson('/api/v1/jars/' . $j2 . '/transfer', [
            'from_jar_id' => $j1,
            'amount'      => 150.00,
            'description' => 'Reasignación',
        ]);

        $res->assertStatus(200);
    }

    public function test_jar_transfer_list()
    {
        $id = $this->makeJar();

        $this->getJson('/api/v1/jars/' . $id . '/transfers')->assertStatus(200);
    }

    // ── Settings y overrides ──────────────────────────────────────────────────

    public function test_jar_settings_read_and_write()
    {
        $this->getJson('/api/v1/jars/settings')->assertStatus(200);

        $this->putJson('/api/v1/jars/settings', [
            'distribute_income' => true,
            'income_base'       => 'net',
        ])->assertStatus(200);
    }

    public function test_jar_monthly_override_upsert_and_delete()
    {
        $jar = (object)['id' => $this->makeJar()];

        $month = now()->format('Y-m-01');

        // Upsert override mensual — el controller espera 'month' como date Y-m-d
        $this->putJson('/api/v1/jars/' . $jar->id . '/override', [
            'month'   => $month,
            'percent' => 25.0,
        ])->assertStatus(200);

        // Listar
        $this->getJson('/api/v1/jars/overrides/month?month=' . $month)
            ->assertStatus(200);

        // Borrar override
        $this->deleteJson('/api/v1/jars/' . $jar->id . '/override?month=' . $month)
            ->assertStatus(200);
    }

    // ── Categorías asignadas a cántaro ────────────────────────────────────────

    public function test_jar_set_categories()
    {
        $jarId = $this->makeJar();
        $jar   = (object)['id' => $jarId];
        $cat1 = Category::factory()->create(['name' => 'Alimentos']);
        $cat2 = Category::factory()->create(['name' => 'Transporte']);

        $res = $this->postJson('/api/v1/jars/' . $jar->id . '/categories', [
            'category_ids' => [$cat1->id, $cat2->id],
        ]);

        $res->assertStatus(200)->assertJson(['status' => 'OK']);
    }

    // ── Income summary y savings ──────────────────────────────────────────────

    public function test_jar_income_summary()
    {
        $this->getJson('/api/v1/jars/income-summary')->assertStatus(200);
    }

    public function test_jar_theoretical_savings()
    {
        $this->getJson('/api/v1/jars/theoretical-savings')->assertStatus(200);
        $this->getJson('/api/v1/jars/theoretical-savings/accumulated')->assertStatus(200);
    }
}
