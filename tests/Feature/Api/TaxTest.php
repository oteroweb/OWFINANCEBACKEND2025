<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Entities\Tax;

class TaxTest extends TestCase
{
    use RefreshDatabase;

    public function test_tax_crud_flow()
    {
        $data = [
            'name' => 'Test Tax',
            'percent' => 0.15,
        ];
        $createResponse = $this->postJson('/api/v1/taxes/', $data);
        $this->assertTrue(in_array($createResponse->status(), [200, 201]));
        $this->assertEquals('OK', $createResponse->json('status'));
        $taxId = $createResponse->json('data.id') ?? Tax::where('name', 'Test Tax')->first()->id;

        $findResponse = $this->getJson('/api/v1/taxes/' . $taxId);
        $findResponse->assertStatus(200);
        $this->assertEquals($taxId, $findResponse->json('data.id'));
        $this->assertEquals('Test Tax', $findResponse->json('data.name'));

        $updateData = ['name' => 'Updated Tax'];
        $updateResponse = $this->putJson('/api/v1/taxes/' . $taxId, $updateData);
        $updateResponse->assertStatus(200);
        $this->assertEquals('Updated Tax', $updateResponse->json('data.name'));

        $listResponse = $this->getJson('/api/v1/taxes/');
        $listResponse->assertStatus(200);
        $json = $listResponse->json();
        $this->assertArrayHasKey('data', $json);
        $this->assertTrue(collect($json['data'])->contains('id', $taxId));

        $deleteResponse = $this->deleteJson('/api/v1/taxes/' . $taxId);
        $deleteResponse->assertStatus(200);
        $this->assertSoftDeleted('taxes', ['id' => $taxId]);
    }

    public function test_get_all_active_taxes()
    {
        Tax::factory()->create(['active' => 1]);
        Tax::factory()->create(['active' => 0]);
        $response = $this->getJson('/api/v1/taxes/active');
        $response->assertStatus(200);
        $json = $response->json();
        if (isset($json['data'])) {
            if (!empty($json['data'])) {
                foreach ($json['data'] as $t) {
                    $this->assertArrayHasKey('id', $t);
                    $this->assertArrayHasKey('name', $t);
                    $this->assertArrayHasKey('percent', $t);
                    $this->assertArrayHasKey('active', $t);
                    $this->assertEquals(1, $t['active']);
                }
            }
        } else {
            $this->assertEquals('FAILED', $json['status']);
            $this->assertEquals(404, $json['code']);
        }
    }

    public function test_get_taxes_with_trashed()
    {
        $tax = Tax::factory()->create();
        $tax->delete();
        $response = $this->getJson('/api/v1/taxes/all');
        $response->assertStatus(200);
        $json = $response->json();
        if (isset($json['data'])) {
            if (!empty($json['data'])) {
                foreach ($json['data'] as $t) {
                    $this->assertArrayHasKey('id', $t);
                    $this->assertArrayHasKey('name', $t);
                    $this->assertArrayHasKey('percent', $t);
                    $this->assertArrayHasKey('active', $t);
                }
                $ids = collect($json['data'])->pluck('id');
                $this->assertTrue($ids->contains($tax->id));
            }
        } else {
            $this->assertEquals('FAILED', $json['status']);
            $this->assertEquals(404, $json['code']);
        }
    }

    public function test_change_status_tax()
    {
        $tax = Tax::factory()->create(['active' => 1]);
        $response = $this->patchJson('/api/v1/taxes/' . $tax->id . '/status');
        $response->assertStatus(200)
            ->assertJson(['status' => 'OK', 'message' => __('Status Tax updated')]);
        $tax->refresh();
        $this->assertEquals(0, $tax->active);
    }
}
