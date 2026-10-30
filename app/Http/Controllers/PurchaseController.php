<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Supplier;
use App\Models\Material;
use App\Models\MaterialBatch;
use App\Models\InventoryLog;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class PurchaseController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:purchase-list|purchase-create|purchase-approve|purchase-receive', ['only' => ['index', 'show']]);
        $this->middleware('permission:purchase-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:purchase-approve', ['only' => ['approve', 'reject']]);
        $this->middleware('permission:purchase-receive', ['only' => ['receive']]);
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $purchases = Purchase::visibleToCurrentOutlet()->with(['supplier', 'user'])->latest();
            return DataTables::of($purchases)
                ->addIndexColumn()
                ->addColumn('total_cost_formatted', function ($row) {
                    return 'Rp ' . number_format($row->total_cost, 0, ',', '.');
                })
                ->addColumn('status_badge', function ($row) {
                    $badges = [
                        'pending' => 'badge-warning',
                        'approved' => 'badge-primary',
                        'completed' => 'badge-success',
                        'rejected' => 'badge-danger'
                    ];
                    $badgeClass = $badges[$row->status] ?? 'badge-secondary';
                    return '<span class="badge ' . $badgeClass . '">' . strtoupper($row->status) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $showBtn = '<a href="'.route('purchases.show', $row->id).'" class="btn btn-info btn-sm mr-1" title="Detail PO"><i class="fas fa-eye"></i></a>';
                    return '<div class="text-center">' . $showBtn . '</div>';
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }

        return view('purchases.index');
    }

    public function create(): View
    {
        $suppliers = Supplier::orderBy('name')->get();
        $materials = Material::orderBy('name')->get();
        return view('purchases.create', compact('suppliers', 'materials'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'materials' => 'required|array|min:1',
            'materials.*.id' => 'required|exists:materials,id',
            'materials.*.qty' => 'required|numeric|min:0.01',
            'materials.*.price' => 'required|numeric|min:0',
            'materials.*.expired_date' => 'nullable|date',
        ]);

        try {
            $purchase = DB::transaction(function () use ($request) {
                $invoiceNumber = 'PO-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
                $totalCost = 0;

                foreach ($request->materials as $item) {
                    $totalCost += ($item['qty'] * $item['price']);
                }

                $purchase = Purchase::create([
                    'supplier_id' => $request->supplier_id,
                    'user_id' => auth()->id(),
                    'outlet_id' => auth()->user()->outlet_id,
                    'invoice_number' => $invoiceNumber,
                    'total_cost' => $totalCost,
                    'status' => 'pending',
                    'notes' => $request->notes,
                ]);

                foreach ($request->materials as $item) {
                    PurchaseDetail::create([
                        'purchase_id' => $purchase->id,
                        'material_id' => $item['id'],
                        'qty_purchase' => $item['qty'],
                        'unit_price' => $item['price'],
                        'subtotal' => $item['qty'] * $item['price'],
                        'expired_date' => $item['expired_date'] ?? null,
                    ]);
                }

                ActivityLog::record('CREATE_PO', "Membuat draft Purchase Order {$invoiceNumber} senilai Rp " . number_format($totalCost, 0, ',', '.'));

                return $purchase;
            });

            return redirect()->route('purchases.index')
                ->with('success', 'Purchase Order (PO) draft created successfully.');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal membuat Purchase Order: ' . $e->getMessage());
        }
    }

    public function show(Purchase $purchase): View
    {
        // Pembatasan outlet: kasir/manager hanya boleh membuka PO outlet-nya.
        abort_unless(\App\Support\OutletScope::apply(Purchase::whereKey($purchase->id))->exists(), 403, 'PO bukan milik outlet Anda.');

        $purchase->load(['supplier', 'user', 'details.material']);
        return view('purchases.show', compact('purchase'));
    }

    // Owner Approval
    public function approve(Purchase $purchase): RedirectResponse
    {
        try {
            if ($purchase->status !== 'pending') {
                return back()->with('error', 'Hanya PO dengan status pending yang dapat disetujui.');
            }

            $purchase->update(['status' => 'approved']);

            ActivityLog::record('APPROVE_PO', "Menyetujui Purchase Order {$purchase->invoice_number}");

            return redirect()->route('purchases.show', $purchase->id)
                ->with('success', 'Purchase Order berhasil disetujui.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menyetujui PO: ' . $e->getMessage());
        }
    }

    public function reject(Purchase $purchase): RedirectResponse
    {
        try {
            if ($purchase->status !== 'pending') {
                return back()->with('error', 'Hanya PO dengan status pending yang dapat ditolak.');
            }

            $purchase->update(['status' => 'rejected']);

            ActivityLog::record('REJECT_PO', "Menolak Purchase Order {$purchase->invoice_number}");

            return redirect()->route('purchases.show', $purchase->id)
                ->with('success', 'Purchase Order berhasil ditolak.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menolak PO: ' . $e->getMessage());
        }
    }

    // Goods Receipt (Penerimaan Barang & Konversi Satuan Otomatis ke Batch FIFO)
    public function receive(Purchase $purchase): RedirectResponse
    {
        try {
            if ($purchase->status !== 'approved') {
                return back()->with('error', 'Hanya PO yang sudah disetujui yang dapat diterima ke stok inventaris.');
            }

            DB::transaction(function () use ($purchase) {
                foreach ($purchase->details as $detail) {
                    $material = Material::lockForUpdate()->find($detail->material_id);
                    if (!$material) {
                        throw new \Exception("Bahan baku ID #{$detail->material_id} tidak ditemukan.");
                    }

                    // Konversi unit beli ke unit resep (misal: 1 karton x 12.000 ml)
                    $addedRecipeQty = $detail->qty_purchase * $material->conversion_factor;

                    // 1. Buat batch FIFO baru
                    $batchNumber = 'BATCH-' . $purchase->invoice_number . '-' . $detail->id;
                    $batch = MaterialBatch::create([
                        'material_id' => $material->id,
                        'purchase_id' => $purchase->id,
                        'batch_number' => $batchNumber,
                        'initial_qty' => $addedRecipeQty,
                        'remaining_qty' => $addedRecipeQty,
                        'purchase_price' => $detail->unit_price,
                        'expired_date' => $detail->expired_date,
                    ]);

                    // 2. Tambah stok master bahan
                    $material->increment('stock', $addedRecipeQty);

                    // 3. Catat log mutasi inventaris
                    InventoryLog::create([
                        'material_id' => $material->id,
                        'batch_id' => $batch->id,
                        'type' => 'in',
                        'quantity' => $addedRecipeQty,
                        'stock_after' => $material->fresh()->stock,
                        'reference' => 'Goods Receipt: ' . $purchase->invoice_number . ' (Batch #' . $batchNumber . ')',
                    ]);
                }

                $purchase->update(['status' => 'completed']);

                ActivityLog::record('RECEIVE_GOODS', "Penerimaan barang PO {$purchase->invoice_number} ke stok gudang & batch FIFO.");
            });

            return redirect()->route('purchases.show', $purchase->id)
                ->with('success', 'Barang berhasil diterima! Stok diperbarui dan batch FIFO telah dibuat.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memproses penerimaan barang: ' . $e->getMessage());
        }
    }
}
