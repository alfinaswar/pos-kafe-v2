<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Index DB untuk query tanggal, outlet, status, dan foreign key (P6).
        $indexes = [
            ['sales', 'sales_created_at_index', ['created_at']],
            ['sales', 'sales_outlet_id_index', ['outlet_id']],
            ['sales', 'sales_status_index', ['status']],
            ['sale_items', 'sale_items_sale_id_index', ['sale_id']],
            ['sale_items', 'sale_items_product_id_index', ['product_id']],
            ['inventory_logs', 'inventory_logs_material_id_index', ['material_id']],
            ['inventory_logs', 'inventory_logs_created_at_index', ['created_at']],
            ['purchases', 'purchases_outlet_id_index', ['outlet_id']],
            ['purchases', 'purchases_status_index', ['status']],
            ['cashier_shifts', 'cashier_shifts_outlet_id_index', ['outlet_id']],
            ['cashier_shifts', 'cashier_shifts_status_index', ['status']],
            ['held_orders', 'held_orders_outlet_id_index', ['outlet_id']],
            ['held_orders', 'held_orders_status_index', ['status']],
        ];

        foreach ($indexes as [$table, $name, $columns]) {
            if (Schema::hasTable($table)) {
                $exists = collect(DB::select("SHOW INDEX FROM `{$table}`"))
                    ->contains(fn ($i) => $i->Key_name === $name);
                if (!$exists) {
                    Schema::table($table, fn (Blueprint $t) => $t->index($columns, $name));
                }
            }
        }

        // 2. Loyalty: riwayat poin + konfigurasi program (P4).
        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->string('type', 20); // earn | redeem | adjust
            $table->integer('points');
            $table->string('notes', 255)->nullable();
            $table->timestamps();
            $table->index(['customer_id', 'created_at']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        DB::table('settings')->insert([
            ['key' => 'loyalty_rate', 'value' => '1000', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'loyalty_redeem_value', 'value' => '100', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_transactions');
        Schema::dropIfExists('settings');

        $indexes = [
            ['sales', 'sales_created_at_index'],
            ['sales', 'sales_outlet_id_index'],
            ['sales', 'sales_status_index'],
            ['sale_items', 'sale_items_sale_id_index'],
            ['sale_items', 'sale_items_product_id_index'],
            ['inventory_logs', 'inventory_logs_material_id_index'],
            ['inventory_logs', 'inventory_logs_created_at_index'],
            ['purchases', 'purchases_outlet_id_index'],
            ['purchases', 'purchases_status_index'],
            ['cashier_shifts', 'cashier_shifts_outlet_id_index'],
            ['cashier_shifts', 'cashier_shifts_status_index'],
            ['held_orders', 'held_orders_outlet_id_index'],
            ['held_orders', 'held_orders_status_index'],
        ];

        foreach ($indexes as [$table, $name]) {
            if (Schema::hasTable($table)) {
                $exists = collect(DB::select("SHOW INDEX FROM `{$table}`"))
                    ->contains(fn ($i) => $i->Key_name === $name);
                if ($exists) {
                    Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
                }
            }
        }
    }
};
