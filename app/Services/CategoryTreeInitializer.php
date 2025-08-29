<?php

namespace App\Services;

use App\Models\Entities\Category;
use App\Models\Entities\TransactionType;
use Illuminate\Support\Facades\DB;

class CategoryTreeInitializer
{
    /**
     * Default tree blueprint. Each node: [name => string, children => array]
     */
    public static function defaultTree(): array
    {
        return [
            [ 'name' => 'Ingresos', 'icon' => 'trending_up', 'flow' => 'income', 'children' => [
                ['name' => 'Salario', 'icon' => 'work'],
                ['name' => 'Negocios / Freelance', 'icon' => 'business_center'],
                ['name' => 'Inversiones', 'icon' => 'show_chart'],
                ['name' => 'Rentas / Alquileres', 'icon' => 'home'],
                ['name' => 'Venta de bienes / servicios', 'icon' => 'sell'],
                ['name' => 'Regalos / Donaciones recibidas', 'icon' => 'card_giftcard'],
                ['name' => 'Reembolsos / Devoluciones', 'icon' => 'undo'],
                ['name' => 'Otros ingresos', 'icon' => 'payments'],
            ]],
            [ 'name' => 'Gastos', 'icon' => 'trending_down', 'flow' => 'expense', 'children' => [
                ['name' => 'Hogar', 'icon' => 'home', 'children' => [
                    ['name' => 'Alquiler', 'icon' => 'home'],
                    ['name' => 'Hipoteca', 'icon' => 'account_balance'],
                    ['name' => 'Servicios (Luz, Agua, Gas, Internet, Teléfono)', 'icon' => 'bolt'],
                    ['name' => 'Comunidad / Impuestos', 'icon' => 'gavel'],
                    ['name' => 'Seguros', 'icon' => 'policy'],
                    ['name' => 'Decoración / Mantenimiento', 'icon' => 'handyman'],
                    ['name' => 'Compras comodidad hogar', 'icon' => 'shopping_cart'],
                ]],
                ['name' => 'Alimentación', 'icon' => 'restaurant', 'children' => [
                    ['name' => 'Supermercado', 'icon' => 'local_grocery_store'],
                    ['name' => 'Comida en restaurantes', 'icon' => 'restaurant'],
                    ['name' => 'Comida calle / rápida', 'icon' => 'fastfood'],
                    ['name' => 'Café / Snacks', 'icon' => 'local_cafe'],
                ]],
                ['name' => 'Transporte', 'icon' => 'directions_car', 'children' => [
                    ['name' => 'Transporte público', 'icon' => 'directions_bus'],
                    ['name' => 'Combustible / Gasolina', 'icon' => 'local_gas_station'],
                    ['name' => 'Mantenimiento / Taller', 'icon' => 'build'],
                    ['name' => 'Seguros vehiculares', 'icon' => 'policy'],
                    ['name' => 'Impuestos vehiculares', 'icon' => 'receipt_long'],
                ]],
                ['name' => 'Salud y Bienestar', 'icon' => 'health_and_safety', 'children' => [
                    ['name' => 'Seguro médico', 'icon' => 'health_and_safety'],
                    ['name' => 'Consultas / Salud médica', 'icon' => 'medical_services'],
                    ['name' => 'Farmacia / Medicamentos', 'icon' => 'local_pharmacy'],
                    ['name' => 'Suplementos', 'icon' => 'medication'],
                    ['name' => 'Gimnasio', 'icon' => 'fitness_center'],
                    ['name' => 'Bienestar personal (peluquería, spa, etc.)', 'icon' => 'spa'],
                ]],
                ['name' => 'Educación', 'icon' => 'school', 'children' => [
                    ['name' => 'Colegaturas', 'icon' => 'school'],
                    ['name' => 'Cursos / Talleres', 'icon' => 'menu_book'],
                    ['name' => 'Libros y materiales', 'icon' => 'book'],
                ]],
                ['name' => 'Ocio y Entretenimiento', 'icon' => 'theaters', 'children' => [
                    ['name' => 'Cine / Música / Streaming', 'icon' => 'subscriptions'],
                    ['name' => 'Viajes', 'icon' => 'flight'],
                    ['name' => 'Eventos sociales', 'icon' => 'event'],
                    ['name' => 'Compras personales (ropa, gadgets, etc.)', 'icon' => 'shopping_basket'],
                ]],
                ['name' => 'Donaciones', 'icon' => 'favorite', 'children' => [
                    ['name' => 'Familia', 'icon' => 'family_restroom'],
                    ['name' => 'Benéficas', 'icon' => 'volunteer_activism'],
                ]],
                ['name' => 'Finanzas', 'icon' => 'account_balance_wallet', 'children' => [
                    ['name' => 'Tarjetas de crédito (intereses, comisiones)', 'icon' => 'credit_card'],
                    ['name' => 'Impuestos financieros', 'icon' => 'request_quote'],
                    ['name' => 'Comisiones bancarias', 'icon' => 'account_balance'],
                    ['name' => 'Retirada de efectivo', 'icon' => 'atm'],
                    ['name' => 'Traspasos entre cuentas', 'icon' => 'swap_horiz', 'include_in_balance' => false],
                    ['name' => 'Ajustes de cuenta', 'icon' => 'tune', 'include_in_balance' => false],
                ]],
                ['name' => 'Mascotas', 'icon' => 'pets', 'children' => [
                    ['name' => 'Veterinario', 'icon' => 'pets'],
                    ['name' => 'Comida', 'icon' => 'restaurant'],
                ]],
            ]],
            [ 'name' => 'Ahorro / Inversión', 'icon' => 'savings', 'flow' => 'expense', 'children' => [
                ['name' => 'Fondo de emergencia', 'icon' => 'savings'],
                ['name' => 'Ahorro para retiro', 'icon' => 'savings'],
                ['name' => 'Inversiones (bolsa, cripto, etc.)', 'icon' => 'trending_up'],
                ['name' => 'Compra de activos (casa, coche, etc.)', 'icon' => 'home'],
            ]],
        ];
    }

    /**
     * Initialize default categories for a user. Idempotent by unique (user_id,parent_id,name)
     */
    public function seedForUser(int $userId): void
    {
        DB::transaction(function () use ($userId) {
            $this->createNodes(self::defaultTree(), $userId, null, null);
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
            $this->createNodes(self::defaultTree(), $userId, null, null);
        });
    }

    /**
     * Create or update categories from blueprint nodes recursively.
     * - Assigns icon, type (folder/category), sort_order, include_in_balance.
     * - For leaves, maps flow (income/expense) to transaction_type_id.
     *
     * @param array<int,array<string,mixed>> $nodes
     * @param int $userId
     * @param int|null $parentId
     * @param string|null $inheritedFlow 'income'|'expense'|null
     */
    private function createNodes(array $nodes, int $userId, ?int $parentId, ?string $inheritedFlow): void
    {
        // Lookup transaction type IDs once
        static $txTypeCache = null;
        if ($txTypeCache === null) {
            $txTypeCache = [
                'income' => TransactionType::query()->where('slug', 'income')->value('id'),
                'expense' => TransactionType::query()->where('slug', 'expense')->value('id'),
            ];
        }

        foreach (array_values($nodes) as $index => $node) {
            $name = $node['name'];
            $hasChildren = !empty($node['children']) && is_array($node['children']);
            $type = $hasChildren ? 'folder' : 'category';
            $icon = $node['icon'] ?? null;
            $includeInBalance = array_key_exists('include_in_balance', $node) ? (int) !!$node['include_in_balance'] : 1;
            $flow = $node['flow'] ?? $inheritedFlow; // 'income'|'expense'|null
            $transactionTypeId = null;
            if ($type === 'category' && $flow && isset($txTypeCache[$flow])) {
                $transactionTypeId = $txTypeCache[$flow];
            }

            $category = Category::updateOrCreate(
                [
                    'user_id' => $userId,
                    'parent_id' => $parentId,
                    'name' => $name,
                ],
                [
                    'active' => 1,
                    'type' => $type,
                    'icon' => $icon,
                    'include_in_balance' => $includeInBalance,
                    'sort_order' => $index,
                    'transaction_type_id' => $type === 'category' ? $transactionTypeId : null,
                ]
            );

            if ($hasChildren) {
                $this->createNodes($node['children'], $userId, $category->id, $flow);
            }
        }
    }
}
