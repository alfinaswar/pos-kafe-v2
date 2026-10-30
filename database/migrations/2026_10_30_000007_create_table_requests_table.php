<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_session_id')->constrained('table_sessions')->cascadeOnDelete();
            $table->enum('type', ['call_cashier', 'request_bill']);
            $table->enum('status', ['pending', 'handled', 'cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_requests');
    }
};
