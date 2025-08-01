<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Entities\ItemCategory;

class ItemCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_item_category_crud_flow()
    {
        $data = [
            'name' => 'Test Item Category',
        ];
        $createResponse = $this->postJson('/api/v1/item_categories/', $data);
        $createResponse->assertStatus(200)
            ->assertJson(['status' => 'OK']);
        $itemCategoryId = $createResponse->json('data.id') ?? ItemCategory::where('name', 'Test Item Category')->first()->id;

        $findResponse = $this->getJson('/api/v1/item_categories/' . $itemCategoryId);
        $findResponse->assertStatus(200)
            ->assertJson(['data' => ['id' => $itemCategoryId, 'name' => 'Test Item Category']]);

        $updateData = ['name' => 'Updated Item Category'];
        $updateResponse = $this->putJson('/api/v1/item_categories/' . $itemCategoryId, $updateData);
        $updateResponse->assertStatus(200)
            ->assertJson(['data' => ['name' => 'Updated Item Category']]);

        $listResponse = $this->getJson('/api/v1/item_categories/');
        $listResponse->assertStatus(200)
            ->assertJsonStructure([
                'status', 'code', 'message', 'data' => [['id', 'name', 'active']]
            ]);
        $this->assertTrue(collect($listResponse->json('data'))->contains('id', $itemCategoryId));

        $deleteResponse = $this->deleteJson('/api/v1/item_categories/' . $itemCategoryId);
        $deleteResponse->assertStatus(200);
        $this->assertSoftDeleted('item_categories', ['id' => $itemCategoryId]);
    }

    public function test_get_all_active_item_categories()
    {
        ItemCategory::factory()->create(['active' => 1]);
        ItemCategory::factory()->create(['active' => 0]);
        $response = $this->getJson('/api/v1/item_categories/active');
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('data', $json);
        if (!empty($json['data'])) {
            foreach ($json['data'] as $ic) {
                $this->assertArrayHasKey('id', $ic);
                $this->assertArrayHasKey('name', $ic);
                $this->assertArrayHasKey('active', $ic);
                $this->assertEquals(1, $ic['active']);
            }
        }
    }

    public function test_get_item_categories_with_trashed()
    {
        $itemCategory = ItemCategory::factory()->create();
        $itemCategory->delete();
        $response = $this->getJson('/api/v1/item_categories/all');
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('data', $json);
        if (!empty($json['data'])) {
            foreach ($json['data'] as $ic) {
                $this->assertArrayHasKey('id', $ic);
                $this->assertArrayHasKey('name', $ic);
                $this->assertArrayHasKey('active', $ic);
            }
            $ids = collect($json['data'])->pluck('id');
            $this->assertTrue($ids->contains($itemCategory->id));
        }
    }

    public function test_change_status_item_category()
    {
        $itemCategory = ItemCategory::factory()->create(['active' => 1]);
        $response = $this->patchJson('/api/v1/item_categories/' . $itemCategory->id . '/status');
        $response->assertStatus(200)
            ->assertJson(['status' => 'OK', 'message' => __('Status Item Category updated')]);
        $itemCategory->refresh();
        $this->assertEquals(0, $itemCategory->active);
    }
}
