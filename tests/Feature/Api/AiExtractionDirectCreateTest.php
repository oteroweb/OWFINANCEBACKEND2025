<?php

namespace Tests\Feature\Api;

use App\Models\Entities\Account;
use App\Models\Entities\AccountType;
use App\Models\Entities\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AiExtractionDirectCreateTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGroqResponse(array $extracted): void
    {
        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode($extracted)]]],
                'usage'   => ['prompt_tokens' => 10, 'completion_tokens' => 10],
            ], 200),
        ]);
    }

    public function test_direct_create_true_when_command_present_and_no_missing_fields()
    {
        $currency = Currency::factory()->create();
        $accountType = AccountType::factory()->create();
        $account = Account::factory()->create(['name' => 'Billetera USD', 'currency_id' => $currency->id, 'account_type_id' => $accountType->id]);
        $user = User::factory()->create();
        $user->accounts()->attach($account->id, ['is_owner' => 1]);
        Sanctum::actingAs($user, ['*']);

        $this->fakeGroqResponse([
            'type' => 'expense', 'amount' => 15, 'currency' => 'USD',
            'description' => 'dulces', 'category_suggestion' => 'comida',
            'merchant' => null, 'account_id' => null, 'date' => '2026-07-20', 'confidence' => 0.95,
        ]);

        $res = $this->postJson('/api/v1/ai/extract-transaction', [
            'source' => 'auto',
            'input'  => 'Gasté 15 dólares en dulces, crea directo',
        ]);

        $res->assertStatus(200)
            ->assertJsonPath('direct_create', true)
            ->assertJsonPath('missing_fields', []);
    }

    public function test_direct_create_false_when_missing_fields_present_even_with_command()
    {
        $currency = Currency::factory()->create();
        $accountType = AccountType::factory()->create();
        $a1 = Account::factory()->create(['name' => 'Billetera USD', 'currency_id' => $currency->id, 'account_type_id' => $accountType->id]);
        $a2 = Account::factory()->create(['name' => 'Billetera VES', 'currency_id' => $currency->id, 'account_type_id' => $accountType->id]);
        $user = User::factory()->create();
        $user->accounts()->attach([$a1->id, $a2->id], ['is_owner' => 1]);
        Sanctum::actingAs($user, ['*']);

        $this->fakeGroqResponse([
            'type' => 'expense', 'amount' => 15, 'currency' => 'USD',
            'description' => 'dulces', 'category_suggestion' => 'comida',
            'merchant' => null, 'account_id' => null, 'date' => '2026-07-20', 'confidence' => 0.95,
        ]);

        $res = $this->postJson('/api/v1/ai/extract-transaction', [
            'source' => 'auto',
            'input'  => 'Gasté 15 dólares en dulces, crea directo',
        ]);

        $res->assertStatus(200)
            ->assertJsonPath('direct_create', false)
            ->assertJsonPath('missing_fields', ['account_id']);
    }

    public function test_direct_create_false_when_command_absent()
    {
        $currency = Currency::factory()->create();
        $accountType = AccountType::factory()->create();
        $account = Account::factory()->create(['name' => 'Billetera USD', 'currency_id' => $currency->id, 'account_type_id' => $accountType->id]);
        $user = User::factory()->create();
        $user->accounts()->attach($account->id, ['is_owner' => 1]);
        Sanctum::actingAs($user, ['*']);

        $this->fakeGroqResponse([
            'type' => 'expense', 'amount' => 15, 'currency' => 'USD',
            'description' => 'dulces', 'category_suggestion' => 'comida',
            'merchant' => null, 'account_id' => null, 'date' => '2026-07-20', 'confidence' => 0.95,
        ]);

        $res = $this->postJson('/api/v1/ai/extract-transaction', [
            'source' => 'auto',
            'input'  => 'Gasté 15 dólares en dulces',
        ]);

        $res->assertStatus(200)->assertJsonPath('direct_create', false);
    }
}
