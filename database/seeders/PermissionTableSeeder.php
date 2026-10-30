<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'role-list', 'role-create', 'role-edit', 'role-delete',
            'table-manage', 'kitchen-access', 'payment-settings', 'approval-manage', 'customer-manage',
            'pos-access', 'shift-manage', 'petty-cash', 'stock-opname', 'report-access', 'dashboard-access',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
