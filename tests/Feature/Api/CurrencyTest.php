<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Entities\Currency;

class CurrencyTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Test cambiar el estado de una currency.
     */
    public function test_change_status_currency()
    {
        $currency = Currency::factory()->create(['active' => 1]);
        $response = $this->patchJson('/api/v1/currencies/' . $currency->id . '/status');
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'OK',
                'message' => __('Status Currency updated')
            ]);
        $currency->refresh();
        $this->assertEquals(0, $currency->active);
    }

    /**
     * Test obtener solo currencies activas.
     */
    public function test_get_all_active_currencies()
    {
        Currency::factory()->create(['active' => 1]);
        Currency::factory()->create(['active' => 0]);
        $response = $this->getJson('/api/v1/currencies/active');
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('data', $json);
        if (!empty($json['data'])) {
            foreach ($json['data'] as $c) {
                $this->assertArrayHasKey('id', $c);
                $this->assertArrayHasKey('name', $c);
                $this->assertArrayHasKey('symbol', $c);
                $this->assertArrayHasKey('code', $c);
                $this->assertArrayHasKey('tax', $c);
                $this->assertArrayHasKey('active', $c);
                $this->assertEquals(1, $c['active']);
            }
        }
    }

    /**
     * Test obtener currencies con eliminados (withTrashed).
     */
    public function test_get_currencies_with_trashed()
    {
        $currency = Currency::factory()->create();
        $currency->delete();
        $response = $this->getJson('/api/v1/currencies/all');
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('data', $json);
        if (!empty($json['data'])) {
            foreach ($json['data'] as $c) {
                $this->assertArrayHasKey('id', $c);
                $this->assertArrayHasKey('name', $c);
                $this->assertArrayHasKey('symbol', $c);
                $this->assertArrayHasKey('code', $c);
                $this->assertArrayHasKey('tax', $c);
                $this->assertArrayHasKey('active', $c);
            }
            $ids = collect($json['data'])->pluck('id');
            $this->assertTrue($ids->contains($currency->id));
        }
    }


    /**
     * Test full currency flow: create, find, update, list, delete.
     *
     * @return void
     */
    public function test_currency_crud_flow()
    {
        // Crear
        $currencyData = [
            'name' => 'Test Currency',
            'tax' => 10.00,
            'last_tax' => 9.00,
            'symbol' => 'TC',
            'align' => 'left',
            'symbol_native' => 'TCn',
            'decimal_digits' => 2,
            'rounding' => 0,
            'name_plural' => 'Test Currencies',
            'code' => 'TC'
        ];
        $createResponse = $this->postJson('/api/v1/currencies/', $currencyData);
        $createResponse->assertStatus(200)
            ->assertJson([
                'status' => 'OK',
                'message' => __('Currency saved correctly')
            ]);
        $currencyId = $createResponse->json('data.id') ?? \App\Models\Entities\Currency::where('name', 'Test Currency')->first()->id;

        // Buscar por id
        $findResponse = $this->getJson('/api/v1/currencies/' . $currencyId);
        $findResponse->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $currencyId,
                    'name' => 'Test Currency'
                ]
            ]);

        // Actualizar
        $updateData = ['name' => 'Updated Name'];
        $updateResponse = $this->putJson('/api/v1/currencies/' . $currencyId, $updateData);
        $updateResponse->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'Updated Name'
                ]
            ]);

        // Listar todos
        $listResponse = $this->getJson('/api/v1/currencies/');
        $listResponse->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'code',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'symbol',
                        'code',
                        'tax',
                        'active'
                    ]
                ]
            ]);
        $this->assertTrue(collect($listResponse->json('data'))->contains('id', $currencyId));

        // Eliminar
        $deleteResponse = $this->deleteJson('/api/v1/currencies/' . $currencyId);
        $deleteResponse->assertStatus(200);
        $this->assertSoftDeleted('currencies', ['id' => $currencyId]);
    }
}
