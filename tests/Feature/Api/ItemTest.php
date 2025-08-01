<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Entities\Item;
use App\Models\Entities\Category;

class ItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_item_crud_flow()
    {
        $itemCategory = \App\Models\Entities\ItemCategory::factory()->create();
        $data = [
            'name' => 'Test Item',
            'category_id' => $itemCategory->id,
            'price' => 10.00,
        ];
        $createResponse = $this->postJson('/api/v1/items/', $data);
        $createResponse->assertStatus(200)
            ->assertJson(['status' => 'OK']);
        $itemId = $createResponse->json('data.id') ?? Item::where('name', 'Test Item')->first()->id;

        $findResponse = $this->getJson('/api/v1/items/' . $itemId);
        $findResponse->assertStatus(200)
            ->assertJson(['data' => ['id' => $itemId, 'name' => 'Test Item']]);

        $updateData = ['name' => 'Updated Item'];
        $updateResponse = $this->putJson('/api/v1/items/' . $itemId, $updateData);
        $updateResponse->assertStatus(200)
            ->assertJson(['data' => ['name' => 'Updated Item']]);

        $listResponse = $this->getJson('/api/v1/items/');
        $listResponse->assertStatus(200)
            ->assertJsonStructure([
                'status', 'code', 'message', 'data' => [['id', 'name', 'category_id', 'price', 'active']]
            ]);
        $this->assertTrue(collect($listResponse->json('data'))->contains('id', $itemId));

        $deleteResponse = $this->deleteJson('/api/v1/items/' . $itemId);
        $deleteResponse->assertStatus(200);
        $this->assertSoftDeleted('items', ['id' => $itemId]);
    }

    public function test_get_all_active_items()
    {
        $tax = \App\Models\Entities\Tax::factory()->create();
        $itemCategory = \App\Models\Entities\ItemCategory::factory()->create();
        Item::factory()->create(['active' => 1, 'tax_id' => $tax->id, 'item_category_id' => $itemCategory->id]);
        Item::factory()->create(['active' => 0, 'tax_id' => $tax->id, 'item_category_id' => $itemCategory->id]);
        $response = $this->getJson('/api/v1/items/active');
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('data', $json);
        if (!empty($json['data'])) {
            foreach ($json['data'] as $i) {
                $this->assertArrayHasKey('id', $i);
                $this->assertArrayHasKey('name', $i);
                $this->assertArrayHasKey('category_id', $i);
                $this->assertArrayHasKey('price', $i);
                $this->assertArrayHasKey('active', $i);
                $this->assertEquals(1, $i['active']);
            }
        }
    }

    public function test_get_items_with_trashed()
    {
        $tax = \App\Models\Entities\Tax::factory()->create();
        $itemCategory = \App\Models\Entities\ItemCategory::factory()->create();
        $item = Item::factory()->create(['tax_id' => $tax->id, 'item_category_id' => $itemCategory->id]);
        $item->delete();
        $response = $this->getJson('/api/v1/items/all');
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('data', $json);
        if (!empty($json['data'])) {
            foreach ($json['data'] as $i) {
                $this->assertArrayHasKey('id', $i);
                $this->assertArrayHasKey('name', $i);
                $this->assertArrayHasKey('category_id', $i);
                $this->assertArrayHasKey('price', $i);
                $this->assertArrayHasKey('active', $i);
            }
            $ids = collect($json['data'])->pluck('id');
            $this->assertTrue($ids->contains($item->id));
        }
    }

    public function test_change_status_item()
    {
        $tax = \App\Models\Entities\Tax::factory()->create();
        $itemCategory = \App\Models\Entities\ItemCategory::factory()->create();
        $item = Item::factory()->create(['active' => 1, 'tax_id' => $tax->id, 'item_category_id' => $itemCategory->id]);
        $response = $this->patchJson('/api/v1/items/' . $item->id . '/status');
        $response->assertStatus(200)
            ->assertJson(['status' => 'OK', 'message' => __('Status Item updated')]);
        $item->refresh();
        $this->assertEquals(0, $item->active);
    }
}
