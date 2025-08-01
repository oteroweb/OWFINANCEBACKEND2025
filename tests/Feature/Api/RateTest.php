<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Entities\Rate;

class RateTest extends TestCase
{
    use RefreshDatabase;
    use \Illuminate\Foundation\Testing\WithoutMiddleware;

    public function test_rate_crud_flow()
    {
        $data = [
            'name' => 'Test Rate',
            'value' => 1.5,
            'date' => now()->format('Y-m-d'),
        ];
        $createResponse = $this->postJson('/api/v1/rates/', $data);
        $createResponse->assertStatus(200)
            ->assertJson(['status' => 'OK']);
        $rateId = $createResponse->json('data.id') ?? Rate::where('name', 'Test Rate')->first()->id;

        $findResponse = $this->getJson('/api/v1/rates/' . $rateId);
        $findResponse->assertStatus(200)
            ->assertJson(['data' => ['id' => $rateId, 'name' => 'Test Rate']]);

        $updateData = ['name' => 'Updated Rate'];
        $updateResponse = $this->putJson('/api/v1/rates/' . $rateId, $updateData);
        $updateResponse->assertStatus(200)
            ->assertJson(['data' => ['name' => 'Updated Rate']]);

        $listResponse = $this->getJson('/api/v1/rates/');
        $listResponse->assertStatus(200)
            ->assertJsonStructure([
                'status', 'code', 'message', 'data' => [['id', 'name', 'value', 'active']]
            ]);
        $this->assertTrue(collect($listResponse->json('data'))->contains('id', $rateId));

        $deleteResponse = $this->deleteJson('/api/v1/rates/' . $rateId);
        $deleteResponse->assertStatus(200);
        $this->assertSoftDeleted('rates', ['id' => $rateId]);
    }

    public function test_get_all_active_rates()
    {
        Rate::factory()->create(['active' => 1]);
        Rate::factory()->create(['active' => 0]);
        $response = $this->getJson('/api/v1/rates/active');
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('data', $json);
        if (!empty($json['data'])) {
            foreach ($json['data'] as $r) {
                $this->assertArrayHasKey('id', $r);
                $this->assertArrayHasKey('name', $r);
                $this->assertArrayHasKey('value', $r);
                $this->assertArrayHasKey('active', $r);
                $this->assertEquals(1, $r['active']);
            }
        }
    }

    public function test_get_rates_with_trashed()
    {
        $rate = Rate::factory()->create();
        $rate->delete();
        $response = $this->getJson('/api/v1/rates/all');
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('data', $json);
        if (!empty($json['data'])) {
            foreach ($json['data'] as $r) {
                $this->assertArrayHasKey('id', $r);
                $this->assertArrayHasKey('name', $r);
                $this->assertArrayHasKey('value', $r);
                $this->assertArrayHasKey('active', $r);
            }
            $ids = collect($json['data'])->pluck('id');
            $this->assertTrue($ids->contains($rate->id));
        }
    }

    public function test_change_status_rate()
    {
        $rate = Rate::factory()->create(['active' => 1]);
        $response = $this->patchJson('/api/v1/rates/' . $rate->id . '/status');
        $response->assertStatus(200)
            ->assertJson(['status' => 'OK', 'message' => __('Status Rate updated')]);
        $rate->refresh();
        $this->assertEquals(0, $rate->active);
    }
}
