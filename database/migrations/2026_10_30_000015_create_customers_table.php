<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id(); $table->foreignId('outlet_id')->nullable()->constrained('outlets')->nullOnDelete(); $table->string('name'); $table->string('phone', 30)->nullable()->index(); $table->string('email')->nullable(); $table->unsignedInteger('loyalty_points')->default(0); $table->enum('member_level', ['regular', 'silver', 'gold', 'platinum'])->default('regular'); $table->timestamps();
        });
        Schema::table('sales', fn (Blueprint $table) => $table->foreignId('customer_id')->nullable()->after('user_id')->constrained('customers')->nullOnDelete());
    }
    public function down(): void { Schema::table('sales', fn (Blueprint $table) => $table->dropForeign(['customer_id'])->dropColumn('customer_id')); Schema::dropIfExists('customers'); }
};
