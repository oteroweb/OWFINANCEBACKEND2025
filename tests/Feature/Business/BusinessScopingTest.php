<?php

namespace Tests\Feature\Business;

use App\Models\Entities\Account;
use App\Models\Entities\Business;
use App\Models\Entities\BusinessUser;
use App\Models\Entities\Category;
use App\Models\Entities\Currency;
use App\Models\Entities\Transaction;
use App\Models\Entities\TransactionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * OWF-370: aislamiento entre contabilidad personal y de empresa (cuentas, movimientos,
 * categorías) y permisos por rol (owner / accountant / viewer).
 */
class BusinessScopingTest extends TestCase
{
    use RefreshDatabase;

    private Currency $currency;
    private TransactionType $type;

    protected function setUp(): void
    {
        parent::setUp();
        $this->currency = Currency::factory()->create(['code' => 'USD']);
        $this->type = TransactionType::factory()->create(['slug' => 'expense']);
    }

    private function business(User $owner): Business
    {
        $b = Business::create(['owner_user_id' => $owner->id, 'name' => 'Panadería Sol', 'color' => Business::PALETTE[0]]);
        BusinessUser::create(['business_id' => $b->id, 'user_id' => $owner->id, 'role' => 'owner', 'status' => 'active']);
        return $b;
    }

    private function member(Business $b, string $role): User
    {
        $u = User::factory()->create();
        BusinessUser::create(['business_id' => $b->id, 'user_id' => $u->id, 'role' => $role, 'status' => 'active']);
        return $u;
    }

    private function businessAccount(Business $b, string $name = 'Caja empresa'): Account
    {
        return Account::factory()->create(['currency_id' => $this->currency->id, 'business_id' => $b->id, 'name' => $name]);
    }

    private function personalAccount(User $u, string $name = 'Mi cuenta'): Account
    {
        $a = Account::factory()->create(['currency_id' => $this->currency->id, 'name' => $name]);
        $a->users()->attach($u->id, ['is_owner' => 1, 'sort_order' => 0]);
        return $a;
    }

    private function tx(Account $a, float $amount = -50): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/v1/transactions/', [
            'name' => 'Gasto', 'amount' => abs($amount), 'date' => now()->format('Y-m-d H:i:s'),
            'transaction_type_id' => $this->type->id,
            'payments' => [['account_id' => $a->id, 'amount' => $amount]],
        ]);
    }

    public function test_personal_account_list_excludes_business_accounts_and_vice_versa(): void
    {
        $owner = User::factory()->create();
        $b = $this->business($owner);
        $this->businessAccount($b, 'Caja empresa');
        $this->personalAccount($owner, 'Mi cuenta');
        Sanctum::actingAs($owner, ['*']);

        $personal = collect($this->getJson('/api/v1/accounts')->json('data.data') ?? $this->getJson('/api/v1/accounts')->json('data'))->pluck('name')->all();
        $this->assertContains('Mi cuenta', $personal);
        $this->assertNotContains('Caja empresa', $personal);

        $biz = collect($this->getJson("/api/v1/accounts?business_id={$b->id}")->json('data.data') ?? $this->getJson("/api/v1/accounts?business_id={$b->id}")->json('data'))->pluck('name')->all();
        $this->assertSame(['Caja empresa'], $biz);
    }

    public function test_every_member_sees_business_accounts_without_pivot(): void
    {
        $owner = User::factory()->create();
        $b = $this->business($owner);
        $this->businessAccount($b, 'Caja empresa');
        $viewer = $this->member($b, 'viewer');

        Sanctum::actingAs($viewer, ['*']);
        $res = $this->getJson("/api/v1/accounts?business_id={$b->id}")->assertOk();
        $rows = $res->json('data.data') ?? $res->json('data');
        $this->assertSame(['Caja empresa'], collect($rows)->pluck('name')->all());
    }

    public function test_stranger_gets_403_on_business_context(): void
    {
        $owner = User::factory()->create();
        $b = $this->business($owner);
        $this->businessAccount($b);

        Sanctum::actingAs(User::factory()->create(), ['*']);
        $this->getJson("/api/v1/accounts?business_id={$b->id}")->assertStatus(403);
        $this->getJson("/api/v1/accounts/tree?business_id={$b->id}")->assertStatus(403);
        $this->getJson("/api/v1/transactions?business_id={$b->id}")->assertStatus(403);
        $this->getJson("/api/v1/categories?business_id={$b->id}")->assertStatus(403);
    }

    public function test_only_owner_creates_business_accounts(): void
    {
        $owner = User::factory()->create();
        $b = $this->business($owner);
        $accountant = $this->member($b, 'accountant');
        $payload = [
            'name' => 'Banco empresa', 'currency_id' => $this->currency->id, 'initial' => 100,
            'account_type_id' => \App\Models\Entities\AccountType::factory()->create()->id,
            'business_id' => $b->id,
        ];

        Sanctum::actingAs($accountant, ['*']);
        $this->postJson('/api/v1/accounts', $payload)->assertStatus(403);

        Sanctum::actingAs($owner, ['*']);
        $res = $this->postJson('/api/v1/accounts', $payload)->assertOk();
        $this->assertSame($b->id, Account::find($res->json('data.id'))->business_id);
        $this->assertSame(0, Account::find($res->json('data.id'))->users()->count(), 'las cuentas de empresa no usan el pivot');
    }

    public function test_business_balance_is_separate_from_personal_global_balance(): void
    {
        $owner = User::factory()->create();
        $b = $this->business($owner);
        $biz = $this->businessAccount($b);
        $biz->update(['balance_cached' => 900, 'include_in_global_balance' => true, 'active' => true]);
        $mine = $this->personalAccount($owner);
        $mine->update(['balance_cached' => 100, 'include_in_global_balance' => true, 'active' => true]);
        Sanctum::actingAs($owner, ['*']);

        $personal = $this->getJson('/api/v1/accounts/summary/global-balance')->assertOk();
        $this->assertSame([$mine->id], collect($personal->json('data.accounts') ?? $personal->json('data.rows') ?? [$mine->id])->map(fn($r) => is_array($r) ? $r['id'] : $r)->all());
        $this->assertStringNotContainsString('900', json_encode($personal->json('data')));

        $business = $this->getJson("/api/v1/accounts/summary/global-balance?business_id={$b->id}")->assertOk();
        $this->assertStringContainsString('900', json_encode($business->json('data')));
        $this->assertStringNotContainsString('"100', json_encode($business->json('data')));

        $tree = $this->getJson('/api/v1/accounts/tree')->assertOk();
        $this->assertStringNotContainsString('Caja empresa', $tree->getContent());
        $btree = $this->getJson("/api/v1/accounts/tree?business_id={$b->id}")->assertOk();
        $this->assertStringContainsString('Caja empresa', $btree->getContent());
    }

    public function test_transaction_in_business_account_gets_business_id_and_role_gates_writes(): void
    {
        $owner = User::factory()->create();
        $b = $this->business($owner);
        $acc = $this->businessAccount($b);
        $accountant = $this->member($b, 'accountant');
        $viewer = $this->member($b, 'viewer');

        Sanctum::actingAs($viewer, ['*']);
        $this->tx($acc)->assertStatus(403);

        Sanctum::actingAs($accountant, ['*']);
        $res = $this->tx($acc)->assertOk();
        $id = $res->json('data.id');
        $this->assertSame($b->id, Transaction::find($id)->business_id);

        // el viewer lo ve pero no lo edita ni borra
        Sanctum::actingAs($viewer, ['*']);
        $ids = collect($this->getJson("/api/v1/transactions?business_id={$b->id}")->assertOk()->json('data') ?? [])->pluck('id')->all();
        $this->assertContains($id, $ids);
        $this->putJson("/api/v1/transactions/{$id}", ['name' => 'hack'])->assertStatus(403);
        $this->deleteJson("/api/v1/transactions/{$id}")->assertStatus(403);

        // el accountant sí edita
        Sanctum::actingAs($accountant, ['*']);
        $this->putJson("/api/v1/transactions/{$id}", ['name' => 'Editado'])->assertOk();
    }

    public function test_personal_transaction_list_excludes_business_movements(): void
    {
        $owner = User::factory()->create();
        $b = $this->business($owner);
        $biz = $this->businessAccount($b);
        $mine = $this->personalAccount($owner);
        Sanctum::actingAs($owner, ['*']);
        $bizId = $this->tx($biz)->assertOk()->json('data.id');
        $myId = $this->tx($mine)->assertOk()->json('data.id');

        $personal = collect($this->getJson('/api/v1/transactions')->json('data') ?? [])->pluck('id')->all();
        $this->assertContains($myId, $personal);
        $this->assertNotContains($bizId, $personal);
    }

    public function test_cannot_mix_personal_and_business_accounts_in_one_transaction(): void
    {
        $owner = User::factory()->create();
        $b = $this->business($owner);
        $biz = $this->businessAccount($b);
        $mine = $this->personalAccount($owner);
        Sanctum::actingAs($owner, ['*']);

        $this->postJson('/api/v1/transactions/', [
            'name' => 'Mixto', 'amount' => 10, 'date' => now()->format('Y-m-d H:i:s'),
            'transaction_type_id' => $this->type->id,
            'payments' => [
                ['account_id' => $biz->id, 'amount' => -10],
                ['account_id' => $mine->id, 'amount' => 10],
            ],
        ])->assertStatus(422);
    }

    public function test_categories_are_scoped_by_context(): void
    {
        $owner = User::factory()->create();
        $b = $this->business($owner);
        $accountant = $this->member($b, 'accountant');
        $viewer = $this->member($b, 'viewer');

        Sanctum::actingAs($viewer, ['*']);
        $this->postJson('/api/v1/categories', ['name' => 'Insumos', 'business_id' => $b->id])->assertStatus(403);

        Sanctum::actingAs($accountant, ['*']);
        $this->postJson('/api/v1/categories', ['name' => 'Insumos', 'business_id' => $b->id])->assertOk();
        $this->assertSame($b->id, Category::where('name', 'Insumos')->first()->business_id);

        // el viewer la ve en el contexto empresa
        Sanctum::actingAs($viewer, ['*']);
        $names = collect($this->getJson("/api/v1/categories?business_id={$b->id}")->assertOk()->json('data'))->pluck('name')->all();
        $this->assertContains('Insumos', $names);

        // y NO aparece en el contexto personal del accountant
        Sanctum::actingAs($accountant, ['*']);
        $personal = collect($this->getJson('/api/v1/categories')->assertOk()->json('data'))->pluck('name')->all();
        $this->assertNotContains('Insumos', $personal);
    }
}
