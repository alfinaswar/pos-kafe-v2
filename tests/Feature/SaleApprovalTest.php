<?php

namespace Tests\Feature;

use App\Models\CashierShift;
use App\Models\Outlet;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SaleApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_void_requires_approved_request(): void
    {
        $outlet = Outlet::create(['name' => 'Outlet']);
        $cashier = User::create(['name' => 'Cashier', 'email' => 'void-cashier@test.com', 'username' => 'void-cashier', 'password' => Hash::make('password'), 'outlet_id' => $outlet->id]);
        $permission = Permission::firstOrCreate(['name' => 'pos-access']);
        $role = Role::firstOrCreate(['name' => 'Void Cashier']);
        $role->givePermissionTo($permission);
        $cashier->assignRole($role);
        $shift = CashierShift::create(['user_id' => $cashier->id, 'outlet_id' => $outlet->id, 'start_cash' => 0, 'status' => 'open']);
        $sale = Sale::create(['invoice_number' => 'INV-VOID-APPROVAL', 'user_id' => $cashier->id, 'shift_id' => $shift->id, 'total_price' => 10000, 'payment_amount' => 10000, 'change_amount' => 0, 'payment_method' => 'cash']);

        $response = $this->actingAs($cashier)->postJson('/sales/' . $sale->id . '/void', ['reason' => 'Salah input']);

        $response->assertStatus(202)->assertJsonPath('status', 'pending');
        $this->assertDatabaseHas('approval_requests', ['type' => 'void', 'reference_id' => $sale->id, 'status' => 'pending']);
        $this->assertDatabaseHas('sales', ['id' => $sale->id]);
    }

    public function test_approved_void_marks_sale_voided_without_deleting_it(): void
    {
        $outlet = Outlet::create(['name' => 'Outlet']);
        $manager = User::create(['name' => 'Manager', 'email' => 'void-manager@test.com', 'username' => 'void-manager', 'password' => Hash::make('password'), 'outlet_id' => $outlet->id]);
        $permission = Permission::firstOrCreate(['name' => 'approval-manage']);
        $role = Role::firstOrCreate(['name' => 'Void Manager']); $role->givePermissionTo($permission); $manager->assignRole($role);
        $shift = CashierShift::create(['user_id' => $manager->id, 'outlet_id' => $outlet->id, 'start_cash' => 0, 'status' => 'open']);
        $sale = Sale::create(['invoice_number' => 'INV-VOID-EXEC', 'user_id' => $manager->id, 'shift_id' => $shift->id, 'total_price' => 10000, 'payment_amount' => 10000, 'change_amount' => 0, 'payment_method' => 'cash']);
        $approval = \App\Models\ApprovalRequest::create(['outlet_id' => $outlet->id, 'requested_by' => $manager->id, 'type' => 'void', 'reference_type' => 'sale', 'reference_id' => $sale->id, 'reason' => 'Salah input']);

        $this->actingAs($manager)->patch(route('approvals.update', $approval), ['status' => 'approved'])->assertRedirect();

        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'status' => 'voided']);
        $this->assertDatabaseHas('approval_requests', ['id' => $approval->id, 'status' => 'approved']);
    }

    public function test_refund_requires_approval(): void
    {
        $outlet = Outlet::create(['name' => 'Refund Outlet']);
        $user = User::create(['name' => 'Refund Cashier', 'email' => 'refund@test.com', 'username' => 'refund-cashier', 'password' => Hash::make('password'), 'outlet_id' => $outlet->id]);
        $permission = Permission::firstOrCreate(['name' => 'pos-access']); $role = Role::firstOrCreate(['name' => 'Refund Cashier']); $role->givePermissionTo($permission); $user->assignRole($role);
        $shift = CashierShift::create(['user_id' => $user->id, 'outlet_id' => $outlet->id, 'start_cash' => 0, 'status' => 'open']);
        $sale = Sale::create(['invoice_number' => 'INV-REFUND-APPROVAL', 'user_id' => $user->id, 'shift_id' => $shift->id, 'total_price' => 10000, 'payment_amount' => 10000, 'change_amount' => 0, 'payment_method' => 'cash']);
        $response = $this->actingAs($user)->postJson('/sales/' . $sale->id . '/refund', ['reason' => 'Customer return']);
        $response->assertStatus(202);
        $this->assertDatabaseHas('approval_requests', ['type' => 'refund', 'reference_id' => $sale->id, 'status' => 'pending']);
    }

    public function test_discount_requires_approval(): void
    {
        $outlet = Outlet::create(['name' => 'Discount Outlet']);
        $user = User::create(['name' => 'Discount Cashier', 'email' => 'discount@test.com', 'username' => 'discount-cashier', 'password' => Hash::make('password'), 'outlet_id' => $outlet->id]);
        $permission = Permission::firstOrCreate(['name' => 'pos-access']); $role = Role::firstOrCreate(['name' => 'Discount Cashier']); $role->givePermissionTo($permission); $user->assignRole($role);
        $shift = CashierShift::create(['user_id' => $user->id, 'outlet_id' => $outlet->id, 'start_cash' => 0, 'status' => 'open']);
        $sale = Sale::create(['invoice_number' => 'INV-DISCOUNT-APPROVAL', 'user_id' => $user->id, 'shift_id' => $shift->id, 'total_price' => 10000, 'payment_amount' => 10000, 'change_amount' => 0, 'payment_method' => 'cash']);
        $this->actingAs($user)->postJson('/sales/' . $sale->id . '/discount', ['discount_amount' => 1000, 'reason' => 'Promo'])->assertStatus(202);
        $this->assertDatabaseHas('approval_requests', ['type' => 'discount', 'reference_id' => $sale->id, 'status' => 'pending']);
    }

    public function test_stock_adjustment_requires_approval(): void
    {
        $outlet = Outlet::create(['name' => 'Stock Outlet']);
        $user = User::create(['name' => 'Stock Staff', 'email' => 'stock@test.com', 'username' => 'stock-staff', 'password' => Hash::make('password'), 'outlet_id' => $outlet->id]);
        $permission = Permission::firstOrCreate(['name' => 'stock-opname']); $role = Role::firstOrCreate(['name' => 'Stock Staff']); $role->givePermissionTo($permission); $user->assignRole($role);
        $material = \App\Models\Material::create(['name' => 'Adjustment Material', 'stock' => 10, 'purchase_unit' => 'Pack', 'recipe_unit' => 'pcs', 'conversion_factor' => 1, 'reorder_point' => 1]);
        $this->actingAs($user)->postJson('/materials/adjustment-request', ['material_id' => $material->id, 'quantity' => -2, 'reason' => 'Rusak'])->assertStatus(202);
        $this->assertDatabaseHas('approval_requests', ['type' => 'stock_adjustment', 'reference_id' => $material->id, 'status' => 'pending']);
        $this->assertDatabaseHas('materials', ['id' => $material->id, 'stock' => 10]);
    }

    public function test_approved_stock_adjustment_changes_material_stock(): void
    {
        $outlet = Outlet::create(['name' => 'Stock Approval Outlet']);
        $manager = User::create(['name' => 'Stock Manager', 'email' => 'stock-manager@test.com', 'username' => 'stock-manager', 'password' => Hash::make('password'), 'outlet_id' => $outlet->id]);
        $permission = Permission::firstOrCreate(['name' => 'approval-manage']); $role = Role::firstOrCreate(['name' => 'Stock Approval Manager']); $role->givePermissionTo($permission); $manager->assignRole($role);
        $material = \App\Models\Material::create(['name' => 'Approved Material', 'stock' => 10, 'purchase_unit' => 'Pack', 'recipe_unit' => 'pcs', 'conversion_factor' => 1, 'reorder_point' => 1]);
        $approval = \App\Models\ApprovalRequest::create(['outlet_id' => $outlet->id, 'requested_by' => $manager->id, 'type' => 'stock_adjustment', 'reference_type' => 'material', 'reference_id' => $material->id, 'reason' => 'Rusak | Quantity: -2']);

        $this->actingAs($manager)->patch(route('approvals.update', $approval), ['status' => 'approved'])->assertRedirect();

        $this->assertDatabaseHas('materials', ['id' => $material->id, 'stock' => 8]);
    }

    public function test_approved_refund_updates_cash_and_restores_recipe_stock(): void
    {
        $outlet = Outlet::create(['name' => 'Refund Execute Outlet']);
        $manager = User::create(['name' => 'Refund Manager', 'email' => 'refund-manager@test.com', 'username' => 'refund-manager', 'password' => Hash::make('password'), 'outlet_id' => $outlet->id]);
        $permission = Permission::firstOrCreate(['name' => 'approval-manage']); $role = Role::firstOrCreate(['name' => 'Refund Execute Manager']); $role->givePermissionTo($permission); $manager->assignRole($role);
        $shift = CashierShift::create(['user_id' => $manager->id, 'outlet_id' => $outlet->id, 'start_cash' => 100000, 'total_sales_cash' => 10000, 'status' => 'open']);
        $material = \App\Models\Material::create(['name' => 'Refund Coffee', 'stock' => 8, 'purchase_unit' => 'Pack', 'recipe_unit' => 'pcs', 'conversion_factor' => 1, 'reorder_point' => 1]);
        $batch = \App\Models\MaterialBatch::create(['material_id' => $material->id, 'initial_qty' => 8, 'remaining_qty' => 8, 'purchase_price' => 1]);
        $product = \App\Models\Product::create(['name' => 'Refund Menu', 'category' => 'Test', 'price' => 10000, 'detail' => 'Refund']);
        $product->recipeItems()->create(['material_id' => $material->id, 'quantity' => 2]);
        $sale = Sale::create(['invoice_number' => 'INV-REFUND-EXEC', 'user_id' => $manager->id, 'shift_id' => $shift->id, 'total_price' => 10000, 'payment_amount' => 10000, 'change_amount' => 0, 'payment_method' => 'cash']);
        \App\Models\SaleItem::create(['sale_id' => $sale->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 10000, 'subtotal' => 10000]);
        $approval = \App\Models\ApprovalRequest::create(['outlet_id' => $outlet->id, 'requested_by' => $manager->id, 'type' => 'refund', 'reference_type' => 'sale', 'reference_id' => $sale->id, 'reason' => 'Return']);

        $this->actingAs($manager)->patch(route('approvals.update', $approval), ['status' => 'approved'])->assertRedirect();

        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'status' => 'refunded']);
        $this->assertDatabaseHas('cashier_shifts', ['id' => $shift->id, 'total_sales_cash' => 0]);
        $this->assertDatabaseHas('materials', ['id' => $material->id, 'stock' => 10]);
        $this->assertDatabaseHas('material_batches', ['id' => $batch->id, 'remaining_qty' => 10]);
    }

    public function test_partial_refund_approval_marks_sale_partially_refunded(): void
    {
        $outlet = Outlet::create(['name' => 'Partial Outlet']); $manager = User::create(['name' => 'Partial Manager', 'email' => 'partial-manager@test.com', 'username' => 'partial-manager', 'password' => Hash::make('password'), 'outlet_id' => $outlet->id]);
        $permission = Permission::firstOrCreate(['name' => 'approval-manage']); $role = Role::firstOrCreate(['name' => 'Partial Manager']); $role->givePermissionTo($permission); $manager->assignRole($role);
        $shift = CashierShift::create(['user_id' => $manager->id, 'outlet_id' => $outlet->id, 'start_cash' => 0, 'status' => 'open']);
        $sale = Sale::create(['invoice_number' => 'INV-PARTIAL', 'user_id' => $manager->id, 'shift_id' => $shift->id, 'total_price' => 10000, 'payment_amount' => 10000, 'change_amount' => 0, 'payment_method' => 'cash']);
        $approval = \App\Models\ApprovalRequest::create(['outlet_id' => $outlet->id, 'requested_by' => $manager->id, 'type' => 'refund', 'reference_type' => 'sale', 'reference_id' => $sale->id, 'reason' => 'Return | Partial: 3000']);
        $this->actingAs($manager)->patch(route('approvals.update', $approval), ['status' => 'approved'])->assertRedirect();
        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'status' => 'partially_refunded']);
    }

    public function test_approved_discount_updates_sale_total_and_shift(): void
    {
        $outlet = Outlet::create(['name' => 'Discount Execute Outlet']);
        $manager = User::create(['name' => 'Discount Manager', 'email' => 'discount-manager@test.com', 'username' => 'discount-manager', 'password' => Hash::make('password'), 'outlet_id' => $outlet->id]);
        $permission = Permission::firstOrCreate(['name' => 'approval-manage']); $role = Role::firstOrCreate(['name' => 'Discount Execute Manager']); $role->givePermissionTo($permission); $manager->assignRole($role);
        $shift = CashierShift::create(['user_id' => $manager->id, 'outlet_id' => $outlet->id, 'start_cash' => 0, 'total_sales_cash' => 10000, 'status' => 'open']);
        $sale = Sale::create(['invoice_number' => 'INV-DISCOUNT-EXEC', 'user_id' => $manager->id, 'shift_id' => $shift->id, 'total_price' => 10000, 'payment_amount' => 10000, 'change_amount' => 0, 'payment_method' => 'cash']);
        $approval = \App\Models\ApprovalRequest::create(['outlet_id' => $outlet->id, 'requested_by' => $manager->id, 'type' => 'discount', 'reference_type' => 'sale', 'reference_id' => $sale->id, 'reason' => 'Promo | Nominal: 2000']);

        $this->actingAs($manager)->patch(route('approvals.update', $approval), ['status' => 'approved'])->assertRedirect();

        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'total_price' => 8000, 'payment_amount' => 10000, 'change_amount' => 2000]);
        $this->assertDatabaseHas('cashier_shifts', ['id' => $shift->id, 'total_sales_cash' => 8000]);
    }
}
