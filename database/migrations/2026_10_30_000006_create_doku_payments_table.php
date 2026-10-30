<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doku_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_order_id')->constrained('customer_orders')->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->string('request_id')->unique();
            $table->string('status')->default('pending');
            $table->decimal('amount', 14, 2);
            $table->text('payment_url')->nullable();
            $table->json('provider_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doku_payments');
    }
};
