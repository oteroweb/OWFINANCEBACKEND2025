<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Entities\JarTemplate;
use App\Models\Entities\JarTemplateJar;
use App\Models\Entities\Category;

class JarTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure some categories exist (fallback to factory or simple creates)
        if (Category::count() < 6) {
            $needed = 6 - Category::count();
            \App\Models\Entities\Category::factory()->count($needed)->create();
        }
    // Preload a few categories to attach to template jars for demo
    $catIds = Category::orderBy('id')->limit(6)->pluck('id')->toArray();

        $defs = [
            [
                'name' => 'Moderado', 'slug' => 'moderado', 'description' => 'Distribución equilibrada', 'active' => 1,
                'jars' => [
                    ['name' => 'Necesidades básicas', 'type' => 'percent', 'percent' => 55, 'base_scope' => 'all_income', 'color' => '#6B7280', 'sort_order' => 1],
                    ['name' => 'Diversión', 'type' => 'percent', 'percent' => 10, 'base_scope' => 'all_income', 'color' => '#F59E0B', 'sort_order' => 2],
                    ['name' => 'Ahorro', 'type' => 'percent', 'percent' => 10, 'base_scope' => 'all_income', 'color' => '#10B981', 'sort_order' => 3],
                    ['name' => 'Educación', 'type' => 'percent', 'percent' => 10, 'base_scope' => 'all_income', 'color' => '#3B82F6', 'sort_order' => 4],
                    ['name' => 'Reservas', 'type' => 'percent', 'percent' => 10, 'base_scope' => 'all_income', 'color' => '#8B5CF6', 'sort_order' => 5],
                    ['name' => 'Caridad y regalos', 'type' => 'percent', 'percent' => 5, 'base_scope' => 'all_income', 'color' => '#EF4444', 'sort_order' => 6],
                ],
            ],
            [
                'name' => 'Avanzado', 'slug' => 'avanzado', 'description' => 'Más categorías, coche 5% y reparto detallado', 'active' => 1,
                'jars' => [
                    ['name' => 'Necesidades básicas', 'type' => 'percent', 'percent' => 50, 'base_scope' => 'all_income', 'color' => '#6B7280', 'sort_order' => 1],
                    ['name' => 'Salud', 'type' => 'percent', 'percent' => 10, 'base_scope' => 'all_income', 'color' => '#EF4444', 'sort_order' => 2],
                    ['name' => 'Educación', 'type' => 'percent', 'percent' => 10, 'base_scope' => 'all_income', 'color' => '#3B82F6', 'sort_order' => 3],
                    ['name' => 'Empresa', 'type' => 'percent', 'percent' => 10, 'base_scope' => 'all_income', 'color' => '#8B5CF6', 'sort_order' => 4],
                    ['name' => 'Coche / Auto y transporte', 'type' => 'percent', 'percent' => 5, 'base_scope' => 'all_income', 'color' => '#F59E0B', 'sort_order' => 5],
                    ['name' => 'Hogar cómodo', 'type' => 'percent', 'percent' => 5, 'base_scope' => 'all_income', 'color' => '#10B981', 'sort_order' => 6],
                    ['name' => 'Ocio / Diversión', 'type' => 'percent', 'percent' => 5, 'base_scope' => 'all_income', 'color' => '#FCD34D', 'sort_order' => 7],
                    ['name' => 'Viajes', 'type' => 'percent', 'percent' => 5, 'base_scope' => 'all_income', 'color' => '#60A5FA', 'sort_order' => 8],
                ],
            ],
            [
                'name' => 'Conservador', 'slug' => 'conservador', 'description' => 'Más peso a necesidades y ahorro', 'active' => 1,
                'jars' => [
                    ['name' => 'Necesidades básicas', 'type' => 'percent', 'percent' => 60, 'base_scope' => 'all_income', 'color' => '#6B7280', 'sort_order' => 1],
                    ['name' => 'Ahorro', 'type' => 'percent', 'percent' => 15, 'base_scope' => 'all_income', 'color' => '#10B981', 'sort_order' => 2],
                    ['name' => 'Reservas', 'type' => 'percent', 'percent' => 10, 'base_scope' => 'all_income', 'color' => '#8B5CF6', 'sort_order' => 3],
                    ['name' => 'Educación', 'type' => 'percent', 'percent' => 5, 'base_scope' => 'all_income', 'color' => '#3B82F6', 'sort_order' => 4],
                    ['name' => 'Diversión', 'type' => 'percent', 'percent' => 5, 'base_scope' => 'all_income', 'color' => '#F59E0B', 'sort_order' => 5],
                    ['name' => 'Caridad y regalos', 'type' => 'percent', 'percent' => 5, 'base_scope' => 'all_income', 'color' => '#EF4444', 'sort_order' => 6],
                ],
            ],
            [
                'name' => 'Arriesgado', 'slug' => 'arriesgado', 'description' => 'Más ocio y metas', 'active' => 1,
                'jars' => [
                    ['name' => 'Necesidades básicas', 'type' => 'percent', 'percent' => 40, 'base_scope' => 'all_income', 'color' => '#6B7280', 'sort_order' => 1],
                    ['name' => 'Diversión', 'type' => 'percent', 'percent' => 20, 'base_scope' => 'all_income', 'color' => '#F59E0B', 'sort_order' => 2],
                    ['name' => 'Ahorro', 'type' => 'percent', 'percent' => 15, 'base_scope' => 'all_income', 'color' => '#10B981', 'sort_order' => 3],
                    ['name' => 'Educación', 'type' => 'percent', 'percent' => 10, 'base_scope' => 'all_income', 'color' => '#3B82F6', 'sort_order' => 4],
                    ['name' => 'Reservas', 'type' => 'percent', 'percent' => 10, 'base_scope' => 'all_income', 'color' => '#8B5CF6', 'sort_order' => 5],
                    ['name' => 'Caridad y regalos', 'type' => 'percent', 'percent' => 5, 'base_scope' => 'all_income', 'color' => '#EF4444', 'sort_order' => 6],
                ],
            ],
        ];

        foreach ($defs as $tpl) {
            $template = JarTemplate::firstOrCreate(['slug' => $tpl['slug']], [
                'name' => $tpl['name'],
                'description' => $tpl['description'] ?? null,
                'active' => $tpl['active'] ?? 1,
            ]);
            $tplJars = [];
            foreach ($tpl['jars'] as $i => $j) {
                $tplJars[] = JarTemplateJar::firstOrCreate([
                    'jar_template_id' => $template->id,
                    'name' => $j['name'],
                ], [
                    'type' => $j['type'],
                    'percent' => $j['percent'] ?? null,
                    'fixed_amount' => $j['fixed_amount'] ?? null,
                    'base_scope' => $j['base_scope'] ?? 'all_income',
                    'color' => $j['color'] ?? null,
                    'sort_order' => $j['sort_order'] ?? 0,
                ]);
            }
            // Attach a simple mapping of categories to first few jars (demo purposes)
            if (!empty($catIds)) {
                foreach (array_slice($tplJars, 0, min(3, count($tplJars))) as $idx => $tplJar) {
                    $attach = array_slice($catIds, $idx, 1);
                    if (!empty($attach)) {
                        $tplJar->categories()->syncWithoutDetaching($attach);
                        if ($tplJar->base_scope === 'categories') {
                            $tplJar->baseCategories()->syncWithoutDetaching($attach);
                        }
                    }
                }
            }
        }
    }
}
