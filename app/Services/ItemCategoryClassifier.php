<?php

namespace App\Services;

use App\Models\Entities\Item;
use App\Models\Entities\ItemCategory;
use Illuminate\Support\Collection;

class ItemCategoryClassifier
{
    /**
     * Map of leaf ItemCategory names to arrays of lowercase keywords.
     * Adjust as needed; keep keys in sync with seeded ItemCategory leaves.
     */
    protected array $mapping = [
        // Alimentación > Supermercado
        'Arroz' => ['arroz', 'grano largo', 'arrocera'],
        'Pasta' => ['pasta', 'espagueti', 'fideos', 'macarrones'],
        'Pan' => ['pan', 'panadería', 'baguette', 'arepa', 'arepas'],
        'Aceite' => ['aceite', 'girasol', 'maíz', 'canola', 'oliva'],
        'Azúcar' => ['azucar', 'azúcar'],
        'Leche' => ['leche', 'leche en polvo', 'lacteo', 'lácteo'],
        'Huevos' => ['huevo', 'huevos', 'cartón de huevos'],
        'Verduras' => ['verdura', 'verduras', 'lechuga', 'tomate', 'zanahoria', 'cebolla', 'pimenton', 'pimentón'],
        'Frutas' => ['fruta', 'frutas', 'banana', 'plátano', 'manzana', 'naranja', 'patilla', 'melón'],
        'Carne de res' => ['carne', 'res', 'molida', 'bistec', 'chuleta'],
        'Pollo' => ['pollo', 'muslo', 'pechuga', 'alas'],
        'Pescado' => ['pescado', 'atun', 'atún', 'merluza', 'sardina'],

        // Alimentación > Restaurantes
        'Almuerzo ejecutivo' => ['almuerzo ejecutivo', 'menu del dia', 'menú del día'],
        'Cena en restaurante' => ['cena', 'restaurante', 'comida en restaurante'],
        'Comida rápida (hamburguesa, pizza)' => ['comida rapida', 'rápida', 'hamburguesa', 'pizza', 'pepito', 'perro caliente'],
        'Bebidas' => ['bebida', 'refresco', 'gaseosa', 'jugo', 'soda'],

        // Alimentación > Café / Snacks
        'Café' => ['cafe', 'café', 'americano', 'espresso', 'marron', 'marrón'],
        'Té' => ['te', 'té', 'infusión'],
        'Galletas' => ['galleta', 'galletas'],
        'Pasteles' => ['pastel', 'pasteles', 'torta'],
        'Chocolates' => ['chocolate', 'cacao'],

        // Hogar
        'Pago mensual de vivienda' => ['alquiler', 'hipoteca', 'renta vivienda', 'canon'],
        'Electricidad' => ['electricidad', 'luz', 'corpoelec'],
        'Agua potable' => ['agua', 'hidrológica', 'hidrocapital'],
        'Gas doméstico' => ['gas', 'bombona'],
        'Internet' => ['internet', 'fibra', 'aba', 'cablemodem'],
        'Teléfono' => ['telefono', 'teléfono', 'movilnet', 'digitel', 'movistar'],
        'TV por cable / streaming' => ['cable', 'inter', 'simpletv', 'directv', 'netflix', 'streaming', 'disney+'],
        'Reparación de plomería' => ['plomeria', 'plomería', 'fuga', 'tuberia', 'tubería'],
        'Reparación eléctrica' => ['electricista', 'cableado', 'interruptor'],
        'Pintura' => ['pintura', 'pintor', 'rodillo'],
        'Cerrajería' => ['cerrajeria', 'cerrajería', 'cerradura', 'llave'],
        'Muebles' => ['mueble', 'muebles', 'sofa', 'sofá', 'silla', 'mesa'],
        'Electrodomésticos' => ['electrodomestico', 'electrodoméstico', 'nevera', 'lavadora', 'microondas'],
        'Ropa de cama' => ['sabanas', 'sábanas', 'almohada', 'edredon', 'edredón'],
        'Decoración' => ['decoracion', 'decoración', 'cuadro', 'florero'],
        'Utensilios de cocina' => ['olla', 'sarten', 'sartén', 'cubiertos', 'platos'],

        // Transporte
        'Pasaje bus' => ['pasaje', 'autobus', 'autobús', 'buseta'],
        'Metro' => ['metro', 'subterraneo', 'subterráneo'],
        'Taxi / Uber' => ['taxi', 'uber', 'cabify', 'ridery'],
        'Combustible' => ['combustible', 'gasolina', 'diesel'],
        'Cambio de aceite' => ['aceite motor', 'cambio de aceite', 'lubricante'],
        'Neumáticos' => ['neumatico', 'neumático', 'caucho', 'llanta'],
        'Batería' => ['bateria', 'batería', 'acumulador'],
        'Seguro anual' => ['seguro vehiculo', 'póliza auto', 'seguro auto'],
        'Impuesto circulación' => ['impuesto vehicular', 'impuesto de circulación', 'patente'],

        // Salud y Bienestar
        'Analgésicos' => ['analgesico', 'analgésico', 'dolor', 'acetaminofen', 'acetaminofén', 'ibuprofeno'],
        'Vitaminas' => ['vitamina', 'multivitaminico', 'multivitamínico'],
        'Antibióticos' => ['antibiotico', 'antibiótico', 'amoxicilina', 'azitromicina'],
        'Productos de higiene personal' => ['shampoo', 'champu', 'champú', 'jabón', 'jabon', 'desodorante', 'crema dental'],
        'General' => ['consulta general', 'medico general', 'médico general'],
        'Odontología' => ['odontologia', 'odontología', 'dentista'],
        'Oftalmología' => ['oftalmologia', 'oftalmología', 'lentes', 'optica', 'óptica'],
        'Pediatría' => ['pediatria', 'pediatría', 'pediatra'],
        'Membresía mensual' => ['gimnasio', 'membresia', 'membresía', 'mensualidad gym'],
        'Clases grupales' => ['clases grupales', 'spinning', 'yoga', 'crossfit'],
        'Entrenador personal' => ['entrenador', 'personal trainer'],
    ];

    /**
     * Attempt to classify a single item and return the matched ItemCategory id, or null.
     */
    public function classifyText(?string $text): ?int
    {
        if (!$text) {
            return null;
        }

        $haystack = mb_strtolower($text);

        // Build name -> id map for leaves we know about
        $categoryIds = ItemCategory::query()
            ->whereIn('name', array_keys($this->mapping))
            ->pluck('id', 'name');

        $best = [
            'name' => null,
            'score' => 0,
            'len' => 0,
        ];

        foreach ($this->mapping as $leafName => $keywords) {
            $score = 0;
            $maxLen = 0;
            foreach ($keywords as $kw) {
                $kw = mb_strtolower($kw);
                if (str_contains($haystack, $kw)) {
                    $score++;
                    $maxLen = max($maxLen, mb_strlen($kw));
                }
            }
            if ($score > 0) {
                if ($score > $best['score'] || ($score === $best['score'] && $maxLen > $best['len'])) {
                    $best = ['name' => $leafName, 'score' => $score, 'len' => $maxLen];
                }
            }
        }

        if ($best['name'] && isset($categoryIds[$best['name']])) {
            return (int) $categoryIds[$best['name']];
        }

        return null;
    }

    /**
     * Classify and optionally persist item_category_id for a set of Items.
     *
     * @param Collection<int, Item> $items
     * @return array{updated:int, examined:int}
     */
    public function classifyItems(Collection $items, bool $onlyMissing = true, bool $persist = false): array
    {
        $updated = 0;
        $examined = 0;

        foreach ($items as $item) {
            $examined++;
            if ($onlyMissing && $item->item_category_id) {
                continue;
            }
            $text = $item->custom_name ?: $item->name;
            $catId = $this->classifyText($text);
            if ($catId && $item->item_category_id !== $catId) {
                if ($persist) {
                    $item->item_category_id = $catId;
                    $item->save();
                }
                $updated++;
            }
        }

        return compact('updated', 'examined');
    }
}
