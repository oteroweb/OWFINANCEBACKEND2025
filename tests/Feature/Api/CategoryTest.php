<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Entities\Category;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_crud_flow()
    {
        $data = [
            'name' => 'Test Category',
        ];
        $createResponse = $this->postJson('/api/v1/categories/', $data);
        $createResponse->assertStatus(200)
            ->assertJson(['status' => 'OK']);
        $categoryId = $createResponse->json('data.id') ?? Category::where('name', 'Test Category')->first()->id;

        $findResponse = $this->getJson('/api/v1/categories/' . $categoryId);
        $findResponse->assertStatus(200)
            ->assertJson(['data' => ['id' => $categoryId, 'name' => 'Test Category']]);

        $updateData = ['name' => 'Updated Category'];
        $updateResponse = $this->putJson('/api/v1/categories/' . $categoryId, $updateData);
        $updateResponse->assertStatus(200)
            ->assertJson(['data' => ['name' => 'Updated Category']]);

        $listResponse = $this->getJson('/api/v1/categories/');
        $listResponse->assertStatus(200)
            ->assertJsonStructure([
                'status', 'code', 'message', 'data' => [['id', 'name', 'active']]
            ]);
        $this->assertTrue(collect($listResponse->json('data'))->contains('id', $categoryId));

        $deleteResponse = $this->deleteJson('/api/v1/categories/' . $categoryId);
        $deleteResponse->assertStatus(200);
        $this->assertSoftDeleted('categories', ['id' => $categoryId]);
    }

    public function test_get_all_active_categories()
    {
        Category::factory()->create(['active' => 1]);
        Category::factory()->create(['active' => 0]);
        $response = $this->getJson('/api/v1/categories/active');
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('data', $json);
        if (!empty($json['data'])) {
            foreach ($json['data'] as $c) {
                $this->assertArrayHasKey('id', $c);
                $this->assertArrayHasKey('name', $c);
                $this->assertArrayHasKey('active', $c);
                $this->assertEquals(1, $c['active']);
            }
        }
    }

    public function test_get_categories_with_trashed()
    {
        $category = Category::factory()->create();
        $category->delete();
        $response = $this->getJson('/api/v1/categories/all');
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('data', $json);
        if (!empty($json['data'])) {
            foreach ($json['data'] as $c) {
                $this->assertArrayHasKey('id', $c);
                $this->assertArrayHasKey('name', $c);
                $this->assertArrayHasKey('active', $c);
            }
            $ids = collect($json['data'])->pluck('id');
            $this->assertTrue($ids->contains($category->id));
        }
    }

    public function test_change_status_category()
    {
        $category = Category::factory()->create(['active' => 1]);
        $response = $this->patchJson('/api/v1/categories/' . $category->id . '/status');
        $response->assertStatus(200)
            ->assertJson(['status' => 'OK', 'message' => __('Status Category updated')]);
        $category->refresh();
        $this->assertEquals(0, $category->active);
    }
}
