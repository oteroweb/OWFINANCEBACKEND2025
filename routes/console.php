<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\Entities\Item;
use App\Services\ItemCategoryClassifier;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('items:autocategorize {--all : Include items that already have an item_category_id} {--limit=0 : Limit the number of items to examine} {--dry-run : Do not persist changes, just report}', function () {
    /** @var bool $onlyMissing */
    $onlyMissing = ! $this->option('all');
    $limit = (int) $this->option('limit');
    $dryRun = (bool) $this->option('dry-run');

    $query = Item::query()->orderByDesc('id');
    if ($onlyMissing) {
        $query->whereNull('item_category_id');
    }
    if ($limit > 0) {
        $query->limit($limit);
    }

    $items = $query->get();
    $classifier = app(ItemCategoryClassifier::class);
    $result = $classifier->classifyItems($items, $onlyMissing, persist: ! $dryRun);

    $this->info("Examined: {$result['examined']}, Updated: {$result['updated']}. " . ($dryRun ? '(dry-run)' : ''));
})->purpose('Auto-assign item_category_id on Items using keyword mapping');
