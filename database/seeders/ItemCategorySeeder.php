<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Entities\ItemCategory;

class ItemCategorySeeder extends Seeder
{
    public function run(): void
    {
        $tree = [
            'Alimentación' => [
                'Supermercado' => [
                    'Arroz','Pasta','Pan','Aceite','Azúcar','Leche','Huevos','Verduras','Frutas','Carne de res','Pollo','Pescado'
                ],
                'Restaurantes' => [
                    'Almuerzo ejecutivo','Cena en restaurante','Comida rápida (hamburguesa, pizza)','Bebidas'
                ],
                'Café / Snacks' => [
                    'Café','Té','Galletas','Pasteles','Chocolates'
                ],
            ],
            'Hogar' => [
                'Alquiler / Hipoteca' => ['Pago mensual de vivienda'],
                'Servicios' => ['Electricidad','Agua potable','Gas doméstico','Internet','Teléfono','TV por cable / streaming'],
                'Mantenimiento' => ['Reparación de plomería','Reparación eléctrica','Pintura','Cerrajería'],
                'Compras hogar' => ['Muebles','Electrodomésticos','Ropa de cama','Decoración','Utensilios de cocina'],
            ],
            'Transporte' => [
                'Transporte público' => ['Pasaje bus','Metro','Taxi / Uber'],
                'Vehículo propio' => ['Combustible','Cambio de aceite','Neumáticos','Batería','Seguro anual','Impuesto circulación'],
            ],
            'Salud y Bienestar' => [
                'Farmacia' => ['Analgésicos','Vitaminas','Antibióticos','Productos de higiene personal'],
                'Consultas médicas' => ['General','Odontología','Oftalmología','Pediatría'],
                'Gimnasio' => ['Membresía mensual','Clases grupales','Entrenador personal'],
            ],
        ];

        $create = function ($name, $parentId = null) {
            return ItemCategory::updateOrCreate([
                'name' => $name,
                'parent_id' => $parentId,
            ], [
                'active' => 1,
                'date' => now()->toDateString(),
            ]);
        };

        foreach ($tree as $rootName => $lvl2) {
            $root = $create($rootName, null);
            foreach ($lvl2 as $childName => $lvl3) {
                if (is_array($lvl3)) {
                    $child = $create($childName, $root->id);
                    foreach ($lvl3 as $leafName) {
                        $create($leafName, $child->id);
                    }
                } else {
                    // In case of a simple list
                    $create($childName, $root->id);
                }
            }
        }
    }
}
