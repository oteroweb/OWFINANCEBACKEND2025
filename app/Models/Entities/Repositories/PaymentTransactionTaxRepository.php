<?php

namespace App\Models\Entities\Repositories;

use App\Models\Entities\PaymentTransactionTax;

class PaymentTransactionTaxRepository
{
    public function all()
    {
        return PaymentTransactionTax::all();
    }
    // Métodos personalizados aquí
}
