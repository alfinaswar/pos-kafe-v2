<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('held_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->json('items');
            $table->text('notes')->nullable();
            $table->enum('status', ['held', 'recalled', 'cancelled'])->default('held');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('held_orders');
    }
};
