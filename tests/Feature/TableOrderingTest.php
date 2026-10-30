<?php

namespace Tests\Feature;

use App\Models\Outlet;
use App\Models\Product;
use App\Models\TableSession;
use App\Models\CafeTable;
use App\Models\User;
use App\Models\CashierShift;
use App\Models\Sale;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableOrderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_view_menu_for_valid_table_token(): void
    {
        $outlet = Outlet::create(['name' => 'Outlet Test']);
        $table = CafeTable::create(['outlet_id' => $outlet->id, 'name' => 'Meja 1', 'capacity' => 4]);
        Product::create(['name' => 'Kopi Test', 'category' => 'Coffee', 'price' => 10000, 'detail' => 'Menu test']);

        $response = $this->get('/order/' . $table->qr_token);

        $response->assertOk()->assertSee('Kopi Test')->assertSee('Meja 1');
    }

    public function test_customer_can_submit_order_and_server_recalculates_price(): void
    {
        $outlet = Outlet::create(['name' => 'Outlet Test']);
        $table = CafeTable::create(['outlet_id' => $outlet->id, 'name' => 'Meja 1', 'capacity' => 4]);
        $product = Product::create(['name' => 'Kopi Test', 'category' => 'Coffee', 'price' => 10000, 'detail' => 'Menu test']);

        $response = $this->postJson('/order/' . $table->qr_token, [
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'price' => 1]],
            'notes' => 'Kurang gula',
        ]);

        $response->assertCreated()->assertJsonPath('total', '20000.00');
        $this->assertDatabaseHas('customer_orders', ['table_session_id' => TableSession::first()->id, 'total' => 20000]);
    }

    public function test_cashier_can_accept_table_order(): void
    {
        $outlet = Outlet::create(['name' => 'Outlet Test']);
        $user = User::create([
            'name' => 'Kasir Test', 'email' => 'kasir@test.com', 'username' => 'kasir-test',
            'password' => Hash::make('password'), 'outlet_id' => $outlet->id,
        ]);
        $permission = Permission::firstOrCreate(['name' => 'pos-access']);
        $role = Role::firstOrCreate(['name' => 'Cashier']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);

        $table = CafeTable::create(['outlet_id' => $outlet->id, 'name' => 'Meja 1', 'capacity' => 4]);
        $session = TableSession::create(['cafe_table_id' => $table->id]);
        $order = $session->orders()->create(['order_number' => 'QR-TEST-001', 'total' => 10000]);

        $response = $this->actingAs($user)->patchJson('/table-orders/' . $order->id . '/status', ['status' => 'accepted']);

        $response->assertOk()->assertJsonPath('status', 'accepted');
        $this->assertDatabaseHas('customer_orders', ['id' => $order->id, 'status' => 'accepted']);
    }

    public function test_cashier_can_checkout_accepted_table_order_into_sale(): void
    {
        $outlet = Outlet::create(['name' => 'Outlet Test']);
        $user = User::create([
            'name' => 'Kasir Test', 'email' => 'checkout@test.com', 'username' => 'kasir-checkout',
            'password' => Hash::make('password'), 'outlet_id' => $outlet->id,
        ]);
        $permission = Permission::firstOrCreate(['name' => 'pos-access']);
        $role = Role::firstOrCreate(['name' => 'Cashier']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);
        $shift = CashierShift::create(['user_id' => $user->id, 'outlet_id' => $outlet->id, 'start_cash' => 100000, 'status' => 'open']);

        $product = Product::create(['name' => 'Kopi Test', 'category' => 'Coffee', 'price' => 10000, 'detail' => 'Menu test']);
        $table = CafeTable::create(['outlet_id' => $outlet->id, 'name' => 'Meja 1', 'capacity' => 4]);
        $session = TableSession::create(['cafe_table_id' => $table->id]);
        $order = $session->orders()->create(['order_number' => 'QR-TEST-002', 'status' => 'accepted', 'total' => 20000]);
        $order->items()->create(['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 10000, 'subtotal' => 20000]);

        $response = $this->actingAs($user)->postJson('/table-orders/' . $order->id . '/checkout', ['payment_amount' => 20000, 'payment_method' => 'cash']);

        $response->assertCreated()->assertJsonPath('sale_id', Sale::first()->id);
        $this->assertDatabaseHas('sales', ['shift_id' => $shift->id, 'total_price' => 20000]);
        $this->assertDatabaseHas('customer_orders', ['id' => $order->id, 'status' => 'completed']);
    }
}
