<?php
namespace App\Http\Controllers;
use App\Models\CustomerComplaint; use App\Models\Voucher; use Illuminate\Http\JsonResponse; use Illuminate\Http\Request;
class CustomerFeatureController extends Controller {
 public function complaint(Request $r): JsonResponse {$d=$r->validate(['customer_id'=>['required','exists:customers,id'],'message'=>['required','string','max:2000']]);return response()->json(CustomerComplaint::create($d+['status'=>'open']),201);}
 public function voucher(Request $r): JsonResponse {$d=$r->validate(['code'=>['required','string']]);$v=Voucher::where('code',$d['code'])->where('active',true)->firstOrFail();abort_if($v->expires_at&&$v->expires_at->isPast(),422,'Voucher expired.');abort_if($v->usage_limit!==null&&$v->used_count>=$v->usage_limit,422,'Voucher limit reached.');return response()->json(['code'=>$v->code,'discount_amount'=>$v->discount_amount]);}
}
