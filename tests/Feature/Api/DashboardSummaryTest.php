<?php

namespace Tests\Feature\Api;

use App\Models\Entities\Account;
use App\Models\Entities\AccountType;
use App\Models\Entities\Currency;
use App\Models\Entities\Transaction;
use App\Models\Entities\TransactionType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_returns_global_totals(): void
    {
        $adminRole = Role::create(['name' => 'Administrator', 'slug' => 'admin']);
        $userRole = Role::create(['name' => 'User', 'slug' => 'user']);

        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $user = User::factory()->create(['role_id' => $userRole->id]);

        Sanctum::actingAs($admin, ['*']);

        $currency = Currency::factory()->create(['code' => 'USD', 'symbol' => '$', 'name' => 'US Dollar']);
        $accountType = AccountType::factory()->create(['name' => 'Checking']);

        $primaryAccount = Account::factory()->create([
            'name' => 'Main account',
            'currency_id' => $currency->id,
            'account_type_id' => $accountType->id,
            'active' => 1,
            'initial' => 1000,
            'balance_cached' => 1000,
        ]);

        Account::factory()->create([
            'name' => 'Inactive account',
            'currency_id' => $currency->id,
            'account_type_id' => $accountType->id,
            'active' => 0,
            'initial' => 500,
            'balance_cached' => 500,
        ]);

        $incomeType = TransactionType::factory()->create(['name' => 'Income', 'slug' => 'income']);
        $expenseType = TransactionType::factory()->create(['name' => 'Expense', 'slug' => 'expense']);

        Transaction::factory()->create([
            'name' => 'Salary',
            'amount' => 200,
            'account_id' => $primaryAccount->id,
            'transaction_type_id' => $incomeType->id,
            'user_id' => $user->id,
            'include_in_balance' => 1,
            'active' => 1,
        ]);

        Transaction::factory()->create([
            'name' => 'Supplies',
            'amount' => -50,
            'account_id' => $primaryAccount->id,
            'transaction_type_id' => $expenseType->id,
            'user_id' => $user->id,
            'include_in_balance' => 1,
            'active' => 1,
        ]);

        $response = $this->getJson('/api/v1/dashboard/admin');

        $response->assertOk();

        $response->assertJson([
            'summary' => [
                'totals' => [
                    'users' => ['total' => 2, 'active' => 2, 'inactive' => 0],
                    'accounts' => ['total' => 2, 'active' => 1],
                    'transactions' => ['total' => 2, 'active' => 2],
                ],
                'financials' => [
                    'income' => 200.0,
                    'expense' => 50.0,
                    'net_cashflow' => 150.0,
                    'total_balance' => 1000.0,
                ],
            ],
        ]);

        $this->assertCount(2, $response->json('summary.users_by_role'));
        $this->assertNotEmpty($response->json('summary.latest_users'));
    }

    public function test_user_dashboard_returns_personal_summary(): void
    {
        $userRole = Role::create(['name' => 'User', 'slug' => 'user']);
        $otherRole = Role::create(['name' => 'Other', 'slug' => 'other']);

        $user = User::factory()->create(['role_id' => $userRole->id]);
        $otherUser = User::factory()->create(['role_id' => $otherRole->id]);

        Sanctum::actingAs($user, ['*']);

        $usd = Currency::factory()->create(['code' => 'USD', 'symbol' => '$', 'name' => 'US Dollar']);
        $eur = Currency::factory()->create(['code' => 'EUR', 'symbol' => '€', 'name' => 'Euro']);
        $accountType = AccountType::factory()->create(['name' => 'Wallet']);

        $accountUsd = Account::factory()->create([
            'name' => 'USD Wallet',
            'currency_id' => $usd->id,
            'account_type_id' => $accountType->id,
            'active' => 1,
            'initial' => 1200.25,
            'balance_cached' => 1200.25,
        ]);

        $accountEur = Account::factory()->create([
            'name' => 'EUR Wallet',
            'currency_id' => $eur->id,
            'account_type_id' => $accountType->id,
            'active' => 1,
            'initial' => 350.75,
            'balance_cached' => 350.75,
        ]);

        $user->accounts()->attach($accountUsd->id, ['is_owner' => 1]);
        $user->accounts()->attach($accountEur->id, ['is_owner' => 0]);

        $incomeType = TransactionType::factory()->create(['name' => 'Income', 'slug' => 'income']);
        $expenseType = TransactionType::factory()->create(['name' => 'Expense', 'slug' => 'expense']);

        Transaction::factory()->create([
            'name' => 'Consulting',
            'amount' => 300,
            'account_id' => $accountUsd->id,
            'transaction_type_id' => $incomeType->id,
            'user_id' => $user->id,
            'include_in_balance' => 1,
            'active' => 1,
            'date' => now()->subDay(),
        ]);

        Transaction::factory()->create([
            'name' => 'Software subscription',
            'amount' => -120.5,
            'account_id' => $accountEur->id,
            'transaction_type_id' => $expenseType->id,
            'user_id' => $user->id,
            'include_in_balance' => 1,
            'active' => 1,
            'date' => now(),
        ]);

        // Transaction excluded from balance
        Transaction::factory()->create([
            'name' => 'Transfer internal',
            'amount' => -999,
            'account_id' => $accountUsd->id,
            'transaction_type_id' => $expenseType->id,
            'user_id' => $user->id,
            'include_in_balance' => 0,
            'active' => 1,
        ]);

        // Transaction in another account (should not appear)
        $otherAccount = Account::factory()->create([
            'currency_id' => $usd->id,
            'account_type_id' => $accountType->id,
            'active' => 1,
            'initial' => 75,
            'balance_cached' => 75,
        ]);

        Transaction::factory()->create([
            'name' => 'Other user income',
            'amount' => 999,
            'account_id' => $otherAccount->id,
            'transaction_type_id' => $incomeType->id,
            'user_id' => $otherUser->id,
            'include_in_balance' => 1,
            'active' => 1,
        ]);

        $response = $this->getJson('/api/v1/dashboard/user');

        $response->assertOk();

        $response->assertJson([
            'summary' => [
                'accounts' => [
                    'total' => 2,
                    'active' => 2,
                    'total_balance' => 1551.0,
                ],
                'financials' => [
                    'income' => 300.0,
                    'expense' => 120.5,
                    'net_cashflow' => 179.5,
                    'transaction_count' => 2,
                ],
            ],
        ]);

        $this->assertCount(2, $response->json('summary.accounts.by_currency'));
        $this->assertCount(2, $response->json('summary.recent_transactions'));
        $this->assertEquals(-120.5, $response->json('summary.recent_transactions.0.amount'));
    }
}
