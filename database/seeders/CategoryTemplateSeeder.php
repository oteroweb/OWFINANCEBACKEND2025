<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Entities\CategoryTemplate;
use App\Models\Entities\JarTemplate;

class CategoryTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $defs = [
            ['name' => 'Ingresos', 'slug' => 'ingresos', 'parent_slug' => null, 'sort_order' => 1],
            ['name' => 'Gastos', 'slug' => 'gastos', 'parent_slug' => null, 'sort_order' => 2],
            ['name' => 'Alquiler', 'slug' => 'alquiler', 'parent_slug' => 'gastos', 'sort_order' => 3],
            ['name' => 'Comida', 'slug' => 'comida', 'parent_slug' => 'gastos', 'sort_order' => 4],
            ['name' => 'Educación', 'slug' => 'educacion', 'parent_slug' => 'gastos', 'sort_order' => 5],
            ['name' => 'Ocio', 'slug' => 'ocio', 'parent_slug' => 'gastos', 'sort_order' => 6],
        ];
        foreach ($defs as $d) {
            CategoryTemplate::firstOrCreate(['slug' => $d['slug']], $d);
        }

        // Link some templates to jars by name for demo
        $tpl = JarTemplate::where('slug','moderado')->with('jars')->first();
        if ($tpl) {
            foreach ($tpl->jars as $j) {
                if (str_contains(strtolower($j->name), 'necesidad')) {
                    $ids = CategoryTemplate::whereIn('slug', ['alquiler','comida'])->pluck('id')->toArray();
                    if (!empty($ids)) { $j->categoryTemplates()->syncWithoutDetaching($ids); }
                }
                if (str_contains(strtolower($j->name), 'educ')) {
                    $ids = CategoryTemplate::whereIn('slug', ['educacion'])->pluck('id')->toArray();
                    if (!empty($ids)) { $j->categoryTemplates()->syncWithoutDetaching($ids); }
                }
                if (str_contains(strtolower($j->name), 'diver')) {
                    $ids = CategoryTemplate::whereIn('slug', ['ocio'])->pluck('id')->toArray();
                    if (!empty($ids)) { $j->categoryTemplates()->syncWithoutDetaching($ids); }
                }
            }
        }
    }
}
