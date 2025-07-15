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
     * Test get all currencies.
     *
     * @return void
     */
    public function test_get_all_currencies()
    {
        Currency::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/currencies/all');

        $response->assertStatus(200)
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
    }

    /**
     * Test find a currency.
     *
     * @return void
     */
    public function test_find_currency()
    {
        $currency = Currency::factory()->create();

        $response = $this->getJson('/api/v1/currencies/find/' . $currency->id);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $currency->id,
                    'name' => $currency->name
                ]
            ]);
    }

    /**
     * Test create a new currency.
     *
     * @return void
     */
    public function test_create_currency()
    {
        $currencyData = [
            'name' => 'Test Currency',
            'tax' => 10.00,
            'last_tax' => 9.00,
            'symbol' => 'TC',
            'symbol_native' => 'TCn',
            'decimal_digits' => 2,
            'rounding' => 0,
            'name_plural' => 'Test Currencies',
            'code' => 'TC'
        ];

        $response = $this->postJson('/api/v1/currencies/save', $currencyData);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'OK',
                'message' => __('Currency saved correctly')
            ]);

        $this->assertDatabaseHas('currencies', ['name' => 'Test Currency']);
    }

    /**
     * Test update a currency.
     *
     * @return void
     */
    public function test_update_currency()
    {
        $currency = Currency::factory()->create();

        $updateData = ['name' => 'Updated Name'];

        $response = $this->putJson('/api/v1/currencies/update/' . $currency->id, $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'Updated Name'
                ]
            ]);
    }

    /**
     * Test delete a currency.
     *
     * @return void
     */
    public function test_delete_currency()
    {
        $currency = Currency::factory()->create();

        $response = $this->deleteJson('/api/v1/currencies/delete/' . $currency->id);

        $response->assertStatus(200);

        $this->assertSoftDeleted('currencies', ['id' => $currency->id]);
    }
}
