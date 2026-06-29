<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Entities\Category;
use App\Models\Entities\Jar;

/**
 * Seed the 15 canonical categories from tx-catalog.js as global (user_id=null).
 * Each category is linked to its canonical jar via jar_category pivot.
 * Idempotent: skips categories that already exist by name (user_id=null).
 */
class CanonicalCategorySeeder extends Seeder
{
    // Mirrors OWF_CATEGORIES from tx-catalog.js
    // jarName matches the jar name in the DB (seeded by JarSeeder or user onboarding)
    private array $categories = [
        // Necesidades básicas (55%)
        ['name' => 'Vivienda',        'icon' => 'home',           'jarName' => 'Necesidades básicas', 'kind' => 'expense'],
        ['name' => 'Supermercado',    'icon' => 'shopping_cart',  'jarName' => 'Necesidades básicas', 'kind' => 'expense'],
        ['name' => 'Servicios',       'icon' => 'bolt',           'jarName' => 'Necesidades básicas', 'kind' => 'expense'],
        ['name' => 'Transporte',      'icon' => 'directions_car', 'jarName' => 'Necesidades básicas', 'kind' => 'expense'],
        ['name' => 'Salud',           'icon' => 'favorite',       'jarName' => 'Necesidades básicas', 'kind' => 'expense'],
        // Diversión (10%)
        ['name' => 'Restaurantes',    'icon' => 'restaurant',     'jarName' => 'Diversión',           'kind' => 'expense'],
        ['name' => 'Entretenimiento', 'icon' => 'sports_esports', 'jarName' => 'Diversión',           'kind' => 'expense'],
        ['name' => 'Ropa',            'icon' => 'checkroom',      'jarName' => 'Diversión',           'kind' => 'expense'],
        ['name' => 'Suscripciones',   'icon' => 'subscriptions',  'jarName' => 'Diversión',           'kind' => 'expense'],
        // Educación (10%)
        ['name' => 'Educación',       'icon' => 'school',         'jarName' => 'Educación',           'kind' => 'expense'],
        // Ahorro (10%)
        ['name' => 'Inversión',       'icon' => 'trending_up',    'jarName' => 'Ahorro',              'kind' => 'expense'],
        // Reservas (10%)
        ['name' => 'Otros',           'icon' => 'category',       'jarName' => 'Reservas',            'kind' => 'expense'],
        // Ingresos (no jar)
        ['name' => 'Salario',         'icon' => 'payments',       'jarName' => null,                  'kind' => 'income'],
        ['name' => 'Freelance',       'icon' => 'work',           'jarName' => null,                  'kind' => 'income'],
        ['name' => 'Ingresos',        'icon' => 'savings',        'jarName' => null,                  'kind' => 'income'],
    ];

    public function run(): void
    {
        // Pre-load jars by name (user_id=null = global jars from JarTemplate/Seed)
        $jars = Jar::whereNull('user_id')->get()->keyBy('name');
        // Also include jars with any user_id as fallback (for dev/stage environments)
        if ($jars->isEmpty()) {
            $jars = Jar::all()->keyBy('name');
        }

        foreach ($this->categories as $def) {
            // Skip if already exists as global category
            $existing = Category::whereNull('user_id')
                ->where('name', $def['name'])
                ->first();

            if (!$existing) {
                $existing = Category::create([
                    'name'               => $def['name'],
                    'icon'               => $def['icon'],
                    'user_id'            => null,
                    'active'             => true,
                    'type'               => 'category',
                    'include_in_balance' => true,
                    'sort_order'         => 0,
                ]);
                $this->command->line("  Created: {$def['name']}");
            } else {
                // Update icon in case it changed
                $existing->update(['icon' => $def['icon']]);
                $this->command->line("  Exists:  {$def['name']} (updated icon)");
            }

            // Link to jar if applicable
            if ($def['jarName'] && $jars->has($def['jarName'])) {
                $jar = $jars->get($def['jarName']);
                DB::table('jar_category')
                    ->updateOrInsert(
                        ['jar_id' => $jar->id, 'category_id' => $existing->id],
                        ['created_at' => now(), 'updated_at' => now()]
                    );
                $this->command->line("    → jar: {$def['jarName']} (id={$jar->id})");
            } elseif ($def['jarName']) {
                $this->command->warn("    ⚠ jar '{$def['jarName']}' not found in DB — link skipped");
            }
        }

        $this->command->info('CanonicalCategorySeeder done.');
    }
}
