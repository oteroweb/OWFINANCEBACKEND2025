<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Entities\Transaction;
use App\Models\Entities\Account;
use Carbon\Carbon;

class TransactionPeriodTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Crea transacciones en diferentes fechas y prueba filtro semanal (ISO week).
     */
    public function test_filter_by_iso_week()
    {
        $account = Account::factory()->create();
        // Semana ISO 37 del 2025: empieza 2025-09-08 (lunes) termina 2025-09-14 (domingo)
        $week = 37; $year = 2025;
        $monday = Carbon::now()->setISODate($year, $week)->startOfWeek();
        $sunday = (clone $monday)->endOfWeek();

        // Dentro de la semana
        $tIn1 = Transaction::factory()->create(['account_id' => $account->id, 'date' => $monday->copy()->addDays(1)]);
        $tIn2 = Transaction::factory()->create(['account_id' => $account->id, 'date' => $monday->copy()->addDays(5)]);
        // Fuera (antes)
        $tOut1 = Transaction::factory()->create(['account_id' => $account->id, 'date' => $monday->copy()->subDay()]);
        // Fuera (después)
        $tOut2 = Transaction::factory()->create(['account_id' => $account->id, 'date' => $sunday->copy()->addDay()]);

        $url = "/api/v1/transactions?period_type=week&week={$week}&year={$year}&account_ids={$account->id}";
        $response = $this->getJson($url);
        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($tIn1->id));
        $this->assertTrue($ids->contains($tIn2->id));
        $this->assertFalse($ids->contains($tOut1->id));
        $this->assertFalse($ids->contains($tOut2->id));
    }

    /**
     * Prueba filtro quincenal: quincena 1 (1-15) vs quincena 2 (16-fin) de un mes.
     */
    public function test_filter_by_fortnight_first_and_second()
    {
        $account = Account::factory()->create();
        $year = 2025; $month = 9; // Septiembre 2025

        // Quincena 1 (1-15)
        $tF1a = Transaction::factory()->create(['account_id' => $account->id, 'date' => Carbon::create($year,$month,1,10,0,0)]);
        $tF1b = Transaction::factory()->create(['account_id' => $account->id, 'date' => Carbon::create($year,$month,15,23,59,0)]);
        // Quincena 2 (16-fin)
        $tF2a = Transaction::factory()->create(['account_id' => $account->id, 'date' => Carbon::create($year,$month,16,0,0,0)]);
        $tF2b = Transaction::factory()->create(['account_id' => $account->id, 'date' => Carbon::create($year,$month,30,12,0,0)]);

        // Filtrar primera quincena
        $url1 = "/api/v1/transactions?period_type=fortnight&fortnight=1&month={$month}&year={$year}&account_ids={$account->id}";
        $resp1 = $this->getJson($url1);
        $resp1->assertStatus(200);
        $ids1 = collect($resp1->json('data'))->pluck('id');
        $this->assertTrue($ids1->contains($tF1a->id));
        $this->assertTrue($ids1->contains($tF1b->id));
        $this->assertFalse($ids1->contains($tF2a->id));
        $this->assertFalse($ids1->contains($tF2b->id));

        // Filtrar segunda quincena
        $url2 = "/api/v1/transactions?period_type=fortnight&fortnight=2&month={$month}&year={$year}&account_ids={$account->id}";
        $resp2 = $this->getJson($url2);
        $resp2->assertStatus(200);
        $ids2 = collect($resp2->json('data'))->pluck('id');
        $this->assertTrue($ids2->contains($tF2a->id));
        $this->assertTrue($ids2->contains($tF2b->id));
        $this->assertFalse($ids2->contains($tF1a->id));
        $this->assertFalse($ids2->contains($tF1b->id));
    }
}
