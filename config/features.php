<?php

return [
    // Toggle to permanently drop legacy item_transactions.category_id
    // Set FEATURE_DROP_LEGACY_ITEM_TX_CATEGORY=true in your .env to enable
    'drop_legacy_item_tx_category' => env('FEATURE_DROP_LEGACY_ITEM_TX_CATEGORY', false),
];
