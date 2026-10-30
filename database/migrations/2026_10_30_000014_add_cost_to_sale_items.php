<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 12, 2)->default(0)->after('unit_price');
            $table->decimal('cost_total', 14, 2)->default(0)->after('subtotal');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['unit_cost', 'cost_total']);
        });
    }
};
