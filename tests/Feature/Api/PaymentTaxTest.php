<?php

namespace Tests\Feature\Api;

use App\Models\Entities\Account;
use App\Models\Entities\Currency;
use App\Models\Entities\PaymentTransaction;
use App\Models\Entities\PaymentTransactionTax;
use App\Models\Entities\Tax;
use App\Models\Entities\TransactionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * OWF-353: impuesto por fila en "Pago múltiple" — reusa payment_transaction_taxes +
 * taxes (existían desde antes, nunca conectados al flujo real de guardado). Mismo
 * patrón que items.*.tax_id, pero el desglose se reversa desde el monto final
 * (que ya viene con el impuesto horneado, igual que items).
 */
class PaymentTaxTest extends TestCase
{
    use RefreshDatabase;

    private function makeAccount(): Account
    {
        $currency = Currency::factory()->create(['code' => 'USD']);
        return Account::factory()->create(['currency_id' => $currency->id]);
    }

    private function makeType(): TransactionType
    {
        return TransactionType::factory()->create(['slug' => 'expense']);
    }

    public function test_payment_with_tax_id_persists_tax_breakdown(): void
    {
        $account = $this->makeAccount();
        $type = $this->makeType();
        $tax = Tax::factory()->create(['name' => 'IGTF', 'percent' => 3, 'applies_to' => 'payment']);
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        // 100 base + 3% IGTF = 103 final (ya horneado, mismo criterio que items)
        $res = $this->postJson('/api/v1/transactions/', [
            'name' => 'Pago con IGTF', 'amount' => 103.00, 'date' => now()->format('Y-m-d H:i:s'),
            'transaction_type_id' => $type->id,
            'payments' => [['account_id' => $account->id, 'amount' => -103.00, 'tax_id' => $tax->id]],
        ]);

        $res->assertStatus(200)->assertJson(['status' => 'OK']);
        $payment = PaymentTransaction::where('transaction_id', $res->json('data.id'))->first();
        $this->assertNotNull($payment);
        $ptTax = PaymentTransactionTax::where('payment_transaction_id', $payment->id)->first();
        $this->assertNotNull($ptTax);
        $this->assertEquals($tax->id, $ptTax->tax_id);
        $this->assertEquals(3.00, (float) $ptTax->amount);
        $this->assertEquals(3.00, (float) $ptTax->percent);
    }

    public function test_payment_tax_not_applicable_to_payments_returns_422(): void
    {
        $account = $this->makeAccount();
        $type = $this->makeType();
        $tax = Tax::factory()->create(['name' => 'IVA', 'percent' => 16, 'applies_to' => 'item']);
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $res = $this->postJson('/api/v1/transactions/', [
            'name' => 'Pago inválido', 'amount' => 100.00, 'date' => now()->format('Y-m-d H:i:s'),
            'transaction_type_id' => $type->id,
            'payments' => [['account_id' => $account->id, 'amount' => -100.00, 'tax_id' => $tax->id]],
        ]);

        $res->assertStatus(422);
        $this->assertEquals(0, PaymentTransaction::count());
    }

    public function test_payment_without_tax_id_still_works(): void
    {
        $account = $this->makeAccount();
        $type = $this->makeType();
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $res = $this->postJson('/api/v1/transactions/', [
            'name' => 'Pago normal', 'amount' => 50.00, 'date' => now()->format('Y-m-d H:i:s'),
            'transaction_type_id' => $type->id,
            'payments' => [['account_id' => $account->id, 'amount' => -50.00]],
        ]);

        $res->assertStatus(200)->assertJson(['status' => 'OK']);
        $this->assertEquals(0, PaymentTransactionTax::count());
    }

    public function test_update_replaces_tax_breakdown_and_cleans_old_rows(): void
    {
        $account = $this->makeAccount();
        $type = $this->makeType();
        $igtf = Tax::factory()->create(['name' => 'IGTF', 'percent' => 3, 'applies_to' => 'payment']);
        $pagomovil = Tax::factory()->create(['name' => 'Pago Móvil', 'percent' => 0.3, 'applies_to' => 'payment']);
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $created = $this->postJson('/api/v1/transactions/', [
            'name' => 'Pago con impuesto', 'amount' => 103.00, 'date' => now()->format('Y-m-d H:i:s'),
            'transaction_type_id' => $type->id,
            'payments' => [['account_id' => $account->id, 'amount' => -103.00, 'tax_id' => $igtf->id]],
        ]);
        $txId = $created->json('data.id');
        $oldPaymentId = PaymentTransaction::where('transaction_id', $txId)->first()->id;
        $this->assertEquals(1, PaymentTransactionTax::where('payment_transaction_id', $oldPaymentId)->count());

        $res = $this->putJson("/api/v1/transactions/{$txId}", [
            'amount' => 100.30,
            'payments' => [['account_id' => $account->id, 'amount' => -100.30, 'tax_id' => $pagomovil->id]],
        ]);

        $res->assertStatus(200)->assertJson(['status' => 'OK']);
        $this->assertEquals(0, PaymentTransactionTax::where('payment_transaction_id', $oldPaymentId)->count());
        $newPayment = PaymentTransaction::where('transaction_id', $txId)->first();
        $this->assertNotEquals($oldPaymentId, $newPayment->id);
        $newTax = PaymentTransactionTax::where('payment_transaction_id', $newPayment->id)->first();
        $this->assertEquals($pagomovil->id, $newTax->tax_id);
    }
}
