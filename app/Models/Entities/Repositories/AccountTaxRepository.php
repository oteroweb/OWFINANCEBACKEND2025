<?php

namespace App\Models\Entities\Repositories;

use App\Models\Entities\AccountTax;

class AccountTaxRepository
{
    public function all()
    {
        return AccountTax::all();
    }
    // Métodos personalizados aquí
}
