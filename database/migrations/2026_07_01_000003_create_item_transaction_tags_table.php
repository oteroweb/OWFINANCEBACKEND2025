<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_transaction_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_transaction_id')->constrained('item_transactions')->onDelete('cascade');
            $table->foreignId('tag_id')->constrained('tags')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['item_transaction_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_transaction_tags');
    }
};
