<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleActionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:pos-access');
    }

    public function void(Request $request, Sale $sale): JsonResponse
    {
        abort_unless($sale->shift->outlet_id === $request->user()->outlet_id, 403);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $approval = ApprovalRequest::firstOrCreate([
            'outlet_id' => $sale->shift->outlet_id,
            'requested_by' => $request->user()->id,
            'type' => 'void',
            'reference_type' => 'sale',
            'reference_id' => $sale->id,
            'status' => 'pending',
        ], ['reason' => $data['reason']]);

        return response()->json(['success' => true, 'status' => $approval->status, 'approval_id' => $approval->id], 202);
    }

    public function refund(Request $request, Sale $sale): JsonResponse
    {
        abort_unless($sale->shift->outlet_id === $request->user()->outlet_id, 403);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $approval = ApprovalRequest::firstOrCreate([
            'outlet_id' => $sale->shift->outlet_id, 'requested_by' => $request->user()->id,
            'type' => 'refund', 'reference_type' => 'sale', 'reference_id' => $sale->id, 'status' => 'pending',
        ], ['reason' => $data['reason']]);
        return response()->json(['success' => true, 'status' => $approval->status, 'approval_id' => $approval->id], 202);
    }

    public function partialRefund(Request $request, Sale $sale): JsonResponse
    {
        abort_unless($sale->shift->outlet_id === $request->user()->outlet_id, 403);
        $data = $request->validate(['amount' => ['required', 'numeric', 'gt:0', 'lt:' . $sale->total_price], 'reason' => ['required', 'string', 'max:1000']]);
        $alreadyRefunded = \App\Models\ApprovalRequest::where('reference_type', 'sale')->where('reference_id', $sale->id)->where('type', 'refund')->where('status', 'approved')->get()->sum(function ($approval) { preg_match('/Partial:\s*([0-9.]+)/', $approval->reason, $match); return (float) ($match[1] ?? 0); });
        abort_unless($alreadyRefunded + $data['amount'] <= $sale->total_price, 422, 'Total refund melebihi nilai transaksi.');
        $approval = ApprovalRequest::create(['outlet_id' => $sale->shift->outlet_id, 'requested_by' => $request->user()->id, 'type' => 'refund', 'reference_type' => 'sale', 'reference_id' => $sale->id, 'reason' => $data['reason'] . ' | Partial: ' . $data['amount'], 'status' => 'pending']);
        return response()->json(['success' => true, 'status' => $approval->status, 'approval_id' => $approval->id], 202);
    }

    public function discount(Request $request, Sale $sale): JsonResponse
    {
        abort_unless($sale->shift->outlet_id === $request->user()->outlet_id, 403);
        $data = $request->validate(['discount_amount' => ['required', 'numeric', 'gt:0', 'max:' . $sale->total_price], 'reason' => ['required', 'string', 'max:1000']]);
        $approval = ApprovalRequest::firstOrCreate([
            'outlet_id' => $sale->shift->outlet_id, 'requested_by' => $request->user()->id,
            'type' => 'discount', 'reference_type' => 'sale', 'reference_id' => $sale->id, 'status' => 'pending',
        ], ['reason' => $data['reason'] . ' | Nominal: ' . $data['discount_amount']]);
        return response()->json(['success' => true, 'status' => $approval->status, 'approval_id' => $approval->id], 202);
    }
}
