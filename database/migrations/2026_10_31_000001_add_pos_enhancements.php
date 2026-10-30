<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Item-level: catatan kasir (mis. "less sugar"), diskon per item, flag cetak ulang struk
        Schema::table('sale_items', function (Blueprint $table) {
            $table->string('notes', 255)->nullable()->after('subtotal');
            $table->decimal('discount', 12, 2)->default(0)->after('notes');
        });

        // Transaksi: voucher code + jumlah cetak ulang struk
        Schema::table('sales', function (Blueprint $table) {
            $table->string('voucher_code', 50)->nullable()->after('payment_method');
            $table->unsignedInteger('print_count')->default(0)->after('voucher_code');
        });

        // Voucher: promo berbasis waktu (mulai berlaku & jam operasional harian)
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dateTime('starts_at')->nullable()->after('expires_at');
            $table->time('active_from')->nullable()->after('starts_at');
            $table->time('active_to')->nullable()->after('active_from');
        });

        // Soft delete untuk master data utama
        foreach (['products', 'materials', 'suppliers', 'outlets'] as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['notes', 'discount']);
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['voucher_code', 'print_count']);
        });
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn(['starts_at', 'active_from', 'active_to']);
        });
        foreach (['products', 'materials', 'suppliers', 'outlets'] as $tableName) {
            if (Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropSoftDeletes();
                });
            }
        }
    }
};
