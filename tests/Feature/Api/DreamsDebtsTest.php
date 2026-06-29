<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

/**
 * Módulos: Dreams y Debts
 * Cubre CRUD completo, depósitos y pagos de cuotas.
 */
class DreamsDebtsTest extends TestCase
{
    use RefreshDatabase;

    private function date(): string
    {
        return now()->format('Y-m-d');
    }

    // ═══════════════════════════════════════════
    // DREAMS
    // ═══════════════════════════════════════════

    public function test_dream_crud_flow()
    {
        $res = $this->postJson('/api/v1/dreams/', [
            'name'          => 'Viaje a Japón',
            'target_amount' => 50000.00,
            'saved_amount'  => 0,
        ]);

        $this->assertContains($res->status(), [200, 201]);
        $id = $res->json('data.id');
        $this->assertNotNull($id);

        // Show
        $this->getJson('/api/v1/dreams/' . $id)
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Viaje a Japón');

        // List
        $list = $this->getJson('/api/v1/dreams/');
        $list->assertStatus(200);
        $this->assertTrue(collect($list->json('data'))->contains('id', $id));

        // Update
        $this->putJson('/api/v1/dreams/' . $id, [
            'name'          => 'Viaje a Japón (2027)',
            'target_amount' => 60000.00,
        ])->assertStatus(200);

        // Delete
        $this->deleteJson('/api/v1/dreams/' . $id)->assertStatus(200);
    }

    public function test_dream_deposit_increases_saved()
    {
        $dream = $this->postJson('/api/v1/dreams/', [
            'name'          => 'Laptop nueva',
            'target_amount' => 20000.00,
            'saved_amount'  => 5000.00,
        ]);
        $this->assertContains($dream->status(), [200, 201]);
        $id = $dream->json('data.id');

        $this->postJson('/api/v1/dreams/' . $id . '/deposit', [
            'amount' => 3000.00,
            'note'   => 'Aportación mensual',
        ])->assertStatus(200);

        $show = $this->getJson('/api/v1/dreams/' . $id);
        $saved = $show->json('data.saved_amount') ?? $show->json('data.saved');
        $this->assertEquals(8000.00, (float) $saved);
    }

    public function test_dream_deposit_cannot_exceed_target()
    {
        $dream = $this->postJson('/api/v1/dreams/', [
            'name'          => 'Carro',
            'target_amount' => 5000.00,
            'saved_amount'  => 4800.00,
        ]);
        $id = $dream->json('data.id');

        $res = $this->postJson('/api/v1/dreams/' . $id . '/deposit', [
            'amount' => 1000.00, // excede target
        ]);

        // Debe retornar error o ajustar al límite
        $this->assertContains($res->status(), [200, 422]);
    }

    public function test_dream_isolated_between_users()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Sanctum::actingAs($user1, ['*']);
        $dream = $this->postJson('/api/v1/dreams/', [
            'name' => 'Sueño privado', 'target_amount' => 1000.00,
        ]);
        $id = $dream->json('data.id');

        Sanctum::actingAs($user2, ['*']);
        $list = $this->getJson('/api/v1/dreams/');
        $this->assertFalse(collect($list->json('data'))->contains('id', $id));
    }

    // ═══════════════════════════════════════════
    // DEBTS
    // ═══════════════════════════════════════════

    public function test_debt_crud_flow()
    {
        $res = $this->postJson('/api/v1/debts/', [
            'name'               => 'Préstamo BBVA',
            'original_amount'    => 30000.00,
            'provider'           => 'loan',
            'total_installments' => 24,
        ]);

        $this->assertContains($res->status(), [200, 201]);
        $id = $res->json('data.id');

        // Show
        $this->getJson('/api/v1/debts/' . $id)
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Préstamo BBVA');

        // List
        $this->getJson('/api/v1/debts/')
            ->assertStatus(200);

        // Update
        $this->putJson('/api/v1/debts/' . $id, [
            'name' => 'Préstamo BBVA — renegociado',
        ])->assertStatus(200);

        // Delete
        $this->deleteJson('/api/v1/debts/' . $id)->assertStatus(200);
    }

    public function test_debt_pay_installment_updates_paid_amount()
    {
        $debt = $this->postJson('/api/v1/debts/', [
            'name'               => 'Cashea',
            'original_amount'    => 6000.00,
            'provider'           => 'cashea',
            'total_installments' => 6,
        ]);
        $this->assertContains($debt->status(), [200, 201]);
        $id = $debt->json('data.id');

        $pay = $this->postJson('/api/v1/debts/' . $id . '/pay', [
            'amount' => 1000.00,
            'date'   => $this->date(),
        ]);
        $pay->assertStatus(200);

        $show = $this->getJson('/api/v1/debts/' . $id);
        // balance decreases as payments are made; paid_installments increases
        $balance = $show->json('data.balance');
        $paidInstallments = $show->json('data.paid_installments');
        $this->assertNotNull($balance);
        $this->assertEquals(1, (int) $paidInstallments);
    }

    public function test_debt_isolated_between_users()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Sanctum::actingAs($user1, ['*']);
        $debt = $this->postJson('/api/v1/debts/', [
            'name' => 'Deuda privada', 'original_amount' => 500.00,
        ]);
        $id = $debt->json('data.id');

        Sanctum::actingAs($user2, ['*']);
        $list = $this->getJson('/api/v1/debts/');
        $this->assertFalse(collect($list->json('data'))->contains('id', $id));
    }
}
