<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Entities\ItemTransaction;
use App\Models\Entities\Item;
use App\Models\Entities\Transaction;

class ItemTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_item_transaction_crud_flow()
    {
        $tax = \App\Models\Entities\Tax::factory()->create();
        $itemCategory = \App\Models\Entities\ItemCategory::factory()->create();
        $item = Item::factory()->create([
            'tax_id' => $tax->id,
            'item_category_id' => $itemCategory->id,
        ]);
        $transaction = Transaction::factory()->create();
        $data = [
            'item_id' => $item->id,
            'transaction_id' => $transaction->id,
            'quantity' => 2,
            'name' => 'Test ItemTransaction',
            'amount' => 100.00,
            'date' => now()->format('Y-m-d H:i:s'),
        ];
        $createResponse = $this->postJson('/api/v1/item_transactions/', $data);
        $this->assertTrue(in_array($createResponse->status(), [200, 201]));
        $id = $createResponse->json('data.id') ?? ItemTransaction::where($data)->first()->id;

        $findResponse = $this->getJson('/api/v1/item_transactions/' . $id);
        $findResponse->assertStatus(200);
        $json = $findResponse->json();
        $this->assertEquals($id, $json['id']);
        $this->assertEquals($item->id, $json['item_id']);
        $this->assertEquals($transaction->id, $json['transaction_id']);

        $listResponse = $this->getJson('/api/v1/item_transactions/');
        $listResponse->assertStatus(200);
        $json = $listResponse->json();
        $this->assertTrue(collect($json)->contains('id', $id));

        $deleteResponse = $this->deleteJson('/api/v1/item_transactions/' . $id);
        $this->assertTrue(in_array($deleteResponse->status(), [200, 204]));
        $this->assertSoftDeleted('item_transactions', ['id' => $id]);
    }

    public function test_get_all_active_item_transactions()
    {
        $tax = \App\Models\Entities\Tax::factory()->create();
        $itemCategory = \App\Models\Entities\ItemCategory::factory()->create();
        $item = Item::factory()->create(['tax_id' => $tax->id, 'item_category_id' => $itemCategory->id]);
        $transaction = Transaction::factory()->create();
        ItemTransaction::factory()->create(['active' => 1, 'item_id' => $item->id, 'transaction_id' => $transaction->id]);
        ItemTransaction::factory()->create(['active' => 0, 'item_id' => $item->id, 'transaction_id' => $transaction->id]);
        $response = $this->getJson('/api/v1/item_transactions/active');
        $response->assertStatus(200);
        $json = $response->json();
        if (is_array($json) && !empty($json)) {
            foreach ($json as $it) {
                $this->assertArrayHasKey('id', $it);
                $this->assertArrayHasKey('item_id', $it);
                $this->assertArrayHasKey('transaction_id', $it);
                $this->assertArrayHasKey('quantity', $it);
                $this->assertArrayHasKey('active', $it);
                $this->assertEquals(1, $it['active']);
            }
        }
    }

    public function test_get_item_transactions_with_trashed()
    {
        $tax = \App\Models\Entities\Tax::factory()->create();
        $itemCategory = \App\Models\Entities\ItemCategory::factory()->create();
        $item = Item::factory()->create(['tax_id' => $tax->id, 'item_category_id' => $itemCategory->id]);
        $transaction = Transaction::factory()->create();
        $itemTransaction = ItemTransaction::factory()->create(['item_id' => $item->id, 'transaction_id' => $transaction->id]);
        $itemTransaction->delete();
        $response = $this->getJson('/api/v1/item_transactions/all');
        $response->assertStatus(200);
        $json = $response->json();
        if (is_array($json) && !empty($json)) {
            foreach ($json as $it) {
                $this->assertArrayHasKey('id', $it);
                $this->assertArrayHasKey('item_id', $it);
                $this->assertArrayHasKey('transaction_id', $it);
                $this->assertArrayHasKey('quantity', $it);
                $this->assertArrayHasKey('active', $it);
            }
            $ids = collect($json)->pluck('id');
            $this->assertTrue($ids->contains($itemTransaction->id));
        }
    }

    public function test_change_status_item_transaction()
    {
        $tax = \App\Models\Entities\Tax::factory()->create();
        $itemCategory = \App\Models\Entities\ItemCategory::factory()->create();
        $item = Item::factory()->create(['tax_id' => $tax->id, 'item_category_id' => $itemCategory->id]);
        $transaction = Transaction::factory()->create();
        $itemTransaction = ItemTransaction::factory()->create(['active' => 1, 'item_id' => $item->id, 'transaction_id' => $transaction->id]);
        $response = $this->patchJson('/api/v1/item_transactions/' . $itemTransaction->id . '/status');
        $response->assertStatus(200);
        $itemTransaction->refresh();
        $this->assertEquals(0, $itemTransaction->active);
    }
}
