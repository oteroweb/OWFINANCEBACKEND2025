<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Entities\AccountTax;
use App\Models\Entities\Account;
use App\Models\Entities\Tax;

class AccountTaxTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_tax_crud_flow()
    {
        $account = Account::factory()->create();
        $tax = Tax::factory()->create();
        $data = [
            'account_id' => $account->id,
            'tax_id' => $tax->id,
            'amount' => 7.00
        ];
        $createResponse = $this->postJson('/api/v1/accounts-taxes/', $data);
        $createResponse->assertStatus(201);
        $id = $createResponse->json('data.id') ?? AccountTax::where($data)->first()->id;

        $findResponse = $this->getJson('/api/v1/accounts-taxes/' . $id);
        $findResponse->assertStatus(200);
        $json = $findResponse->json();
        $this->assertEquals($id, $json['id']);
        $this->assertEquals($account->id, $json['account_id']);
        $this->assertEquals($tax->id, $json['tax_id']);

        $listResponse = $this->getJson('/api/v1/accounts-taxes/');
        $listResponse->assertStatus(200);
        $json = $listResponse->json();
        $this->assertTrue(collect($json)->contains('id', $id));

        $deleteResponse = $this->deleteJson('/api/v1/accounts-taxes/' . $id);
        $this->assertTrue(in_array($deleteResponse->status(), [200, 204]));
        $this->assertSoftDeleted('accounts_taxes', ['id' => $id]);
    }

    public function test_get_all_active_account_taxes()
    {
        $account = Account::factory()->create();
        $tax = Tax::factory()->create();
        AccountTax::factory()->create(['active' => 1, 'account_id' => $account->id, 'tax_id' => $tax->id]);
        AccountTax::factory()->create(['active' => 0, 'account_id' => $account->id, 'tax_id' => $tax->id]);
        $response = $this->getJson('/api/v1/accounts-taxes/active');
        $response->assertStatus(200);
        $json = $response->json();
        if (!empty($json)) {
            foreach ($json as $a) {
                $this->assertArrayHasKey('id', $a);
                $this->assertArrayHasKey('account_id', $a);
                $this->assertArrayHasKey('tax_id', $a);
                $this->assertArrayHasKey('amount', $a);
                $this->assertArrayHasKey('active', $a);
                $this->assertEquals(1, $a['active']);
            }
        }
    }

    public function test_get_account_taxes_with_trashed()
    {
        $account = Account::factory()->create();
        $tax = Tax::factory()->create();
        $accountTax = AccountTax::factory()->create(['account_id' => $account->id, 'tax_id' => $tax->id]);
        $accountTax->delete();
        $response = $this->getJson('/api/v1/accounts-taxes/all');
        $response->assertStatus(200);
        $json = $response->json();
        if (!empty($json)) {
            foreach ($json as $a) {
                $this->assertArrayHasKey('id', $a);
                $this->assertArrayHasKey('account_id', $a);
                $this->assertArrayHasKey('tax_id', $a);
                $this->assertArrayHasKey('amount', $a);
                $this->assertArrayHasKey('active', $a);
            }
            $ids = collect($json)->pluck('id');
            $this->assertTrue($ids->contains($accountTax->id));
        }
    }

    public function test_change_status_account_tax()
    {
        $account = Account::factory()->create();
        $tax = Tax::factory()->create();
        $accountTax = AccountTax::factory()->create(['active' => 1, 'account_id' => $account->id, 'tax_id' => $tax->id]);
        $response = $this->patchJson('/api/v1/accounts-taxes/' . $accountTax->id . '/status');
        $response->assertStatus(200);
        $accountTax->refresh();
        $this->assertEquals(0, $accountTax->active);
    }
}
