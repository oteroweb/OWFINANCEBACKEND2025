<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Entities\Account;
use App\Models\Entities\Category;
use App\Models\Entities\Currency;
use App\Models\Entities\Jar;
use App\Models\Entities\TransactionType;
use App\Models\Entities\UserCurrency;
use App\Models\Entities\ItemTransaction;
use App\Models\Entities\PaymentTransaction;
use App\Models\Entities\Transaction;
use App\Models\User;

/**
 * Simulaciones de transacciones compuestas con ítems por categoría.
 *
 * Escenarios cubiertos:
 *  1. Transacción simple (sin items[]) — regresión, no debe romperse
 *  2. Transacción simple con 1 item genérico — flujo base actual
 *  3. Multi-ítem con categorías distintas (chatarra + hogar)
 *  4. Multi-ítem con cántaros (jar_id) distintos por ítem
 *  5. Multi-ítem con moneda extranjera (tasa explícita)
 *  6. Multi-ítem con moneda extranjera vía tasa almacenada en UserCurrency
 *  7. Multi-ítem + pago dividido en 2 cuentas
 *  8. Multi-ítem + moneda extranjera + pago dividido (caso complejo máximo)
 *  9. Update reemplaza items y preserva categorías nuevas
 * 10. Validación: suma de items no cuadra con amount → 422
 * 11. Validación: category_id inválido → 400
 * 12. Validación: items vacío pero amount ausente → 422
 * 13. Transferencia (2 pagos ± opuestos) sin items — regresión
 */
class CompositeTransactionTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeAccount(?int $currencyId = null): Account
    {
        $currency = $currencyId
            ? Currency::find($currencyId)
            : Currency::factory()->create(['code' => 'USD']);

        return Account::factory()->create(['currency_id' => $currency->id]);
    }

    private function makeType(string $slug = 'expense'): TransactionType
    {
        return TransactionType::factory()->create(['slug' => $slug]);
    }

    private function makeCategory(string $name): Category
    {
        return Category::factory()->create(['name' => $name]);
    }

    private function makeJar(string $name): Jar
    {
        return Jar::factory()->create(['name' => $name, 'percent' => 10]);
    }

    private function baseDate(): string
    {
        return now()->format('Y-m-d H:i:s');
    }

    // ── 1. Transacción simple sin items[] — REGRESIÓN ─────────────────────

    public function test_simple_transaction_without_items_still_works()
    {
        $account = $this->makeAccount();
        $type    = $this->makeType();

        $payload = [
            'name'                => 'Pago simple',
            'amount'              => 200.00,
            'date'                => $this->baseDate(),
            'transaction_type_id' => $type->id,
            'payments'            => [
                ['account_id' => $account->id, 'amount' => -200.00],
            ],
        ];

        $res = $this->postJson('/api/v1/transactions/', $payload);

        $res->assertStatus(200)->assertJson(['status' => 'OK']);

        $txId = $res->json('data.id');
        $this->assertDatabaseHas('transactions', ['id' => $txId, 'amount' => 200.00]);
        $this->assertDatabaseHas('payment_transactions', ['transaction_id' => $txId, 'account_id' => $account->id]);

        // No deben haberse creado ItemTransactions
        $this->assertEquals(0, ItemTransaction::where('transaction_id', $txId)->count());
    }

    // ── 2. Transacción simple con 1 ítem genérico — FLUJO BASE ACTUAL ─────

    public function test_simple_transaction_with_one_generic_item()
    {
        $account  = $this->makeAccount();
        $type     = $this->makeType('income');
        $category = $this->makeCategory('Nómina');

        $payload = [
            'name'                => 'Cobro nómina',
            'date'                => $this->baseDate(),
            'transaction_type_id' => $type->id,
            'items' => [
                ['name' => 'Nómina julio', 'amount' => 5000.00, 'category_id' => $category->id],
            ],
            'payments' => [
                ['account_id' => $account->id, 'amount' => 5000.00],
            ],
        ];

        $res = $this->postJson('/api/v1/transactions/', $payload);

        $res->assertStatus(200)->assertJson(['status' => 'OK']);

        $txId = $res->json('data.id');
        $this->assertDatabaseHas('transactions', ['id' => $txId, 'amount' => 5000.00]);

        $item = ItemTransaction::where('transaction_id', $txId)->first();
        $this->assertNotNull($item);
        $this->assertEquals($category->id, $item->category_id);
        $this->assertEquals(5000.00, $item->amount);
    }

    // ── 3. Multi-ítem con CATEGORÍAS DISTINTAS — CASO PRINCIPAL ───────────

    public function test_multi_item_with_different_categories_per_item()
    {
        $account  = $this->makeAccount();
        $type     = $this->makeType();
        $catFood  = $this->makeCategory('Alimentación');
        $catJunk  = $this->makeCategory('Chatarra');
        $catHome  = $this->makeCategory('Artículos del hogar');

        $payload = [
            'name'                => 'Compra Walmart',
            'date'                => $this->baseDate(),
            'transaction_type_id' => $type->id,
            'items' => [
                ['name' => 'Frutas y verduras', 'amount' => 300.00, 'category_id' => $catFood->id],
                ['name' => 'Snacks y dulces',   'amount' => 150.00, 'category_id' => $catJunk->id],
                ['name' => 'Limpieza',           'amount' => 200.00, 'category_id' => $catHome->id],
            ],
            'payments' => [
                ['account_id' => $account->id, 'amount' => -650.00],
            ],
        ];

        $res = $this->postJson('/api/v1/transactions/', $payload);

        $res->assertStatus(200)->assertJson(['status' => 'OK']);

        $txId = $res->json('data.id');

        // Total derivado correctamente
        $this->assertDatabaseHas('transactions', ['id' => $txId, 'amount' => 650.00]);

        $items = ItemTransaction::where('transaction_id', $txId)->get();
        $this->assertCount(3, $items);

        $byName = $items->keyBy('name');
        $this->assertEquals($catFood->id, $byName['Frutas y verduras']->category_id);
        $this->assertEquals($catJunk->id, $byName['Snacks y dulces']->category_id);
        $this->assertEquals($catHome->id, $byName['Limpieza']->category_id);
    }

    // ── 4. Multi-ítem con CÁNTAROS DISTINTOS por ítem ─────────────────────

    public function test_multi_item_with_different_jars_per_item()
    {
        $account     = $this->makeAccount();
        $type        = $this->makeType();
        $jarNec      = $this->makeJar('Necesidades');
        $jarDiv      = $this->makeJar('Diversión');
        $catAlim     = $this->makeCategory('Alimentos');
        $catEntretenimiento = $this->makeCategory('Entretenimiento');

        $payload = [
            'name'                => 'Salida fin de semana',
            'date'                => $this->baseDate(),
            'transaction_type_id' => $type->id,
            'items' => [
                ['name' => 'Mercado',      'amount' => 400.00, 'category_id' => $catAlim->id,         'jar_id' => $jarNec->id],
                ['name' => 'Cine',         'amount' => 120.00, 'category_id' => $catEntretenimiento->id, 'jar_id' => $jarDiv->id],
            ],
            'payments' => [
                ['account_id' => $account->id, 'amount' => -520.00],
            ],
        ];

        $res = $this->postJson('/api/v1/transactions/', $payload);

        $res->assertStatus(200)->assertJson(['status' => 'OK']);

        $txId  = $res->json('data.id');
        $items = ItemTransaction::where('transaction_id', $txId)->get()->keyBy('name');

        $this->assertEquals($jarNec->id, $items['Mercado']->jar_id);
        $this->assertEquals($jarDiv->id, $items['Cine']->jar_id);
        $this->assertEquals($catAlim->id, $items['Mercado']->category_id);
        $this->assertEquals($catEntretenimiento->id, $items['Cine']->category_id);
    }

    // ── 5. Multi-ítem con MONEDA EXTRANJERA (tasa explícita en payment) ────

    public function test_multi_item_with_explicit_foreign_currency_rate()
    {
        // Cuenta en VES (bolívares)
        $currencyVes = Currency::factory()->create(['code' => 'VES']);
        $account     = Account::factory()->create(['currency_id' => $currencyVes->id]);
        $type        = $this->makeType();
        $catAlim     = $this->makeCategory('Alimentos');
        $catHome     = $this->makeCategory('Hogar');

        // Tasa: 1 USD = 40 VES
        $rate = 40.00;

        // Ítems en moneda base (USD)
        $item1Usd = 100.00;
        $item2Usd = 60.00;
        $totalUsd = 160.00;
        $totalVes = $totalUsd * $rate; // 6400 VES

        $payload = [
            'name'                => 'Compra en bolívares',
            'amount'              => $totalUsd,
            'date'                => $this->baseDate(),
            'transaction_type_id' => $type->id,
            'items' => [
                ['name' => 'Despensa', 'amount' => $item1Usd, 'category_id' => $catAlim->id],
                ['name' => 'Limpieza', 'amount' => $item2Usd, 'category_id' => $catHome->id],
            ],
            'payments' => [
                [
                    'account_id'  => $account->id,
                    'amount'      => -$totalVes,   // monto en VES
                    'rate'        => $rate,         // tasa explícita
                    'is_current'  => true,
                    'is_official' => false,
                ],
            ],
        ];

        $res = $this->postJson('/api/v1/transactions/', $payload);

        $res->assertStatus(200)->assertJson(['status' => 'OK']);

        $txId = $res->json('data.id');

        // Transacción guardada en USD
        $this->assertDatabaseHas('transactions', ['id' => $txId, 'amount' => $totalUsd]);

        // PaymentTransaction guardada en VES con user_currency_id asignado
        $pt = PaymentTransaction::where('transaction_id', $txId)->first();
        $this->assertNotNull($pt);
        $this->assertEquals(-$totalVes, $pt->amount);
        $this->assertNotNull($pt->user_currency_id); // tasa registrada automáticamente

        // UserCurrency persisted con la tasa
        $uc = UserCurrency::find($pt->user_currency_id);
        $this->assertNotNull($uc);
        $this->assertEquals($rate, $uc->current_rate);
        $this->assertTrue($uc->is_current);

        // Ítems correctamente asignados en USD
        $items = ItemTransaction::where('transaction_id', $txId)->get();
        $this->assertCount(2, $items);
        $this->assertEquals($item1Usd, $items->firstWhere('name', 'Despensa')->amount);
        $this->assertEquals($item2Usd, $items->firstWhere('name', 'Limpieza')->amount);
    }

    // ── 6. Multi-ítem con tasa almacenada previamente en UserCurrency ───────

    public function test_multi_item_uses_stored_user_currency_rate_when_no_rate_provided()
    {
        $user        = User::factory()->create();
        $currencyEur = Currency::factory()->create(['code' => 'EUR']);
        $account     = Account::factory()->create(['currency_id' => $currencyEur->id]);
        $type        = $this->makeType();
        $catAlim     = $this->makeCategory('Alimentos');

        // Pre-cargar tasa vigente: 1 USD = 0.92 EUR
        $storedRate = 0.92;
        UserCurrency::create([
            'user_id'      => $user->id,
            'currency_id'  => $currencyEur->id,
            'current_rate' => $storedRate,
            'is_current'   => true,
            'is_official'  => true,
        ]);

        $totalUsd = 200.00;
        $totalEur = round($totalUsd * $storedRate, 2); // 184 EUR

        \Laravel\Sanctum\Sanctum::actingAs($user, ['*']);

        $payload = [
            'name'                => 'Compra en euros sin especificar tasa',
            'amount'              => $totalUsd,
            'date'                => $this->baseDate(),
            'transaction_type_id' => $type->id,
            'items' => [
                ['name' => 'Supermercado', 'amount' => 200.00, 'category_id' => $catAlim->id],
            ],
            'payments' => [
                // No se envía 'rate' — debe resolverse desde UserCurrency
                ['account_id' => $account->id, 'amount' => -$totalEur],
            ],
        ];

        $res = $this->postJson('/api/v1/transactions/', $payload);

        $res->assertStatus(200)->assertJson(['status' => 'OK']);

        $txId = $res->json('data.id');
        $this->assertDatabaseHas('transactions', ['id' => $txId, 'amount' => $totalUsd]);

        $pt = PaymentTransaction::where('transaction_id', $txId)->first();
        $this->assertEquals(-$totalEur, $pt->amount);
    }

    // ── 7. Multi-ítem + PAGO DIVIDIDO EN 2 CUENTAS ────────────────────────

    public function test_multi_item_with_split_payment_across_two_accounts()
    {
        $currencyUsd = Currency::factory()->create(['code' => 'USD']);
        $account1    = Account::factory()->create(['currency_id' => $currencyUsd->id, 'name' => 'BBVA']);
        $account2    = Account::factory()->create(['currency_id' => $currencyUsd->id, 'name' => 'Efectivo']);
        $type        = $this->makeType();
        $catAlim     = $this->makeCategory('Alimentos');
        $catHome     = $this->makeCategory('Hogar');
        $catJunk     = $this->makeCategory('Chatarra');

        // 3 ítems: total $800
        $payload = [
            'name'                => 'Compra grande Walmart',
            'date'                => $this->baseDate(),
            'transaction_type_id' => $type->id,
            'items' => [
                ['name' => 'Despensa',       'amount' => 400.00, 'category_id' => $catAlim->id],
                ['name' => 'Artículos hogar','amount' => 250.00, 'category_id' => $catHome->id],
                ['name' => 'Snacks',         'amount' => 150.00, 'category_id' => $catJunk->id],
            ],
            'payments' => [
                ['account_id' => $account1->id, 'amount' => -500.00], // Tarjeta BBVA
                ['account_id' => $account2->id, 'amount' => -300.00], // Efectivo
            ],
        ];

        $res = $this->postJson('/api/v1/transactions/', $payload);

        $res->assertStatus(200)->assertJson(['status' => 'OK']);

        $txId = $res->json('data.id');

        // Total derivado de ítems = 800
        $this->assertDatabaseHas('transactions', ['id' => $txId, 'amount' => 800.00]);

        // 2 payments con cuentas distintas
        $pts = PaymentTransaction::where('transaction_id', $txId)->get();
        $this->assertCount(2, $pts);

        $byAccount = $pts->keyBy('account_id');
        $this->assertEquals(-500.00, $byAccount[$account1->id]->amount);
        $this->assertEquals(-300.00, $byAccount[$account2->id]->amount);

        // 3 ítems con categorías correctas
        $items = ItemTransaction::where('transaction_id', $txId)->get()->keyBy('name');
        $this->assertCount(3, $items);
        $this->assertEquals($catAlim->id, $items['Despensa']->category_id);
        $this->assertEquals($catHome->id, $items['Artículos hogar']->category_id);
        $this->assertEquals($catJunk->id, $items['Snacks']->category_id);
    }

    // ── 8. CASO COMPLEJO MÁXIMO: multi-ítem + moneda extranjera + 2 cuentas ─

    public function test_multi_item_foreign_currency_split_payment_full_composite()
    {
        $user        = User::factory()->create();
        $currencyUsd = Currency::factory()->create(['code' => 'USD']);
        $currencyVes = Currency::factory()->create(['code' => 'VES']);

        // Cuenta 1 en USD (tarjeta internacional)
        $accountUsd = Account::factory()->create(['currency_id' => $currencyUsd->id]);
        // Cuenta 2 en VES (efectivo local)
        $accountVes = Account::factory()->create(['currency_id' => $currencyVes->id]);

        $type    = $this->makeType();
        $catAlim = $this->makeCategory('Alimentos');
        $catJunk = $this->makeCategory('Chatarra');
        $jarNec  = $this->makeJar('Necesidades');
        $jarDiv  = $this->makeJar('Diversión');

        \Laravel\Sanctum\Sanctum::actingAs($user, ['*']);

        // Tasa VES: 1 USD = 40 VES
        $rateVes = 40.00;

        // Ítems en USD
        $item1Usd = 200.00; // Despensa → jar Necesidades
        $item2Usd = 100.00; // Snacks   → jar Diversión
        $totalUsd = 300.00;

        // Pago: 150 USD en tarjeta + 6000 VES en efectivo (= 150 USD @ 40)
        $payUsd  = -150.00;
        $payVes  = -($totalUsd - 150.00) * $rateVes; // -6000 VES

        $payload = [
            'name'                => 'Compra mixta monedas',
            'amount'              => $totalUsd,
            'date'                => $this->baseDate(),
            'transaction_type_id' => $type->id,
            'items' => [
                [
                    'name'        => 'Despensa',
                    'amount'      => $item1Usd,
                    'category_id' => $catAlim->id,
                    'jar_id'      => $jarNec->id,
                ],
                [
                    'name'        => 'Snacks',
                    'amount'      => $item2Usd,
                    'category_id' => $catJunk->id,
                    'jar_id'      => $jarDiv->id,
                ],
            ],
            'payments' => [
                [
                    'account_id' => $accountUsd->id,
                    'amount'     => $payUsd,
                    // USD/USD rate = 1, no rate needed
                ],
                [
                    'account_id'  => $accountVes->id,
                    'amount'      => $payVes,
                    'rate'        => $rateVes,
                    'is_current'  => true,
                    'is_official' => false,
                ],
            ],
        ];

        $res = $this->postJson('/api/v1/transactions/', $payload);

        $res->assertStatus(200)->assertJson(['status' => 'OK']);

        $txId = $res->json('data.id');

        $this->assertDatabaseHas('transactions', ['id' => $txId, 'amount' => $totalUsd]);

        // Ítems con categorías y cántaros
        $items = ItemTransaction::where('transaction_id', $txId)->get()->keyBy('name');
        $this->assertEquals($catAlim->id, $items['Despensa']->category_id);
        $this->assertEquals($jarNec->id,  $items['Despensa']->jar_id);
        $this->assertEquals($catJunk->id, $items['Snacks']->category_id);
        $this->assertEquals($jarDiv->id,  $items['Snacks']->jar_id);

        // Payments: 2 cuentas, una con tasa VES
        $pts = PaymentTransaction::where('transaction_id', $txId)->get();
        $this->assertCount(2, $pts);

        $ptVes = $pts->firstWhere('account_id', $accountVes->id);
        $this->assertNotNull($ptVes->user_currency_id);

        $uc = UserCurrency::find($ptVes->user_currency_id);
        $this->assertEquals($rateVes, $uc->current_rate);
    }

    // ── 9. UPDATE reemplaza items manteniendo categorías nuevas ──────────────

    public function test_update_replaces_items_with_new_categories()
    {
        $account  = $this->makeAccount();
        $type     = $this->makeType();
        $catOld   = $this->makeCategory('Categoría vieja');
        $catNew1  = $this->makeCategory('Alimentos nueva');
        $catNew2  = $this->makeCategory('Hogar nueva');

        // Crear transacción inicial con 1 ítem
        $create = $this->postJson('/api/v1/transactions/', [
            'name'                => 'Compra inicial',
            'date'                => $this->baseDate(),
            'transaction_type_id' => $type->id,
            'items' => [
                ['name' => 'Ítem único', 'amount' => 500.00, 'category_id' => $catOld->id],
            ],
            'payments' => [
                ['account_id' => $account->id, 'amount' => -500.00],
            ],
        ]);

        $create->assertStatus(200);
        $txId = $create->json('data.id');

        // Verificar ítem original
        $originalItems = ItemTransaction::where('transaction_id', $txId)->get();
        $this->assertCount(1, $originalItems);
        $this->assertEquals($catOld->id, $originalItems->first()->category_id);

        // Update: reemplazar con 2 ítems de categorías distintas
        $update = $this->putJson('/api/v1/transactions/' . $txId, [
            'name'   => 'Compra actualizada',
            'amount' => 700.00,
            'items' => [
                ['name' => 'Mercado',  'amount' => 400.00, 'category_id' => $catNew1->id],
                ['name' => 'Limpieza', 'amount' => 300.00, 'category_id' => $catNew2->id],
            ],
            'payments' => [
                ['account_id' => $account->id, 'amount' => -700.00],
            ],
        ]);

        $update->assertStatus(200)->assertJson(['status' => 'OK']);

        // Ítems originales eliminados (soft delete)
        $this->assertEquals(0, ItemTransaction::where('transaction_id', $txId)
            ->whereNull('deleted_at')
            ->whereIn('name', ['Ítem único'])
            ->count());

        // Nuevos ítems con categorías correctas
        $newItems = ItemTransaction::where('transaction_id', $txId)->whereNull('deleted_at')->get()->keyBy('name');
        $this->assertCount(2, $newItems);
        $this->assertEquals($catNew1->id, $newItems['Mercado']->category_id);
        $this->assertEquals($catNew2->id, $newItems['Limpieza']->category_id);

        // Monto actualizado
        $this->assertDatabaseHas('transactions', ['id' => $txId, 'amount' => 700.00]);
    }

    // ── 10. VALIDACIÓN: suma de items ≠ amount → 422 ──────────────────────

    public function test_validation_items_sum_mismatch_returns_422()
    {
        $account = $this->makeAccount();
        $type    = $this->makeType();
        $cat     = $this->makeCategory('Alimentos');

        $payload = [
            'name'                => 'Compra con descuadre',
            'amount'              => 999.00, // ← no coincide con suma de ítems (300)
            'date'                => $this->baseDate(),
            'transaction_type_id' => $type->id,
            'items' => [
                ['name' => 'Ítem A', 'amount' => 200.00, 'category_id' => $cat->id],
                ['name' => 'Ítem B', 'amount' => 100.00, 'category_id' => $cat->id],
            ],
            'payments' => [
                ['account_id' => $account->id, 'amount' => -300.00],
            ],
        ];

        $res = $this->postJson('/api/v1/transactions/', $payload);

        $res->assertStatus(422)
            ->assertJson(['status' => 'FAILED']);

        // PHP devuelve ints en JSON cuando el valor no tiene decimales — comparar numéricamente
        $this->assertEquals(999, $res->json('data.provided_amount'));
        $this->assertEquals(300, $res->json('data.items_total'));

        // No debe haberse creado ninguna transacción
        $this->assertEquals(0, Transaction::count());
    }

    // ── 11. VALIDACIÓN: category_id inválido → 400 ────────────────────────

    public function test_validation_invalid_category_id_returns_400()
    {
        $account = $this->makeAccount();
        $type    = $this->makeType();

        $payload = [
            'name'                => 'Compra con categoría fantasma',
            'date'                => $this->baseDate(),
            'transaction_type_id' => $type->id,
            'items' => [
                ['name' => 'Ítem', 'amount' => 100.00, 'category_id' => 99999], // no existe
            ],
            'payments' => [
                ['account_id' => $account->id, 'amount' => -100.00],
            ],
        ];

        $res = $this->postJson('/api/v1/transactions/', $payload);

        $res->assertStatus(400)->assertJson(['status' => 'FAILED']);
    }

    // ── 12. VALIDACIÓN: sin items y sin amount → 422 ──────────────────────

    public function test_validation_no_items_and_no_amount_returns_422()
    {
        $account = $this->makeAccount();
        $type    = $this->makeType();

        $payload = [
            'name'                => 'Sin monto ni items',
            'date'                => $this->baseDate(),
            'transaction_type_id' => $type->id,
            // sin 'amount', sin 'items'
            'payments' => [
                ['account_id' => $account->id, 'amount' => -100.00],
            ],
        ];

        $res = $this->postJson('/api/v1/transactions/', $payload);

        $res->assertStatus(422)->assertJson(['status' => 'FAILED']);
    }

    // ── 13. TRANSFERENCIA sin items — REGRESIÓN ────────────────────────────

    public function test_transfer_without_items_still_works()
    {
        $currencyUsd = Currency::factory()->create(['code' => 'USD']);
        $account1    = Account::factory()->create(['currency_id' => $currencyUsd->id]);
        $account2    = Account::factory()->create(['currency_id' => $currencyUsd->id]);
        $type        = $this->makeType('transfer');

        $payload = [
            'name'                => 'Transferencia entre cuentas',
            'amount'              => 1000.00,
            'date'                => $this->baseDate(),
            'transaction_type_id' => $type->id,
            'payments' => [
                ['account_id' => $account1->id, 'amount' => -1000.00],
                ['account_id' => $account2->id, 'amount' =>  1000.00],
            ],
        ];

        $res = $this->postJson('/api/v1/transactions/', $payload);

        $res->assertStatus(200)->assertJson(['status' => 'OK']);

        $txId = $res->json('data.id');
        $pts  = PaymentTransaction::where('transaction_id', $txId)->get();
        $this->assertCount(2, $pts);

        // Verificar que hay exactamente un leg positivo y uno negativo
        $this->assertEquals(1, $pts->filter(fn($p) => $p->amount > 0)->count());
        $this->assertEquals(1, $pts->filter(fn($p) => $p->amount < 0)->count());
    }
}
