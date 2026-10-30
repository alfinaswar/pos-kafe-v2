<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Suppliers
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        // 2. Materials (Bahan Baku & Multi-Unit)
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable();
            $table->decimal('stock', 12, 2)->default(0); // dalam recipe_unit
            $table->string('purchase_unit'); // misal: Karton, Pack, Botol
            $table->string('recipe_unit'); // misal: ml, gram, pcs
            $table->decimal('conversion_factor', 12, 2)->default(1);
            $table->decimal('reorder_point', 12, 2)->default(0);
            $table->timestamps();
        });

        // 3. Update products table for POS Menu
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'category')) {
                $table->string('category')->nullable()->after('name');
            }
            if (!Schema::hasColumn('products', 'price')) {
                $table->decimal('price', 12, 2)->default(0)->after('category');
            }
            if (!Schema::hasColumn('products', 'image')) {
                $table->string('image')->nullable()->after('price');
            }
        });

        // 4. Recipe Items (Bill of Materials)
        Schema::create('recipe_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
            $table->decimal('quantity', 12, 2); // takaran dalam recipe_unit
            $table->timestamps();
        });

        // 5. Purchases (Purchase Orders)
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->string('invoice_number')->unique();
            $table->decimal('total_cost', 14, 2)->default(0);
            $table->enum('status', ['pending', 'approved', 'completed', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 6. Purchase Details
        Schema::create('purchase_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->onDelete('cascade');
            $table->foreignId('material_id')->constrained('materials')->onDelete('restrict');
            $table->decimal('qty_purchase', 12, 2); // jumlah dalam satuan beli
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->timestamps();
        });

        // 7. Sales (Transaksi POS)
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->decimal('total_price', 14, 2);
            $table->decimal('payment_amount', 14, 2);
            $table->decimal('change_amount', 14, 2)->default(0);
            $table->string('payment_method')->default('cash');
            $table->timestamps();
        });

        // 8. Sale Items
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 14, 2);
            $table->timestamps();
        });

        // 9. Inventory Logs (Mutasi Stok & Log FIFO)
        Schema::create('inventory_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
            $table->enum('type', ['in', 'out']);
            $table->decimal('quantity', 12, 2); // dalam recipe_unit
            $table->decimal('stock_after', 12, 2)->default(0);
            $table->string('reference'); // contoh: 'POS Sale #INV-xxx' atau 'PO Receipt #PO-xxx'
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_logs');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('purchase_details');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('recipe_items');
        
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['category', 'price', 'image']);
        });

        Schema::dropIfExists('materials');
        Schema::dropIfExists('suppliers');
    }
};
