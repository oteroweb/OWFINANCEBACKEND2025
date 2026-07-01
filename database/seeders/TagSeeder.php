<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Entities\Tag;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $systemTags = [
            [
                'slug'        => 'comision',
                'name'        => 'Comisión',
                'description' => 'Cargo adicional cobrado por el banco o plataforma sobre una transacción.',
                'color'       => '#F59E0B',
                'icon'        => 'receipt',
            ],
            [
                'slug'        => 'pago_movil',
                'name'        => 'Pago Móvil',
                'description' => 'Operación realizada vía pago móvil (0.30% BCV u otro cargo fijo).',
                'color'       => '#0EA5E9',
                'icon'        => 'smartphone',
            ],
            [
                'slug'        => 'impulso',
                'name'        => 'Compra Impulsiva',
                'description' => 'Gasto no planificado, decidido en el momento sin evaluación previa.',
                'color'       => '#EF4444',
                'icon'        => 'flash_on',
            ],
            [
                'slug'        => 'planificado',
                'name'        => 'Planificado',
                'description' => 'Gasto o ingreso previsto con anticipación dentro del presupuesto.',
                'color'       => '#10B981',
                'icon'        => 'event',
            ],
            [
                'slug'        => 'recurrente',
                'name'        => 'Recurrente',
                'description' => 'Movimiento que se repite periódicamente (suscripción, servicio mensual, etc.).',
                'color'       => '#3B82F6',
                'icon'        => 'repeat',
            ],
            [
                'slug'        => 'transferencia_interna',
                'name'        => 'Transferencia Interna',
                'description' => 'Movimiento entre cuentas propias, no representa un gasto real.',
                'color'       => '#8B5CF6',
                'icon'        => 'swap_horiz',
            ],
        ];

        foreach ($systemTags as $tag) {
            Tag::updateOrCreate(
                ['slug' => $tag['slug']],
                array_merge($tag, [
                    'type'    => 'system',
                    'user_id' => null,
                    'active'  => 1,
                ])
            );
        }
    }
}
