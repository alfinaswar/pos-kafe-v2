<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\MaterialBatch;
use App\Models\InventoryLog;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class MaterialBatchController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:batch-list', ['only' => ['index']]);
        $this->middleware('permission:stock-opname', ['only' => ['opname', 'storeOpname']]);
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $batches = MaterialBatch::with(['material', 'purchase'])->latest('id');

            if ($request->filled('material_id')) {
                $batches->where('material_id', $request->material_id);
            }
            if ($request->filled('status')) {
                if ($request->status === 'active') {
                    $batches->where('remaining_qty', '>', 0);
                } elseif ($request->status === 'depleted') {
                    $batches->where('remaining_qty', '<=', 0);
                } elseif ($request->status === 'near_expiry') {
                    $batches->where('remaining_qty', '>', 0)
                            ->whereNotNull('expired_date')
                            ->where('expired_date', '>=', now())
                            ->where('expired_date', '<=', now()->addDays(7));
                } elseif ($request->status === 'expired') {
                    $batches->where('remaining_qty', '>', 0)
                            ->whereNotNull('expired_date')
                            ->where('expired_date', '<', now());
                }
            }

            return DataTables::of($batches)
                ->addIndexColumn()
                ->addColumn('batch_code', fn($r) => $r->batch_number ?? ('BATCH-' . str_pad($r->id, 5, '0', STR_PAD_LEFT)))
                ->addColumn('material_name', fn($r) => $r->material->name ?? '-')
                ->addColumn('unit', fn($r) => $r->material->recipe_unit ?? '-')
                ->addColumn('initial_qty_fmt', fn($r) => number_format($r->initial_qty, 0, ',', '.') . ' ' . $r->material->recipe_unit)
                ->addColumn('remaining_qty_fmt', fn($r) => number_format($r->remaining_qty, 0, ',', '.') . ' ' . $r->material->recipe_unit)
                ->addColumn('purchase_price_fmt', fn($r) => 'Rp ' . number_format($r->purchase_price, 0, ',', '.'))
                ->addColumn('expired_date_fmt', function ($r) {
                    if (!$r->expired_date) return '<span class="text-muted">-</span>';
                    $exp = Carbon::parse($r->expired_date);
                    if ($exp->isPast() && $r->remaining_qty > 0) {
                        return '<span class="badge badge-danger"><i class="fas fa-skull-crossbones mr-1"></i> Kadaluarsa (' . $exp->format('d/m/Y') . ')</span>';
                    } elseif ($exp->diffInDays(now()) <= 7 && $r->remaining_qty > 0) {
                        return '<span class="badge badge-warning"><i class="fas fa-exclamation-triangle mr-1"></i> Segera Habis (' . $exp->format('d/m/Y') . ')</span>';
                    }
                    return '<span class="badge badge-light border">' . $exp->format('d/m/Y') . '</span>';
                })
                ->addColumn('status_badge', function ($r) {
                    if ($r->remaining_qty <= 0) {
                        return '<span class="badge badge-secondary">Habis</span>';
                    }
                    if ($r->expired_date && Carbon::parse($r->expired_date)->isPast()) {
                        return '<span class="badge badge-danger">Expired</span>';
                    }
                    return '<span class="badge badge-success">Aktif (Tersedia)</span>';
                })
                ->rawColumns(['expired_date_fmt', 'status_badge'])
                ->make(true);
        }

        $materials = Material::orderBy('name')->get();
        return view('batches.index', compact('materials'));
    }

    public function opname(): View
    {
        $materials = Material::with(['batches' => function($q) {
            $q->where('remaining_qty', '>', 0)->orderBy('created_at', 'asc');
        }])->orderBy('name')->get();

        return view('batches.opname', compact('materials'));
    }

    public function storeOpname(Request $request): RedirectResponse
    {
        $request->validate([
            'material_id' => 'required|exists:materials,id',
            'batch_id' => 'nullable|exists:material_batches,id',
            'type' => 'required|in:waste,opname_loss,opname_gain',
            'quantity' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $material = Material::lockForUpdate()->findOrFail($request->material_id);
                $qty = (float) $request->quantity;

                if ($request->filled('batch_id')) {
                    $batch = MaterialBatch::lockForUpdate()->findOrFail($request->batch_id);

                    if (in_array($request->type, ['waste', 'opname_loss'])) {
                        if ($batch->remaining_qty < $qty) {
                            throw new \Exception("Jumlah penyesuaian melampaui sisa stok batch ({$batch->remaining_qty} {$material->recipe_unit})!");
                        }
                        $batch->decrement('remaining_qty', $qty);
                        $material->decrement('stock', $qty);

                        InventoryLog::create([
                            'material_id' => $material->id,
                            'batch_id' => $batch->id,
                            'type' => 'out',
                            'quantity' => $qty,
                            'stock_after' => $material->fresh()->stock,
                            'reference' => strtoupper($request->type) . ': ' . $request->reason . ' (Batch #' . ($batch->batch_number ?? $batch->id) . ')',
                        ]);
                    } else {
                        // opname_gain (penambahan fisik)
                        $batch->increment('remaining_qty', $qty);
                        $material->increment('stock', $qty);

                        InventoryLog::create([
                            'material_id' => $material->id,
                            'batch_id' => $batch->id,
                            'type' => 'in',
                            'quantity' => $qty,
                            'stock_after' => $material->fresh()->stock,
                            'reference' => 'OPNAME GAIN: ' . $request->reason . ' (Batch #' . ($batch->batch_number ?? $batch->id) . ')',
                        ]);
                    }
                } else {
                    // Penyesuaian umum tanpa batch spesifik: potong / tambah dari batch tertua
                    if (in_array($request->type, ['waste', 'opname_loss'])) {
                        if ($material->stock < $qty) {
                            throw new \Exception("Jumlah penyesuaian melampaui total stok bahan ({$material->stock} {$material->recipe_unit})!");
                        }

                        $needed = $qty;
                        $batches = MaterialBatch::where('material_id', $material->id)
                            ->where('remaining_qty', '>', 0)
                            ->orderBy('created_at', 'asc')
                            ->lockForUpdate()
                            ->get();

                        foreach ($batches as $b) {
                            if ($needed <= 0) break;
                            $deduct = min($b->remaining_qty, $needed);
                            $b->decrement('remaining_qty', $deduct);
                            $needed -= $deduct;
                        }

                        $material->decrement('stock', $qty);

                        InventoryLog::create([
                            'material_id' => $material->id,
                            'type' => 'out',
                            'quantity' => $qty,
                            'stock_after' => $material->fresh()->stock,
                            'reference' => strtoupper($request->type) . ': ' . $request->reason,
                        ]);
                    } else {
                        // Buat batch baru untuk selisih lebih
                        $newBatch = MaterialBatch::create([
                            'material_id' => $material->id,
                            'batch_number' => 'OPNAME-' . date('YmdHis'),
                            'initial_qty' => $qty,
                            'remaining_qty' => $qty,
                            'purchase_price' => 0,
                            'expired_date' => Carbon::now()->addMonths(3),
                        ]);

                        $material->increment('stock', $qty);

                        InventoryLog::create([
                            'material_id' => $material->id,
                            'batch_id' => $newBatch->id,
                            'type' => 'in',
                            'quantity' => $qty,
                            'stock_after' => $material->fresh()->stock,
                            'reference' => 'OPNAME GAIN: ' . $request->reason,
                        ]);
                    }
                }

                ActivityLog::record(
                    'STOCK_OPNAME',
                    "Audit Stok / Waste pada bahan {$material->name} sebanyak {$qty} {$material->recipe_unit}. Tipe: {$request->type}. Alasan: {$request->reason}"
                );
            });

            return redirect()->route('batches.index')->with('success', 'Penyesuaian stok / waste log berhasil diproses.');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }
}
