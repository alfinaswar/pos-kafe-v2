<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{CafeTable, Customer, Sale, SaleItem, CashierShift, User, Outlet, Product, TableSession, Voucher};

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Reuse idempotent demo dataset so repeated localhost seeding is safe.
        $this->call(DatabaseSeeder::class);
        $outlet = Outlet::where('name', 'Kedai Kopi Kaya Raya - Pusat')->firstOrFail();
        $cashier = User::where('email', 'kasir@kayaraya.com')->firstOrFail();
        foreach (['Meja 1', 'Meja 2', 'Meja 3', 'Meja 4'] as $name) CafeTable::firstOrCreate(['outlet_id' => $outlet->id, 'name' => $name], ['capacity' => 4, 'status' => 'active']);
        $customer = Customer::firstOrCreate(['phone' => '081234567890'], ['outlet_id' => $outlet->id, 'name' => 'Dina Demo', 'email' => 'dina@example.test']);
        Voucher::firstOrCreate(['code' => 'DEMO10'], ['discount_amount' => 10000, 'expires_at' => now()->addMonths(3), 'usage_limit' => 100, 'active' => true]);
        $shift = CashierShift::firstOrCreate(['user_id' => $cashier->id, 'status' => 'open'], ['outlet_id' => $outlet->id, 'start_cash' => 500000, 'total_sales_cash' => 0]);
        $products = Product::orderBy('id')->take(3)->get();
        for ($i = 1; $i <= 10; $i++) {
            $sale = Sale::firstOrCreate(['invoice_number' => 'DEMO-INV-' . str_pad($i, 3, '0', STR_PAD_LEFT)], ['user_id' => $cashier->id, 'customer_id' => $customer->id, 'shift_id' => $shift->id, 'total_price' => 18000 + ($i * 1000), 'payment_amount' => 20000 + ($i * 1000), 'change_amount' => 2000, 'payment_method' => $i % 2 ? 'cash' : 'qris']);
            if ($products->isNotEmpty()) SaleItem::firstOrCreate(['sale_id' => $sale->id, 'product_id' => $products->first()->id], ['quantity' => 1, 'unit_price' => $sale->total_price, 'subtotal' => $sale->total_price]);
        }
    }
}
