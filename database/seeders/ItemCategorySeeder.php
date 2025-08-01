<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Entities\ItemCategory;

class ItemCategorySeeder extends Seeder
{
    public function run(): void
    {
        ItemCategory::factory()->count(10)->create();
    }
}
