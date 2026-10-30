<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Outlet;
use App\Models\Supplier;
use App\Models\Material;
use App\Models\MaterialBatch;
use App\Models\Product;
use App\Models\RecipeItem;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\InventoryLog;
use App\Models\CashierShift;
use App\Models\PettyCash;
use App\Models\ActivityLog;
use Carbon\Carbon;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Outlets
        $outlet = Outlet::firstOrCreate(
            ['name' => 'Kedai Kopi Kaya Raya - Pusat'],
            [
                'address' => 'Jl. Tengku Zainal Abidin No. 1, Kota Pekanbaru',
                'phone' => '0761-123456',
            ]
        );

        // 2. Users untuk tiap Role (Owner, Manager, Cashier)
        $owner = User::firstOrCreate(
            ['email' => 'owner@kayaraya.com'],
            [
                'name' => 'Budi Santoso (Owner)',
                'username' => 'owner',
                'password' => Hash::make('password'),
                'outlet_id' => $outlet->id,
            ]
        );
        $owner->syncRoles(['Owner']);

        $manager = User::firstOrCreate(
            ['email' => 'manager@kayaraya.com'],
            [
                'name' => 'Siti Rahma (Manager)',
                'username' => 'manager',
                'password' => Hash::make('password'),
                'outlet_id' => $outlet->id,
            ]
        );
        $manager->syncRoles(['Manager']);

        $cashier = User::firstOrCreate(
            ['email' => 'kasir@kayaraya.com'],
            [
                'name' => 'Rian Kasir',
                'username' => 'kasir',
                'password' => Hash::make('password'),
                'outlet_id' => $outlet->id,
            ]
        );
        $cashier->syncRoles(['Cashier']);

        // 3. Suppliers
        $supDairy = Supplier::firstOrCreate(
            ['name' => 'PT Sumber Susu Nusantara'],
            ['phone' => '081234567890', 'address' => 'Jl. Soekarno Hatta No. 45, Pekanbaru']
        );

        $supCoffee = Supplier::firstOrCreate(
            ['name' => 'CV Roastery Kopi Mandiri'],
            ['phone' => '081398765432', 'address' => 'Jl. Riau No. 12, Pekanbaru']
        );

        $supPackaging = Supplier::firstOrCreate(
            ['name' => 'Toko Kemasan Abadi'],
            ['phone' => '082155667788', 'address' => 'Jl. Tuanku Tambusai No. 88, Pekanbaru']
        );

        // 4. Raw Materials & Multi-Unit Conversion
        $matSusu = Material::firstOrCreate(
            ['name' => 'Susu UHT Plain'],
            [
                'category' => 'Dairy',
                'stock' => 24000, // 24.000 ml
                'purchase_unit' => 'Karton',
                'recipe_unit' => 'ml',
                'conversion_factor' => 12000,
                'reorder_point' => 5000,
            ]
        );

        $matKopi = Material::firstOrCreate(
            ['name' => 'Biji Kopi Arabika House Blend'],
            [
                'category' => 'Coffee Beans',
                'stock' => 5000, // 5.000 gram
                'purchase_unit' => 'Kg',
                'recipe_unit' => 'gram',
                'conversion_factor' => 1000,
                'reorder_point' => 1500,
            ]
        );

        $matAren = Material::firstOrCreate(
            ['name' => 'Sirup Gula Aren Asli'],
            [
                'category' => 'Syrup',
                'stock' => 10000, // 10.000 ml
                'purchase_unit' => 'Jerigen',
                'recipe_unit' => 'ml',
                'conversion_factor' => 5000,
                'reorder_point' => 2000,
            ]
        );

        $matCup = Material::firstOrCreate(
            ['name' => 'Paper Cup Dingin 16oz + Tutup'],
            [
                'category' => 'Packaging',
                'stock' => 150, // Stok kritis di bawah ROP 200
                'purchase_unit' => 'Dus',
                'recipe_unit' => 'pcs',
                'conversion_factor' => 1000,
                'reorder_point' => 200,
            ]
        );

        $matKaramel = Material::firstOrCreate(
            ['name' => 'Sirup Karamel Gourmet'],
            [
                'category' => 'Syrup',
                'stock' => 1500, // 1.500 ml
                'purchase_unit' => 'Botol',
                'recipe_unit' => 'ml',
                'conversion_factor' => 750,
                'reorder_point' => 500,
            ]
        );

        // 5. Material Batches (FIFO Basis)
        MaterialBatch::firstOrCreate(
            ['batch_number' => 'BATCH-SUSU-001'],
            [
                'material_id' => $matSusu->id,
                'initial_qty' => 24000,
                'remaining_qty' => 24000,
                'purchase_price' => 190000,
                'expired_date' => Carbon::now()->addMonths(4),
            ]
        );

        MaterialBatch::firstOrCreate(
            ['batch_number' => 'BATCH-KOPI-001'],
            [
                'material_id' => $matKopi->id,
                'initial_qty' => 5000,
                'remaining_qty' => 4982,
                'purchase_price' => 150000,
                'expired_date' => Carbon::now()->addMonths(6),
            ]
        );

        MaterialBatch::firstOrCreate(
            ['batch_number' => 'BATCH-AREN-001'],
            [
                'material_id' => $matAren->id,
                'initial_qty' => 10000,
                'remaining_qty' => 10000,
                'purchase_price' => 95000,
                'expired_date' => Carbon::now()->addMonths(3),
            ]
        );

        MaterialBatch::firstOrCreate(
            ['batch_number' => 'BATCH-CUP-001'],
            [
                'material_id' => $matCup->id,
                'initial_qty' => 1000,
                'remaining_qty' => 150,
                'purchase_price' => 450000,
                'expired_date' => Carbon::now()->addYears(2),
            ]
        );

        MaterialBatch::firstOrCreate(
            ['batch_number' => 'BATCH-KRM-001'],
            [
                'material_id' => $matKaramel->id,
                'initial_qty' => 1500,
                'remaining_qty' => 1500,
                'purchase_price' => 85000,
                'expired_date' => Carbon::now()->addDays(5), // Uji notifikasi near-expiry!
            ]
        );

        // 6. Products & BOM
        $prodKopiSusu = Product::firstOrCreate(
            ['name' => 'Es Kopi Susu Kaya Raya'],
            [
                'category' => 'Coffee',
                'price' => 22000,
                'detail' => 'Signature kopi susu dengan gula aren pilihan khas Kedai Kopi Kaya Raya.'
            ]
        );
        RecipeItem::firstOrCreate(['product_id' => $prodKopiSusu->id, 'material_id' => $matKopi->id], ['quantity' => 18]);
        RecipeItem::firstOrCreate(['product_id' => $prodKopiSusu->id, 'material_id' => $matSusu->id], ['quantity' => 120]);
        RecipeItem::firstOrCreate(['product_id' => $prodKopiSusu->id, 'material_id' => $matAren->id], ['quantity' => 25]);
        RecipeItem::firstOrCreate(['product_id' => $prodKopiSusu->id, 'material_id' => $matCup->id], ['quantity' => 1]);

        $prodCaramel = Product::firstOrCreate(
            ['name' => 'Caramel Latte Ice'],
            [
                'category' => 'Coffee',
                'price' => 26000,
                'detail' => 'Espresso berpadu susu segar dan gurihnya sirup karamel gourmet.'
            ]
        );
        RecipeItem::firstOrCreate(['product_id' => $prodCaramel->id, 'material_id' => $matKopi->id], ['quantity' => 18]);
        RecipeItem::firstOrCreate(['product_id' => $prodCaramel->id, 'material_id' => $matSusu->id], ['quantity' => 150]);
        RecipeItem::firstOrCreate(['product_id' => $prodCaramel->id, 'material_id' => $matKaramel->id], ['quantity' => 20]);
        RecipeItem::firstOrCreate(['product_id' => $prodCaramel->id, 'material_id' => $matCup->id], ['quantity' => 1]);

        $prodAmericano = Product::firstOrCreate(
            ['name' => 'Americano Ice'],
            [
                'category' => 'Coffee',
                'price' => 18000,
                'detail' => 'Double shot espresso dengan air dingin segar.'
            ]
        );
        RecipeItem::firstOrCreate(['product_id' => $prodAmericano->id, 'material_id' => $matKopi->id], ['quantity' => 18]);
        RecipeItem::firstOrCreate(['product_id' => $prodAmericano->id, 'material_id' => $matCup->id], ['quantity' => 1]);

        // 7. Purchase Order (PO)
        $poCompleted = Purchase::firstOrCreate(
            ['invoice_number' => 'PO-20261028-0001'],
            [
                'supplier_id' => $supDairy->id,
                'user_id' => $manager->id,
                'total_cost' => 380000,
                'status' => 'completed',
                'notes' => 'Pengadaan rutin susu UHT mingguan.'
            ]
        );
        PurchaseDetail::firstOrCreate(
            ['purchase_id' => $poCompleted->id, 'material_id' => $matSusu->id],
            ['qty_purchase' => 2, 'unit_price' => 190000, 'subtotal' => 380000, 'expired_date' => Carbon::now()->addMonths(4)]
        );

        $poPending = Purchase::firstOrCreate(
            ['invoice_number' => 'PO-20261028-0002'],
            [
                'supplier_id' => $supPackaging->id,
                'user_id' => $manager->id,
                'total_cost' => 450000,
                'status' => 'pending',
                'notes' => 'Restock darurat cup dingin karena stok mendekati ROP.'
            ]
        );
        PurchaseDetail::firstOrCreate(
            ['purchase_id' => $poPending->id, 'material_id' => $matCup->id],
            ['qty_purchase' => 1, 'unit_price' => 450000, 'subtotal' => 450000, 'expired_date' => Carbon::now()->addYears(2)]
        );

        // 8. Cashier Shift & Petty Cash
        $shift = CashierShift::firstOrCreate(
            ['user_id' => $cashier->id, 'status' => 'open'],
            [
                'outlet_id' => $outlet->id,
                'start_cash' => 200000,
                'total_sales_cash' => 48000,
                'total_sales_non_cash' => 0,
                'expected_cash' => 228000, // 200.000 + 48.000 - 20.000
                'actual_cash' => null,
                'variance' => 0,
                'notes' => 'Shift Pagi Kasir'
            ]
        );

        PettyCash::firstOrCreate(
            ['shift_id' => $shift->id, 'description' => 'Beli es batu kristal 2 pack'],
            [
                'user_id' => $cashier->id,
                'type' => 'out',
                'amount' => 20000,
            ]
        );

        // 9. Transaksi POS Dummy (Sales)
        $sale = Sale::firstOrCreate(
            ['invoice_number' => 'INV-202610280915-101'],
            [
                'user_id' => $cashier->id,
                'shift_id' => $shift->id,
                'total_price' => 48000,
                'payment_amount' => 50000,
                'change_amount' => 2000,
                'payment_method' => 'cash',
            ]
        );
        SaleItem::firstOrCreate(
            ['sale_id' => $sale->id, 'product_id' => $prodKopiSusu->id],
            ['quantity' => 1, 'unit_price' => 22000, 'subtotal' => 22000]
        );
        SaleItem::firstOrCreate(
            ['sale_id' => $sale->id, 'product_id' => $prodCaramel->id],
            ['quantity' => 1, 'unit_price' => 26000, 'subtotal' => 26000]
        );

        // 10. Inventory Logs
        InventoryLog::firstOrCreate(
            ['reference' => 'Goods Receipt: PO-20261028-0001'],
            [
                'material_id' => $matSusu->id,
                'type' => 'in',
                'quantity' => 24000,
                'stock_after' => 24000,
            ]
        );
        InventoryLog::firstOrCreate(
            ['reference' => 'POS Sale #INV-202610280915-101 (Es Kopi Susu x1)'],
            [
                'material_id' => $matKopi->id,
                'type' => 'out',
                'quantity' => 18,
                'stock_after' => 4982,
            ]
        );

        // 11. Activity Logs
        ActivityLog::firstOrCreate(
            ['action' => 'SYSTEM_SEED'],
            [
                'user_id' => $owner->id,
                'description' => 'Inisialisasi sistem SIP3-KAFE Commercial Edition',
                'ip_address' => '127.0.0.1',
            ]
        );
    }
}
