<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Permissions List
        $permissions = [
            // User & Role
            'user-list', 'user-create', 'user-edit', 'user-delete',
            'role-list', 'role-create', 'role-edit', 'role-delete',
            // Outlets
            'outlet-list', 'outlet-create', 'outlet-edit', 'outlet-delete',
            // Supplier
            'supplier-list', 'supplier-create', 'supplier-edit', 'supplier-delete',
            // Material & Batches
            'material-list', 'material-create', 'material-edit', 'material-delete',
            'batch-list', 'stock-opname',
            // Product & BOM (Recipe)
            'product-list', 'product-create', 'product-edit', 'product-delete',
            'recipe-manage',
            // Purchasing
            'purchase-list', 'purchase-create', 'purchase-approve', 'purchase-receive',
            // POS & Shift Management
            'pos-access', 'shift-manage', 'petty-cash',
            // Reports, Activity Log & Dashboard
            'report-access', 'dashboard-access', 'activity-log-list'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // 2. Roles based on PRD: Owner, Manager, Cashier
        $ownerRole = Role::firstOrCreate(['name' => 'Owner']);
        $managerRole = Role::firstOrCreate(['name' => 'Manager']);
        $cashierRole = Role::firstOrCreate(['name' => 'Cashier']);

        // Owner: Full System Access (Termasuk Approve PO)
        $ownerRole->syncPermissions(Permission::all());

        // Manager: Master data, PO create/receive, Stock opname, Batches, Shifts, Reports, Activity logs
        $managerRole->syncPermissions([
            'dashboard-access',
            'outlet-list',
            'supplier-list', 'supplier-create', 'supplier-edit',
            'material-list', 'material-create', 'material-edit',
            'batch-list', 'stock-opname',
            'product-list', 'product-create', 'product-edit', 'recipe-manage',
            'purchase-list', 'purchase-create', 'purchase-receive',
            'shift-manage',
            'report-access', 'activity-log-list'
        ]);

        // Cashier: POS Access, Shift open/close, Petty cash
        $cashierRole->syncPermissions([
            'pos-access',
            'shift-manage',
            'petty-cash'
        ]);

        // Assign Owner role to admin@gmail.com if exists
        $admin = User::where('email', 'admin@gmail.com')->first();
        if ($admin) {
            $admin->assignRole('Owner');
        }
    }
}
