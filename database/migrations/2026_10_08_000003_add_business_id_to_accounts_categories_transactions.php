<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// OWF-370: business_id NULL = contabilidad personal de siempre (sin ningún cambio para
// los datos existentes); con valor = pertenece a la contabilidad de esa empresa.
return new class extends Migration
{
    private array $tables = ['accounts', 'categories', 'transactions'];

    public function up(): void
    {
        foreach ($this->tables as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->foreign('business_id')->references('id')->on('businesses')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $name) {
            Schema::table($name, function (Blueprint $table) use ($name) {
                $table->dropForeign([ 'business_id' ]);
                $table->dropIndex($name . '_business_id_index');
                $table->dropColumn('business_id');
            });
        }
    }
};
