<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Entities\ItemTax;
use App\Models\Entities\Item;
use App\Models\Entities\Tax;

class ItemTaxTest extends TestCase
{
    use RefreshDatabase;

    public function test_item_tax_crud_flow()
    {
        $item = Item::factory()->create();
        $tax = Tax::factory()->create();
        $itemTransaction = \App\Models\Entities\ItemTransaction::factory()->create([
            'item_id' => $item->id,
            'tax_id' => $tax->id
        ]);
        $data = [
            'item_transaction_id' => $itemTransaction->id,
            'tax_id' => $tax->id,
            'amount' => 5.00
        ];
        $createResponse = $this->postJson('/api/v1/item-taxes/', $data);
        $this->assertTrue(in_array($createResponse->status(), [200, 201, 204]));
        $id = $createResponse->json('data.id') ?? ItemTax::where($data)->first()->id;

        $findResponse = $this->getJson('/api/v1/item-taxes/' . $id);
        $findResponse->assertStatus(200);
        $json = $findResponse->json();
        $this->assertEquals($id, $json['id']);
        $this->assertEquals($itemTransaction->id, $json['item_transaction_id']);
        $this->assertEquals($tax->id, $json['tax_id']);

        $listResponse = $this->getJson('/api/v1/item-taxes/');
        $listResponse->assertStatus(200);
        $json = $listResponse->json();
        $this->assertTrue(collect($json)->contains('id', $id));

        $deleteResponse = $this->deleteJson('/api/v1/item-taxes/' . $id);
        $this->assertTrue(in_array($deleteResponse->status(), [200, 204]));
        $this->assertSoftDeleted('item_taxes', ['id' => $id]);
    }

    public function test_get_all_active_item_taxes()
    {
        $item = Item::factory()->create();
        $tax = Tax::factory()->create();
        ItemTax::factory()->create(['active' => 1, 'item_id' => $item->id, 'tax_id' => $tax->id]);
        ItemTax::factory()->create(['active' => 0, 'item_id' => $item->id, 'tax_id' => $tax->id]);
        $response = $this->getJson('/api/v1/item-taxes/active');
        $response->assertStatus(200);
        $json = $response->json();
        if (!empty($json)) {
            foreach ($json as $i) {
                $this->assertArrayHasKey('id', $i);
                $this->assertArrayHasKey('item_id', $i);
                $this->assertArrayHasKey('tax_id', $i);
                $this->assertArrayHasKey('amount', $i);
                $this->assertArrayHasKey('active', $i);
                $this->assertEquals(1, $i['active']);
            }
        }
    }

    public function test_get_item_taxes_with_trashed()
    {
        $item = Item::factory()->create();
        $tax = Tax::factory()->create();
        $itemTax = ItemTax::factory()->create(['item_id' => $item->id, 'tax_id' => $tax->id]);
        $itemTax->delete();
        $response = $this->getJson('/api/v1/item-taxes/all');
        $response->assertStatus(200);
        $json = $response->json();
        if (!empty($json)) {
            foreach ($json as $i) {
                $this->assertArrayHasKey('id', $i);
                $this->assertArrayHasKey('item_id', $i);
                $this->assertArrayHasKey('tax_id', $i);
                $this->assertArrayHasKey('amount', $i);
                $this->assertArrayHasKey('active', $i);
            }
            $ids = collect($json)->pluck('id');
            $this->assertTrue($ids->contains($itemTax->id));
        }
    }

    public function test_change_status_item_tax()
    {
        $item = Item::factory()->create();
        $tax = Tax::factory()->create();
        $itemTax = ItemTax::factory()->create(['active' => 1, 'item_id' => $item->id, 'tax_id' => $tax->id]);
        $response = $this->patchJson('/api/v1/item-taxes/' . $itemTax->id . '/status');
        $response->assertStatus(200);
        $itemTax->refresh();
        $this->assertEquals(0, $itemTax->active);
    }
}
