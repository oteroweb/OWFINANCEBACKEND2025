<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Entities\Provider;

class Transaction extends Model
{
    use HasFactory, SoftDeletes, Notifiable;

    protected $fillable = [
        'name',
        'amount',
        'description',
        'date',
        'active',
        'deleted_at',
        'provider_id',
        'url_file',
        'rate_id',
        'amount_tax',
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d',
        'updated_at' => 'datetime:Y-m-d',
        'deleted_at' => 'datetime:Y-m-d',
        'date'       => 'datetime:Y-m-d H:i:s',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\TransactionFactory::new();
    }
        /**
     * Asocia un rate por monto. Si no existe, lo crea y lo asocia.
     * @param float $rateAmount
     * @param string|null $rateName
     * @param string|null $rateDate
     * @return void
     */
    public function setRateByAmount($rateAmount, $rateName = null, $rateDate = null)
    {
        $rate = \App\Models\Entities\Rate::firstOrCreate([
            'name' => $rateName ?? 'Auto',
            'date' => $rateDate ?? now()->toDateString(),
        ], [
            'active' => true,
        ]);
        $this->rate_id = $rate->id;
        $this->save();
    }

    /**
     * Setter para asignar el monto del rate en vez del id.
     * Si no existe un rate con ese monto, lo crea.
     * @param float $value
     */
    public function setRateAmountAttribute($value)
    {
        $rate = \App\Models\Entities\Rate::firstOrCreate([
            'name' => 'Auto',
            'date' => now()->toDateString(),
        ], [
            'active' => true,
        ]);
        $this->attributes['rate_id'] = $rate->id;
        // Puedes guardar el monto en otro campo si lo necesitas
    }
}
