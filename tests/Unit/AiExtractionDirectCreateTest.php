<?php

namespace Tests\Unit;

use App\Http\Controllers\AI\AiExtractionController;
use Tests\TestCase;

class AiExtractionDirectCreateTest extends TestCase
{
    private function detect(string $text): bool
    {
        $controller = new AiExtractionController();
        $method = new \ReflectionMethod($controller, 'detectDirectCreateCommand');
        $method->setAccessible(true);

        return $method->invoke($controller, $text);
    }

    /** @dataProvider positiveCases */
    public function test_detects_direct_create_phrases(string $text)
    {
        $this->assertTrue($this->detect($text));
    }

    public static function positiveCases(): array
    {
        return [
            ['Gasté 15 dólares en dulces, crea directo'],
            ['Gasté 15 dólares en dulces créalo directo'],
            ['creá directo'],
            ['guarda directo'],
            ['guárdalo directo'],
            ['registra directo'],
            ['regístralo directo'],
            ['confirma directo'],
            ['CREA DIRECTO'],
        ];
    }

    /** @dataProvider negativeCases */
    public function test_does_not_detect_unrelated_phrases(string $text)
    {
        $this->assertFalse($this->detect($text));
    }

    public static function negativeCases(): array
    {
        return [
            ['Gasté 15 dólares en dulces'],
            ['crea una categoría nueva'],
            ['directo al grano, gasté 10 dólares'],
            ['guarda esto para después'],
            [''],
        ];
    }
}
