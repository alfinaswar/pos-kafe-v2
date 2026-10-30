<?php

namespace Tests\Feature;

use App\Models\Outlet;
use App\Models\Product;
use App\Models\TableSession;
use App\Models\CafeTable;
use App\Models\User;
use App\Models\CashierShift;
use App\Models\Sale;
use App\Models\Material;
use App\Models\MaterialBatch;
use App\Models\InventoryLog;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\PaymentSetting;
use App\Models\DokuPayment;
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

    public function test_table_order_checkout_deducts_recipe_stock_fifo(): void
    {
        $outlet = Outlet::create(['name' => 'Outlet Test']);
        $user = User::create([
            'name' => 'Kasir FIFO', 'email' => 'fifo@test.com', 'username' => 'kasir-fifo',
            'password' => Hash::make('password'), 'outlet_id' => $outlet->id,
        ]);
        $permission = Permission::firstOrCreate(['name' => 'pos-access']);
        $role = Role::firstOrCreate(['name' => 'Cashier']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);
        CashierShift::create(['user_id' => $user->id, 'outlet_id' => $outlet->id, 'start_cash' => 100000, 'status' => 'open']);

        $material = Material::create(['name' => 'Kopi FIFO', 'category' => 'Coffee', 'stock' => 100, 'purchase_unit' => 'Kg', 'recipe_unit' => 'gram', 'conversion_factor' => 1000, 'reorder_point' => 10]);
        $oldBatch = MaterialBatch::create(['material_id' => $material->id, 'initial_qty' => 60, 'remaining_qty' => 60, 'purchase_price' => 100, 'expired_date' => now()->addMonth()]);
        $newBatch = MaterialBatch::create(['material_id' => $material->id, 'initial_qty' => 40, 'remaining_qty' => 40, 'purchase_price' => 120, 'expired_date' => now()->addMonths(2)]);
        $product = Product::create(['name' => 'Kopi FIFO Menu', 'category' => 'Coffee', 'price' => 10000, 'detail' => 'FIFO test']);
        $product->recipeItems()->create(['material_id' => $material->id, 'quantity' => 20]);

        $table = CafeTable::create(['outlet_id' => $outlet->id, 'name' => 'Meja FIFO', 'capacity' => 2]);
        $session = TableSession::create(['cafe_table_id' => $table->id]);
        $order = $session->orders()->create(['order_number' => 'QR-FIFO-001', 'status' => 'accepted', 'total' => 40000]);
        $order->items()->create(['product_id' => $product->id, 'quantity' => 4, 'unit_price' => 10000, 'subtotal' => 40000]);

        $this->actingAs($user)->postJson('/table-orders/' . $order->id . '/checkout', ['payment_amount' => 40000, 'payment_method' => 'cash'])->assertCreated();

        $this->assertDatabaseHas('material_batches', ['id' => $oldBatch->id, 'remaining_qty' => 0]);
        $this->assertDatabaseHas('material_batches', ['id' => $newBatch->id, 'remaining_qty' => 20]);
        $this->assertDatabaseHas('materials', ['id' => $material->id, 'stock' => 20]);
        $this->assertSame(2, InventoryLog::where('reference', 'like', '%QR-FIFO-001%')->count());
    }

    public function test_manager_can_create_table_with_qr_token(): void
    {
        $outlet = Outlet::create(['name' => 'Outlet Test']);
        $user = User::create([
            'name' => 'Manager Test', 'email' => 'manager-table@test.com', 'username' => 'manager-table',
            'password' => Hash::make('password'), 'outlet_id' => $outlet->id,
        ]);
        $permission = Permission::firstOrCreate(['name' => 'table-manage']);
        $role = Role::firstOrCreate(['name' => 'Manager']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);

        $response = $this->actingAs($user)->post('/tables', ['name' => 'Meja Baru', 'capacity' => 4]);

        $response->assertRedirect(route('tables.index'));
        $this->assertDatabaseHas('cafe_tables', ['outlet_id' => $outlet->id, 'name' => 'Meja Baru', 'capacity' => 4, 'status' => 'active']);
        $this->assertNotEmpty(CafeTable::first()->qr_token);
    }

    public function test_manager_can_view_printable_qr_page_for_table(): void
    {
        $outlet = Outlet::create(['name' => 'Outlet Test']);
        $user = User::create([
            'name' => 'Manager QR', 'email' => 'manager-qr@test.com', 'username' => 'manager-qr',
            'password' => Hash::make('password'), 'outlet_id' => $outlet->id,
        ]);
        $permission = Permission::firstOrCreate(['name' => 'table-manage']);
        $role = Role::firstOrCreate(['name' => 'Manager']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);
        $table = CafeTable::create(['outlet_id' => $outlet->id, 'name' => 'Meja QR', 'capacity' => 2]);

        $response = $this->actingAs($user)->get('/tables/' . $table->id . '/qr');

        $response->assertOk()->assertSee($table->qr_token)->assertSee('Meja QR');
    }

    public function test_manager_can_download_valid_qr_image_for_table(): void
    {
        $outlet = Outlet::create(['name' => 'Outlet Test']);
        $user = User::create([
            'name' => 'Manager QR Image', 'email' => 'manager-qr-image@test.com', 'username' => 'manager-qr-image',
            'password' => Hash::make('password'), 'outlet_id' => $outlet->id,
        ]);
        $permission = Permission::firstOrCreate(['name' => 'table-manage']);
        $role = Role::firstOrCreate(['name' => 'Manager']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);
        $table = CafeTable::create(['outlet_id' => $outlet->id, 'name' => 'Meja QR Image', 'capacity' => 2]);

        $response = $this->actingAs($user)->get('/tables/' . $table->id . '/qr/image');

        $response->assertOk()->assertHeader('Content-Type', 'image/svg+xml');
        $this->assertStringContainsString('<svg', $response->getContent());
    }

    public function test_customer_order_marks_table_occupied_and_checkout_frees_it(): void
    {
        $outlet = Outlet::create(['name' => 'Outlet Test']);
        $table = CafeTable::create(['outlet_id' => $outlet->id, 'name' => 'Meja Status', 'capacity' => 2]);
        $product = Product::create(['name' => 'Status Test', 'category' => 'Test', 'price' => 10000, 'detail' => 'Status test']);

        $this->postJson('/order/' . $table->qr_token, [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertCreated();

        $this->assertDatabaseHas('cafe_tables', ['id' => $table->id, 'occupancy_status' => 'occupied']);
    }

    public function test_customer_can_view_order_status_by_session_token(): void
    {
        $outlet = Outlet::create(['name' => 'Status Outlet']); $table = CafeTable::create(['outlet_id' => $outlet->id, 'name' => 'Meja Status Customer', 'capacity' => 2]); $session = TableSession::create(['cafe_table_id' => $table->id]);
        $session->orders()->create(['order_number' => 'STATUS-001', 'status' => 'preparing', 'total' => 10000]);
        $this->getJson('/order/session/' . $session->session_token . '/status')->assertOk()->assertJsonPath('orders.0.status', 'preparing');
    }

    public function test_sale_completion_awards_customer_loyalty_points(): void
    {
        $outlet = Outlet::create(['name' => 'Loyalty Outlet']); $customer = \App\Models\Customer::create(['outlet_id' => $outlet->id, 'name' => 'Loyalty Customer']);
        $this->assertSame(0, $customer->loyalty_points);
        $shift = CashierShift::create(['user_id' => User::factory()->create(['outlet_id' => $outlet->id])->id, 'outlet_id' => $outlet->id, 'start_cash' => 0, 'status' => 'open']);
        $sale = Sale::create(['invoice_number' => 'LOYALTY-001', 'user_id' => $shift->user_id, 'customer_id' => $customer->id, 'shift_id' => $shift->id, 'total_price' => 50000, 'payment_amount' => 50000, 'change_amount' => 0, 'payment_method' => 'cash']);
        app(\App\Services\LoyaltyService::class)->award($sale);
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'loyalty_points' => 50, 'member_level' => 'silver']);
    }

    public function test_owner_can_save_encrypted_doku_settings_without_exposing_secret(): void
    {
        $outlet = Outlet::create(['name' => 'Outlet Test']);
        $user = User::create([
            'name' => 'Owner DOKU', 'email' => 'owner-doku@test.com', 'username' => 'owner-doku',
            'password' => Hash::make('password'), 'outlet_id' => $outlet->id,
        ]);
        $permission = Permission::firstOrCreate(['name' => 'payment-settings']);
        $role = Role::firstOrCreate(['name' => 'Owner']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);

        $response = $this->actingAs($user)->post('/settings/payment', [
            'doku_client_id' => 'MCH-TEST',
            'doku_secret_key' => 'secret-test-value',
            'doku_mode' => 'sandbox',
            'doku_enabled' => '1',
        ]);

        $response->assertRedirect(route('settings.payment'));
        $this->assertSame('MCH-TEST', PaymentSetting::where('key', 'doku_client_id')->value('value'));
        $setting = PaymentSetting::where('key', 'doku_secret_key')->first();
        $this->assertNotSame('secret-test-value', $setting->getRawOriginal('value'));
        $this->assertSame('secret-test-value', $setting->value);
        $response = $this->actingAs($user)->get(route('settings.payment'));
        $response->assertOk()->assertDontSee('secret-test-value');
    }

    public function test_staff_can_view_kitchen_queue_and_update_order_status(): void
    {
        $outlet = Outlet::create(['name' => 'Outlet Test']);
        $user = User::create(['name' => 'Kitchen', 'email' => 'kitchen@test.com', 'username' => 'kitchen', 'password' => Hash::make('password'), 'outlet_id' => $outlet->id]);
        $permission = Permission::firstOrCreate(['name' => 'kitchen-access']);
        $role = Role::firstOrCreate(['name' => 'Kitchen']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);
        $table = CafeTable::create(['outlet_id' => $outlet->id, 'name' => 'Meja KDS', 'capacity' => 2]);
        $session = TableSession::create(['cafe_table_id' => $table->id]);
        $order = $session->orders()->create(['order_number' => 'KDS-001', 'status' => 'accepted', 'total' => 10000]);

        $this->actingAs($user)->get('/kitchen')->assertOk()->assertSee('KDS-001');
        $this->actingAs($user)->patchJson('/kitchen/orders/' . $order->id . '/status', ['status' => 'preparing'])->assertOk();
        $this->assertDatabaseHas('customer_orders', ['id' => $order->id, 'status' => 'preparing']);
    }

    public function test_customer_can_request_cashier_for_open_table_session(): void
    {
        $outlet = Outlet::create(['name' => 'Outlet Test']);
        $table = CafeTable::create(['outlet_id' => $outlet->id, 'name' => 'Meja Request', 'capacity' => 2]);
        $session = TableSession::create(['cafe_table_id' => $table->id]);

        $response = $this->postJson('/order/session/' . $session->session_token . '/request', ['type' => 'call_cashier']);

        $response->assertCreated();
        $this->assertDatabaseHas('table_requests', ['table_session_id' => $session->id, 'type' => 'call_cashier', 'status' => 'pending']);
    }

    public function test_cashier_can_split_table_order_into_two_payments(): void
    {
        $outlet = Outlet::create(['name' => 'Split Outlet']);
        $cashier = User::create(['name' => 'Split Cashier', 'email' => 'split@test.com', 'username' => 'split-cashier', 'password' => Hash::make('password'), 'outlet_id' => $outlet->id]);
        $permission = Permission::firstOrCreate(['name' => 'pos-access']); $role = Role::firstOrCreate(['name' => 'Split Cashier']); $role->givePermissionTo($permission); $cashier->assignRole($role);
        $shift = CashierShift::create(['user_id' => $cashier->id, 'outlet_id' => $outlet->id, 'start_cash' => 0, 'status' => 'open']);
        $table = CafeTable::create(['outlet_id' => $outlet->id, 'name' => 'Meja Split', 'capacity' => 2]); $session = TableSession::create(['cafe_table_id' => $table->id]);
        $order = $session->orders()->create(['order_number' => 'SPLIT-001', 'status' => 'accepted', 'total' => 20000]);
        $response = $this->actingAs($cashier)->postJson('/table-orders/' . $order->id . '/split-checkout', ['payments' => [['amount' => 10000, 'payment_method' => 'cash'], ['amount' => 10000, 'payment_method' => 'qris']]]);
        $response->assertCreated();
        $this->assertSame(2, Sale::where('shift_id', $shift->id)->count());
        $this->assertDatabaseHas('customer_orders', ['id' => $order->id, 'status' => 'completed']);
    }

    public function test_kitchen_queue_can_filter_bar_and_kitchen_destinations(): void
    {
        $outlet = Outlet::create(['name' => 'KDS Outlet']);
        $staff = User::create(['name' => 'KDS Staff', 'email' => 'kds-filter@test.com', 'username' => 'kds-filter', 'password' => Hash::make('password'), 'outlet_id' => $outlet->id]);
        $permission = Permission::firstOrCreate(['name' => 'kitchen-access']); $role = Role::firstOrCreate(['name' => 'KDS Filter']); $role->givePermissionTo($permission); $staff->assignRole($role);
        $table = CafeTable::create(['outlet_id' => $outlet->id, 'name' => 'Meja KDS Filter', 'capacity' => 2]); $session = TableSession::create(['cafe_table_id' => $table->id]);
        $bar = $session->orders()->create(['order_number' => 'BAR-001', 'status' => 'accepted', 'total' => 10000, 'destination' => 'bar']);
        $kitchen = $session->orders()->create(['order_number' => 'KIT-001', 'status' => 'accepted', 'total' => 10000, 'destination' => 'kitchen']);
        $this->actingAs($staff)->get('/kitchen?destination=bar')->assertOk()->assertSee('BAR-001')->assertDontSee('KIT-001');
    }

    public function test_manager_can_set_table_cleaning_status(): void
    {
        $outlet = Outlet::create(['name' => 'Cleaning Outlet']);
        $manager = User::create(['name' => 'Cleaning Manager', 'email' => 'cleaning@test.com', 'username' => 'cleaning-manager', 'password' => Hash::make('password'), 'outlet_id' => $outlet->id]);
        $permission = Permission::firstOrCreate(['name' => 'table-manage']); $role = Role::firstOrCreate(['name' => 'Cleaning Manager']); $role->givePermissionTo($permission); $manager->assignRole($role);
        $table = CafeTable::create(['outlet_id' => $outlet->id, 'name' => 'Meja Cleaning', 'capacity' => 2]);
        $this->actingAs($manager)->patchJson('/tables/' . $table->id . '/occupancy', ['occupancy_status' => 'cleaning'])->assertOk();
        $this->assertDatabaseHas('cafe_tables', ['id' => $table->id, 'occupancy_status' => 'cleaning']);
    }

    public function test_customer_order_accepts_modifier_data_and_server_keeps_it(): void
    {
        $outlet = Outlet::create(['name' => 'Outlet Test']);
        $table = CafeTable::create(['outlet_id' => $outlet->id, 'name' => 'Meja Modifier', 'capacity' => 2]);
        $product = Product::create(['name' => 'Latte', 'category' => 'Coffee', 'price' => 18000, 'detail' => 'Modifier test']);

        $response = $this->postJson('/order/' . $table->qr_token, [
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
                'notes' => 'Less sugar',
                'modifiers' => ['size' => 'large', 'sugar' => 'less', 'ice' => 'none', 'toppings' => ['boba']],
            ]],
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('customer_order_items', ['notes' => 'Less sugar']);
        $this->assertDatabaseHas('order_item_modifiers', ['key' => 'size', 'value' => 'large']);
    }

    public function test_successful_doku_notification_creates_sale_once(): void
    {
        $outlet = Outlet::create(['name' => 'Outlet Test']);
        $user = User::create(['name' => 'Cashier Doku', 'email' => 'doku-sale@test.com', 'username' => 'doku-sale', 'password' => Hash::make('password'), 'outlet_id' => $outlet->id]);
        $shift = CashierShift::create(['user_id' => $user->id, 'outlet_id' => $outlet->id, 'start_cash' => 0, 'status' => 'open']);
        $table = CafeTable::create(['outlet_id' => $outlet->id, 'name' => 'Meja Doku', 'capacity' => 2]);
        $session = TableSession::create(['cafe_table_id' => $table->id]);
        $order = $session->orders()->create(['order_number' => 'DOKU-SALE-001', 'status' => 'accepted', 'total' => 25000]);
        $payment = DokuPayment::create(['customer_order_id' => $order->id, 'invoice_number' => 'DOKU-DOKU-SALE-001', 'request_id' => 'PAY-001', 'amount' => 25000]);
        $body = ['order' => ['invoice_number' => $payment->invoice_number], 'transaction' => ['status' => 'SUCCESS']];
        $service = new \App\Services\DokuPaymentService('MCH-TEST', 'secret-test');
        PaymentSetting::create(['key' => 'doku_client_id', 'value' => 'MCH-TEST']);
        PaymentSetting::create(['key' => 'doku_secret_key', 'value' => 'secret-test', 'is_secret' => true]);
        $headers = ['Request-Id' => 'WEB-001', 'Request-Timestamp' => '2020-10-21T03:38:28Z', 'Signature' => $service->signature('WEB-001', '2020-10-21T03:38:28Z', '/payments/doku/notify', $body)];

        $this->postJson('/payments/doku/notify', $body, $headers)->assertOk();
        $this->postJson('/payments/doku/notify', $body, $headers)->assertOk();
        $this->assertDatabaseHas('doku_payments', ['id' => $payment->id, 'status' => 'paid']);
        $this->assertSame(1, Sale::where('invoice_number', $payment->invoice_number)->count());
        $this->assertDatabaseHas('sales', ['shift_id' => $shift->id, 'total_price' => 25000, 'payment_method' => 'doku']);
        $this->assertDatabaseHas('customer_orders', ['id' => $order->id, 'status' => 'completed']);
    }

    public function test_cashier_sees_only_orders_from_own_outlet(): void
    {
        $outletA = Outlet::create(['name' => 'Outlet A']);
        $outletB = Outlet::create(['name' => 'Outlet B']);
        $user = User::create(['name' => 'Cashier A', 'email' => 'outlet-a@test.com', 'username' => 'outlet-a', 'password' => Hash::make('password'), 'outlet_id' => $outletA->id]);
        $permission = Permission::firstOrCreate(['name' => 'pos-access']);
        $role = Role::firstOrCreate(['name' => 'Cashier Outlet']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);
        foreach ([[$outletA, 'A-ORDER'], [$outletB, 'B-ORDER']] as [$outlet, $number]) {
            $table = CafeTable::create(['outlet_id' => $outlet->id, 'name' => 'Meja ' . $number, 'capacity' => 2]);
            $session = TableSession::create(['cafe_table_id' => $table->id]);
            $session->orders()->create(['order_number' => $number, 'status' => 'pending', 'total' => 10000]);
        }

        $response = $this->actingAs($user)->get('/table-orders');
        $response->assertOk()->assertSee('A-ORDER')->assertDontSee('B-ORDER');
    }
}
