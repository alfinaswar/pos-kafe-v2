<?php
namespace Tests\Feature;
use App\Models\Customer; use App\Models\Voucher; use Illuminate\Foundation\Testing\RefreshDatabase; use Tests\TestCase;
class CustomerFeaturesTest extends TestCase { use RefreshDatabase; public function test_customer_can_validate_voucher_and_submit_complaint(): void { $customer=Customer::create(['name'=>'Test Customer']); Voucher::create(['code'=>'TEST10','discount_amount'=>10000,'active'=>true]); $this->postJson('/customer/vouchers/validate',['code'=>'TEST10'])->assertOk()->assertJsonPath('discount_amount','10000.00'); $this->postJson('/customer/complaints',['customer_id'=>$customer->id,'message'=>'Test complaint'])->assertCreated()->assertJsonPath('status','open'); } }
