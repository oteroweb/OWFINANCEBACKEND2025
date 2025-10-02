<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Entities\Provider;
use App\Models\Entities\Rate;
use App\Models\Entities\User;
use App\Models\Entities\Account;
use App\Models\Entities\ItemTransaction;
use App\Models\Entities\PaymentTransaction;

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
        'transaction_type_id',
        'user_id',
        'account_id',
        'category_id',
        'amount_tax',
    'include_in_balance',
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d',
        'updated_at' => 'datetime:Y-m-d',
        'deleted_at' => 'datetime:Y-m-d',
        'date'       => 'datetime:Y-m-d H:i:s',
    'include_in_balance' => 'boolean',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function rate() {
        return $this->belongsTo(Rate::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function account() {
        return $this->belongsTo(Account::class);
    }

    public function transactionType()
    {
        return $this->belongsTo(TransactionType::class, 'transaction_type_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Relationship: a transaction has many item transactions (invoice lines)
    public function itemTransactions()
    {
        return $this->hasMany(ItemTransaction::class);
    }

    // Relationship: a transaction can be paid with multiple payment transactions (split payments)
    public function paymentTransactions()
    {
        return $this->hasMany(PaymentTransaction::class);
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
