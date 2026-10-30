<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:approval-manage');
    }

    public function index(): View
    {
        $approvals = ApprovalRequest::where('outlet_id', auth()->user()->outlet_id)->latest()->paginate(30);
        return view('approvals.index', compact('approvals'));
    }

    public function update(Request $request, ApprovalRequest $approval): RedirectResponse
    {
        abort_unless($approval->outlet_id === $request->user()->outlet_id, 403);
        $data = $request->validate(['status' => ['required', 'in:approved,rejected'], 'decision_note' => ['nullable', 'string', 'max:1000']]);
        $approval->update(['status' => $data['status'], 'decision_note' => $data['decision_note'] ?? null, 'approved_by' => $request->user()->id, 'decided_at' => now()]);
        if ($data['status'] === 'approved' && $approval->type === 'void' && $approval->reference_type === 'sale') {
            \App\Models\Sale::whereKey($approval->reference_id)->update(['status' => 'voided']);
        }
        if ($data['status'] === 'approved' && $approval->type === 'refund' && $approval->reference_type === 'sale') {
            $sale = \App\Models\Sale::with('items.product.recipeItems')->lockForUpdate()->findOrFail($approval->reference_id);
            preg_match('/Partial:\s*([0-9.]+)/', $approval->reason, $partialMatch);
            $refundAmount = (float) ($partialMatch[1] ?? $sale->total_price);
            if ($sale->status !== 'refunded' && $refundAmount > 0) {
                $sale->update(['status' => $partialMatch ? 'partially_refunded' : 'refunded']);
                $shift = $sale->shift()->lockForUpdate()->first();
                if ($shift && $sale->payment_method === 'cash') $shift->decrement('total_sales_cash', $refundAmount);
                elseif ($shift) $shift->decrement('total_sales_non_cash', $refundAmount);
                $ratio = min(1, $refundAmount / max(1, (float) $sale->total_price));
                foreach ($sale->items as $item) foreach ($item->product->recipeItems as $recipe) {
                    $quantity = $recipe->quantity * $item->quantity * $ratio;
                    $batch = \App\Models\MaterialBatch::where('material_id', $recipe->material_id)->orderByDesc('created_at')->lockForUpdate()->first();
                    if ($batch) $batch->increment('remaining_qty', $quantity);
                    \App\Models\Material::whereKey($recipe->material_id)->increment('stock', $quantity);
                    \App\Models\InventoryLog::create(['material_id' => $recipe->material_id, 'batch_id' => $batch?->id, 'type' => 'in', 'quantity' => $quantity, 'stock_after' => \App\Models\Material::find($recipe->material_id)->stock, 'reference' => 'Refund #' . $sale->invoice_number]);
                }
            }
        }
        if ($data['status'] === 'approved' && $approval->type === 'stock_adjustment' && $approval->reference_type === 'material') {
            preg_match('/Quantity:\s*(-?[0-9.]+)/', $approval->reason, $match);
            $quantity = (float) ($match[1] ?? 0);
            \App\Models\Material::whereKey($approval->reference_id)->lockForUpdate()->firstOrFail()->increment('stock', $quantity);
        }
        if ($data['status'] === 'approved' && $approval->type === 'discount' && $approval->reference_type === 'sale') {
            preg_match('/Nominal:\s*([0-9.]+)/', $approval->reason, $match);
            $discount = (float) ($match[1] ?? 0);
            $sale = \App\Models\Sale::lockForUpdate()->findOrFail($approval->reference_id);
            $newTotal = max(0, (float) $sale->total_price - $discount);
            $sale->update(['total_price' => $newTotal, 'change_amount' => max(0, (float) $sale->payment_amount - $newTotal)]);
            if ($sale->shift) $sale->shift->decrement($sale->payment_method === 'cash' ? 'total_sales_cash' : 'total_sales_non_cash', $discount);
        }
        \App\Models\ActivityLog::record('APPROVAL_DECISION', "Approval #{$approval->id} {$data['status']} oleh {$request->user()->name}");
        return redirect()->route('approvals.index')->with('success', 'Approval diperbarui.');
    }
}
