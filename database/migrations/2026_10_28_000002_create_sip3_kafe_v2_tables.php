<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Outlets (Multi-Outlet Support)
        Schema::create('outlets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        // Add username and outlet_id to users if not present
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->nullable()->unique()->after('email');
            }
            if (!Schema::hasColumn('users', 'outlet_id')) {
                $table->foreignId('outlet_id')->nullable()->after('remember_token')->constrained('outlets')->nullOnDelete();
            }
        });

        // 2. Cashier Shifts (Manajemen Shift Kasir & Rekonsiliasi)
        Schema::create('cashier_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('outlet_id')->nullable()->constrained('outlets')->nullOnDelete();
            $table->decimal('start_cash', 14, 2)->default(0);
            $table->decimal('end_cash', 14, 2)->nullable();
            $table->decimal('total_sales_cash', 14, 2)->default(0);
            $table->decimal('total_sales_non_cash', 14, 2)->default(0);
            $table->decimal('expected_cash', 14, 2)->default(0);
            $table->decimal('actual_cash', 14, 2)->nullable();
            $table->decimal('variance', 14, 2)->default(0);
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 3. Petty Cash (Kas Kecil Operasional Shift)
        Schema::create('petty_cash', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('shift_id')->constrained('cashier_shifts')->onDelete('cascade');
            $table->enum('type', ['in', 'out']);
            $table->decimal('amount', 14, 2);
            $table->string('description');
            $table->timestamps();
        });

        // 4. Update sales with shift_id
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'shift_id')) {
                $table->foreignId('shift_id')->nullable()->after('user_id')->constrained('cashier_shifts')->nullOnDelete();
            }
        });

        // 5. Update purchase_details with expired_date
        Schema::table('purchase_details', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_details', 'expired_date')) {
                $table->date('expired_date')->nullable()->after('subtotal');
            }
        });

        // 6. Material Batches (FIFO Tracking & Expiry)
        Schema::create('material_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
            $table->foreignId('purchase_id')->nullable()->constrained('purchases')->nullOnDelete();
            $table->string('batch_number')->nullable();
            $table->decimal('initial_qty', 12, 2); // dalam recipe_unit
            $table->decimal('remaining_qty', 12, 2); // dalam recipe_unit
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->date('expired_date')->nullable();
            $table->timestamps();
        });

        // 7. Update inventory_logs with batch_id
        Schema::table('inventory_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_logs', 'batch_id')) {
                $table->foreignId('batch_id')->nullable()->after('material_id')->constrained('material_batches')->nullOnDelete();
            }
        });

        // 8. Activity Logs (Audit Trail)
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->text('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');

        Schema::table('inventory_logs', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_logs', 'batch_id')) {
                $table->dropForeign(['batch_id']);
                $table->dropColumn('batch_id');
            }
        });

        Schema::dropIfExists('material_batches');

        Schema::table('purchase_details', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_details', 'expired_date')) {
                $table->dropColumn('expired_date');
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'shift_id')) {
                $table->dropForeign(['shift_id']);
                $table->dropColumn('shift_id');
            }
        });

        Schema::dropIfExists('petty_cash');
        Schema::dropIfExists('cashier_shifts');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'outlet_id')) {
                $table->dropForeign(['outlet_id']);
                $table->dropColumn('outlet_id');
            }
            if (Schema::hasColumn('users', 'username')) {
                $table->dropColumn('username');
            }
        });

        Schema::dropIfExists('outlets');
    }
};
