<?php

namespace App\Models\Entities\Repositories;

use App\Models\Entities\PaymentTransaction;

class PaymentTransactionRepository
{
    public function all()
    {
        return PaymentTransaction::all();
    }
    // Métodos personalizados aquí
}
