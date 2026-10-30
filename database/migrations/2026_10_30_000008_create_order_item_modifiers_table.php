<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_item_modifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_order_item_id')->constrained('customer_order_items')->cascadeOnDelete();
            $table->string('key', 30);
            $table->string('value', 100);
            $table->timestamps();
            $table->unique(['customer_order_item_id', 'key', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_modifiers');
    }
};
