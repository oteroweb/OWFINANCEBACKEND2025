<?php

namespace App\Services;

use App\Models\Entities\Category;
use Illuminate\Support\Facades\DB;

class CategoryTreeInitializer
{
    /**
     * Default tree blueprint. Each node: [name => string, children => array]
     */
    public static function defaultTree(): array
    {
        return [
            [ 'name' => 'Ingresos', 'children' => [
                ['name' => 'Salario'],
                ['name' => 'Negocios / Freelance'],
                ['name' => 'Inversiones'],
                ['name' => 'Rentas / Alquileres'],
                ['name' => 'Venta de bienes / servicios'],
                ['name' => 'Regalos / Donaciones recibidas'],
                ['name' => 'Reembolsos / Devoluciones'],
                ['name' => 'Otros ingresos'],
            ]],
            [ 'name' => 'Gastos', 'children' => [
                ['name' => 'Hogar', 'children' => [
                    ['name' => 'Alquiler'],
                    ['name' => 'Hipoteca'],
                    ['name' => 'Servicios (Luz, Agua, Gas, Internet, Teléfono)'],
                    ['name' => 'Comunidad / Impuestos'],
                    ['name' => 'Seguros'],
                    ['name' => 'Decoración / Mantenimiento'],
                    ['name' => 'Compras comodidad hogar'],
                ]],
                ['name' => 'Alimentación', 'children' => [
                    ['name' => 'Supermercado'],
                    ['name' => 'Comida en restaurantes'],
                    ['name' => 'Comida calle / rápida'],
                    ['name' => 'Café / Snacks'],
                ]],
                ['name' => 'Transporte', 'children' => [
                    ['name' => 'Transporte público'],
                    ['name' => 'Combustible / Gasolina'],
                    ['name' => 'Mantenimiento / Taller'],
                    ['name' => 'Seguros vehiculares'],
                    ['name' => 'Impuestos vehiculares'],
                ]],
                ['name' => 'Salud y Bienestar', 'children' => [
                    ['name' => 'Seguro médico'],
                    ['name' => 'Consultas / Salud médica'],
                    ['name' => 'Farmacia / Medicamentos'],
                    ['name' => 'Suplementos'],
                    ['name' => 'Gimnasio'],
                    ['name' => 'Bienestar personal (peluquería, spa, etc.)'],
                ]],
                ['name' => 'Educación', 'children' => [
                    ['name' => 'Colegaturas'],
                    ['name' => 'Cursos / Talleres'],
                    ['name' => 'Libros y materiales'],
                ]],
                ['name' => 'Ocio y Entretenimiento', 'children' => [
                    ['name' => 'Cine / Música / Streaming'],
                    ['name' => 'Viajes'],
                    ['name' => 'Eventos sociales'],
                    ['name' => 'Compras personales (ropa, gadgets, etc.)'],
                ]],
                ['name' => 'Donaciones', 'children' => [
                    ['name' => 'Familia'],
                    ['name' => 'Benéficas'],
                ]],
                ['name' => 'Finanzas', 'children' => [
                    ['name' => 'Tarjetas de crédito (intereses, comisiones)'],
                    ['name' => 'Impuestos financieros'],
                    ['name' => 'Comisiones bancarias'],
                    ['name' => 'Retirada de efectivo'],
                    ['name' => 'Traspasos entre cuentas'],
                    ['name' => 'Ajustes de cuenta'],
                ]],
                ['name' => 'Mascotas', 'children' => [
                    ['name' => 'Veterinario'],
                    ['name' => 'Comida'],
                ]],
            ]],
            [ 'name' => 'Ahorro / Inversión', 'children' => [
                ['name' => 'Fondo de emergencia'],
                ['name' => 'Ahorro para retiro'],
                ['name' => 'Inversiones (bolsa, cripto, etc.)'],
                ['name' => 'Compra de activos (casa, coche, etc.)'],
            ]],
        ];
    }

    /**
     * Initialize default categories for a user. Idempotent by unique (user_id,parent_id,name)
     */
    public function seedForUser(int $userId): void
    {
        DB::transaction(function () use ($userId) {
            $this->createNodes(self::defaultTree(), $userId, null);
        });
    }

    /**
     * Reset user's categories: purge all personal categories and reseed defaults.
     */
    public function resetForUser(int $userId): void
    {
        DB::transaction(function () use ($userId) {
            // Hard delete user categories (including soft-deleted) to avoid unique conflicts
            Category::withTrashed()->where('user_id', $userId)->chunkById(500, function ($chunk) {
                /** @var \Illuminate\Support\Collection<int,Category> $chunk */
                foreach ($chunk as $cat) {
                    $cat->forceDelete();
                }
            });
            // Reseed defaults
            $this->createNodes(self::defaultTree(), $userId, null);
        });
    }

    private function createNodes(array $nodes, int $userId, ?int $parentId): void
    {
        foreach ($nodes as $node) {
            $name = $node['name'];
            $category = Category::firstOrCreate([
                'user_id' => $userId,
                'parent_id' => $parentId,
                'name' => $name,
            ], [
                'active' => 1,
            ]);
            if (!empty($node['children']) && is_array($node['children'])) {
                $this->createNodes($node['children'], $userId, $category->id);
            }
        }
    }
}
