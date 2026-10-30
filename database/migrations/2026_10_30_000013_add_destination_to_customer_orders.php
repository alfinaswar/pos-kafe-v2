<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_orders', function (Blueprint $table) {
            $table->enum('destination', ['bar', 'kitchen'])->default('kitchen')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('customer_orders', function (Blueprint $table) {
            $table->dropColumn('destination');
        });
    }
};
