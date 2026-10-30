<?php

namespace Tests\Feature;

use App\Models\ApprovalRequest;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApprovalAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_approval_decision_is_audited(): void
    {
        $outlet = Outlet::create(['name' => 'Audit Outlet']);
        $manager = User::create(['name' => 'Manager', 'email' => 'audit@test.com', 'username' => 'audit-manager', 'password' => Hash::make('password'), 'outlet_id' => $outlet->id]);
        $permission = Permission::firstOrCreate(['name' => 'approval-manage']);
        $role = Role::firstOrCreate(['name' => 'Audit Manager']);
        $role->givePermissionTo($permission);
        $manager->assignRole($role);
        $approval = ApprovalRequest::create(['outlet_id' => $outlet->id, 'requested_by' => $manager->id, 'type' => 'void', 'reference_type' => 'sale', 'reference_id' => 1, 'reason' => 'Test', 'status' => 'pending']);

        $this->actingAs($manager)->patch(route('approvals.update', $approval), ['status' => 'approved']);

        $this->assertDatabaseHas('activity_logs', ['user_id' => $manager->id, 'action' => 'APPROVAL_DECISION']);
    }
}
