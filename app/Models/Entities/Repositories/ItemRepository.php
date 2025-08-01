<?php

namespace App\Models\Entities\Repositories;

use App\Models\Entities\Item;

class ItemRepository
{
    public function all()
    {
        return Item::all();
    }
    // Agrega aquí métodos personalizados según tus necesidades
}
