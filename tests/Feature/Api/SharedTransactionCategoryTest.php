<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Entities\Account;
use App\Models\Entities\Category;
use App\Models\Entities\Currency;
use App\Models\Entities\Jar;
use App\Models\Entities\TransactionType;
use App\Models\Entities\SharedTransactionCategory;

/**
 * OWF-326: panel "Gasto compartido" — split de una transacción entre N categorías
 * que deben sumar exactamente el monto total (a diferencia de items[], donde el
 * monto se DERIVA de la suma de líneas).
 */
class SharedTransactionCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function makeAccount(): Account
    {
        $currency = Currency::factory()->create(['code' => 'USD']);
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

    private function baseDate(): string
    {
        return now()->format('Y-m-d H:i:s');
    }

    public function test_shared_categories_sum_matching_amount_persists_rows()
    {
        $account = $this->makeAccount();
        $type    = $this->makeType();
        $food    = $this->makeCategory('Comida');
        $home    = $this->makeCategory('Hogar');

        $payload = [
            'name'                => 'Mercado compartido',
            'amount'              => 100.00,
            'date'                => $this->baseDate(),
            'transaction_type_id' => $type->id,
            'shared_categories'   => [
                ['category_id' => $food->id, 'amount' => 60.00],
                ['category_id' => $home->id, 'amount' => 40.00],
            ],
            'payments' => [
                ['account_id' => $account->id, 'amount' => -100.00],
            ],
        ];

        $res = $this->postJson('/api/v1/transactions/', $payload);

        $res->assertStatus(200)->assertJson(['status' => 'OK']);

        $txId = $res->json('data.id');
        $this->assertEquals(2, SharedTransactionCategory::where('transaction_id', $txId)->count());
        $this->assertDatabaseHas('shared_transaction_categories', [
            'transaction_id' => $txId, 'category_id' => $food->id, 'amount' => 60.00,
        ]);
        $this->assertDatabaseHas('shared_transaction_categories', [
            'transaction_id' => $txId, 'category_id' => $home->id, 'amount' => 40.00,
        ]);
    }

    public function test_shared_categories_sum_mismatch_returns_422()
    {
        $account = $this->makeAccount();
        $type    = $this->makeType();
        $food    = $this->makeCategory('Comida');
        $home    = $this->makeCategory('Hogar');

        $payload = [
            'name'                => 'Mercado compartido',
            'amount'              => 100.00,
            'date'                => $this->baseDate(),
            'transaction_type_id' => $type->id,
            'shared_categories'   => [
                ['category_id' => $food->id, 'amount' => 60.00],
                ['category_id' => $home->id, 'amount' => 30.00],
            ],
            'payments' => [
                ['account_id' => $account->id, 'amount' => -100.00],
            ],
        ];

        $res = $this->postJson('/api/v1/transactions/', $payload);

        $res->assertStatus(422);
        $this->assertEquals(0, SharedTransactionCategory::count());
    }

    public function test_transaction_without_shared_categories_still_works()
    {
        $account = $this->makeAccount();
        $type    = $this->makeType();

        $payload = [
            'name'                => 'Gasto normal',
            'amount'              => 50.00,
            'date'                => $this->baseDate(),
            'transaction_type_id' => $type->id,
            'payments'            => [
                ['account_id' => $account->id, 'amount' => -50.00],
            ],
        ];

        $res = $this->postJson('/api/v1/transactions/', $payload);

        $res->assertStatus(200)->assertJson(['status' => 'OK']);
        $this->assertEquals(0, SharedTransactionCategory::where('transaction_id', $res->json('data.id'))->count());
    }

    public function test_shared_category_without_jar_id_saves_null()
    {
        $account = $this->makeAccount();
        $type    = $this->makeType();
        $food    = $this->makeCategory('Comida');

        $payload = [
            'name'                => 'Gasto sin cántaro',
            'amount'              => 20.00,
            'date'                => $this->baseDate(),
            'transaction_type_id' => $type->id,
            'shared_categories'   => [
                ['category_id' => $food->id, 'amount' => 20.00],
            ],
            'payments' => [
                ['account_id' => $account->id, 'amount' => -20.00],
            ],
        ];

        $res = $this->postJson('/api/v1/transactions/', $payload);

        $res->assertStatus(200);
        $row = SharedTransactionCategory::where('transaction_id', $res->json('data.id'))->first();
        $this->assertNotNull($row);
        $this->assertNull($row->jar_id);
    }

    public function test_shared_category_with_jar_id_persists_it()
    {
        $account = $this->makeAccount();
        $type    = $this->makeType();
        $food    = $this->makeCategory('Comida');
        $jar     = Jar::factory()->create(['name' => 'Necesidades', 'percent' => 50]);

        $payload = [
            'name'                => 'Gasto con cántaro',
            'amount'              => 20.00,
            'date'                => $this->baseDate(),
            'transaction_type_id' => $type->id,
            'shared_categories'   => [
                ['category_id' => $food->id, 'amount' => 20.00, 'jar_id' => $jar->id],
            ],
            'payments' => [
                ['account_id' => $account->id, 'amount' => -20.00],
            ],
        ];

        $res = $this->postJson('/api/v1/transactions/', $payload);

        $res->assertStatus(200);
        $row = SharedTransactionCategory::where('transaction_id', $res->json('data.id'))->first();
        $this->assertEquals($jar->id, $row->jar_id);
    }
}
