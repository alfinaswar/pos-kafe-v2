<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Menambahkan outlet_id pada transaksi penjualan agar data dapat
        // dibatasi per outlet (kasir hanya melihat outlet sendiri).
        if (!Schema::hasColumn('sales', 'outlet_id')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->foreignId('outlet_id')->nullable()->after('user_id')
                    ->constrained('outlets')->nullOnDelete();
            });

            // Backfill: ambil outlet dari shift transaksi, fallback ke outlet kasir.
            DB::statement('UPDATE sales SET outlet_id = (SELECT cashier_shifts.outlet_id FROM cashier_shifts WHERE cashier_shifts.id = sales.shift_id) WHERE sales.shift_id IS NOT NULL');
            DB::statement('UPDATE sales SET outlet_id = (SELECT users.outlet_id FROM users WHERE users.id = sales.user_id) WHERE sales.outlet_id IS NULL');
        }

        if (!Schema::hasColumn('sales', 'idempotency_key')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->string('idempotency_key', 64)->nullable()->unique()->after('payment_method');
            });
        }

        // Outlet untuk purchase order agar manager terbatas pada outlet-nya.
        if (!Schema::hasColumn('purchases', 'outlet_id')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->foreignId('outlet_id')->nullable()->after('supplier_id')
                    ->constrained('outlets')->nullOnDelete();
            });

            DB::statement('UPDATE purchases SET outlet_id = (SELECT users.outlet_id FROM users WHERE users.id = purchases.user_id) WHERE purchases.outlet_id IS NULL');
        }
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'idempotency_key')) {
                $table->dropUnique(['idempotency_key']);
                $table->dropColumn('idempotency_key');
            }
            if (Schema::hasColumn('sales', 'outlet_id')) {
                $table->dropForeign(['outlet_id']);
                $table->dropColumn('outlet_id');
            }
        });

        Schema::table('purchases', function (Blueprint $table) {
            if (Schema::hasColumn('purchases', 'outlet_id')) {
                $table->dropForeign(['outlet_id']);
                $table->dropColumn('outlet_id');
            }
        });
    }
};
