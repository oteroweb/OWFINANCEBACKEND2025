<?php

namespace Tests\Feature\Api;

use App\Models\Entities\Account;
use App\Models\Entities\AccountType;
use App\Models\Entities\AiExtraction;
use App\Models\Entities\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AiExtractionAnswerTest extends TestCase
{
    use RefreshDatabase;

    public function test_answer_resolves_missing_account_without_calling_ai_provider()
    {
        Http::fake();

        $currency = Currency::factory()->create();
        $accountType = AccountType::factory()->create();
        $account = Account::factory()->create(['name' => 'Billetera USD', 'currency_id' => $currency->id, 'account_type_id' => $accountType->id]);
        $user = User::factory()->create();
        $user->accounts()->attach($account->id, ['is_owner' => 1]);
        Sanctum::actingAs($user, ['*']);

        $extraction = AiExtraction::create([
            'user_id'        => $user->id,
            'source'         => 'auto',
            'raw_input'      => 'gasté 15 dólares',
            'extracted_data' => ['type' => 'expense', 'amount' => 15, 'currency' => 'USD', 'account_id' => null],
            'missing_fields' => ['account_id'],
            'resolved'       => false,
            'model_used'     => 'test',
        ]);

        $res = $this->postJson("/api/v1/ai/extract-transaction/{$extraction->id}/answer", [
            'field' => 'account_id',
            'value' => $account->id,
        ]);

        $res->assertStatus(200)
            ->assertJsonPath('missing_fields', [])
            ->assertJsonPath('data.account_id', $account->id);

        Http::assertNothingSent();

        $this->assertTrue($extraction->fresh()->resolved);
    }

    public function test_answer_rejects_account_not_owned_by_user()
    {
        $currency = Currency::factory()->create();
        $accountType = AccountType::factory()->create();
        $ownAccount = Account::factory()->create(['name' => 'Mi cuenta', 'currency_id' => $currency->id, 'account_type_id' => $accountType->id]);
        $otherAccount = Account::factory()->create(['name' => 'Cuenta ajena', 'currency_id' => $currency->id, 'account_type_id' => $accountType->id]);
        $user = User::factory()->create();
        $user->accounts()->attach($ownAccount->id, ['is_owner' => 1]);
        Sanctum::actingAs($user, ['*']);

        $extraction = AiExtraction::create([
            'user_id'        => $user->id,
            'source'         => 'auto',
            'raw_input'      => 'gasté 15 dólares',
            'extracted_data' => ['type' => 'expense', 'amount' => 15, 'currency' => 'USD'],
            'missing_fields' => ['account_id'],
            'resolved'       => false,
            'model_used'     => 'test',
        ]);

        $res = $this->postJson("/api/v1/ai/extract-transaction/{$extraction->id}/answer", [
            'field' => 'account_id',
            'value' => $otherAccount->id,
        ]);

        $res->assertStatus(422);
    }

    public function test_answer_returns_404_for_extraction_belonging_to_another_user()
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $extraction = AiExtraction::create([
            'user_id'        => $owner->id,
            'source'         => 'auto',
            'raw_input'      => 'gasté 15 dólares',
            'extracted_data' => ['type' => 'expense', 'amount' => 15],
            'missing_fields' => ['account_id'],
            'resolved'       => false,
            'model_used'     => 'test',
        ]);

        Sanctum::actingAs($intruder, ['*']);

        $res = $this->postJson("/api/v1/ai/extract-transaction/{$extraction->id}/answer", [
            'field' => 'account_id',
            'value' => 1,
        ]);

        $res->assertStatus(404);
    }
}
