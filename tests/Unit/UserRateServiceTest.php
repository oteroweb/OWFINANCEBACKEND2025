<?php

namespace Tests\Unit;

use App\Models\Entities\Currency;
use App\Models\Entities\UserCurrency;
use App\Models\User;
use App\Services\UserRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRateServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserAndCurrency(): array
    {
        $user = User::factory()->create();
        $currency = Currency::factory()->create();
        return [$user, $currency];
    }

    public function test_marks_multiple_officials_and_single_current(): void
    {
        [$user, $currency] = $this->makeUserAndCurrency();
        $svc = new UserRateService();

        // First official (not current)
        $svc->applyFromPayment($user->id, $currency->id, 36.5, false, true);

        // Second official and current
        $svc->applyFromPayment($user->id, $currency->id, 37.0, true, true);

        $all = UserCurrency::where('user_id', $user->id)
            ->where('currency_id', $currency->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $all, 'Should have two rate records');
        $this->assertTrue($all[0]->is_official, 'First should be official');
        $this->assertNotNull($all[0]->official_at, 'First official should have timestamp');
        $this->assertTrue($all[1]->is_official, 'Second should be official');
        $this->assertNotNull($all[1]->official_at, 'Second official should have timestamp');

        $currents = $all->where('is_current', true);
        $this->assertCount(1, $currents, 'Only one current expected');
        $this->assertEquals(37.0, (float) $currents->first()->current_rate, 'Latest flagged current should be current');
    }

    public function test_reuses_existing_rate_record_and_preserves_official_at(): void
    {
        [$user, $currency] = $this->makeUserAndCurrency();
        $svc = new UserRateService();

        $first = $svc->applyFromPayment($user->id, $currency->id, 36.5, false, true);
        $firstOfficialAt = $first->official_at;

        // Call again with same rate, toggle current true; should reuse record
        $second = $svc->applyFromPayment($user->id, $currency->id, 36.5, true, true);

        $this->assertEquals($first->id, $second->id, 'Should reuse same rate record for identical rate');
        $this->assertTrue($second->is_current, 'Record should now be current');
        $this->assertTrue($second->is_official, 'Record remains official');
        $this->assertEquals($firstOfficialAt?->toISOString(), $second->official_at?->toISOString(), 'official_at should not change when re-marking');

        // Ensure there is still only one record
        $count = UserCurrency::where('user_id', $user->id)
            ->where('currency_id', $currency->id)
            ->where('current_rate', 36.5)
            ->count();
        $this->assertEquals(1, $count, 'Only one record for same rate');
    }
}
