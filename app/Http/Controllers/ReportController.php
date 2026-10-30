<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Purchase;
use App\Models\InventoryLog;
use App\Models\Material;
use App\Models\CashierShift;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\Facades\DataTables;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:report-access');
    }

    public function sales(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $sales = Sale::visibleToCurrentOutlet()->with(['user', 'shift.outlet'])->latest();

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $sales->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
            }
            if ($request->filled('payment_method')) {
                $sales->where('payment_method', $request->payment_method);
            }

            return DataTables::of($sales)
                ->addIndexColumn()
                ->addColumn('cashier', fn($r) => $r->user->name ?? '-')
                ->addColumn('outlet', fn($r) => $r->shift->outlet->name ?? '-')
                ->addColumn('method', fn($r) => '<span class="badge badge-light border">' . strtoupper($r->payment_method) . '</span>')
                ->addColumn('total_formatted', fn($r) => 'Rp ' . number_format($r->total_price, 0, ',', '.'))
                ->addColumn('date', fn($r) => $r->created_at->format('d/m/Y H:i'))
                ->rawColumns(['method'])
                ->make(true);
        }

        return view('reports.sales');
    }

    public function inventory(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $logs = InventoryLog::with(['material', 'batch'])->latest();

            if ($request->filled('material_id')) {
                $logs->where('material_id', $request->material_id);
            }
            if ($request->filled('type')) {
                $logs->where('type', $request->type);
            }

            return DataTables::of($logs)
                ->addIndexColumn()
                ->addColumn('material_name', fn($r) => $r->material->name ?? '-')
                ->addColumn('batch_info', fn($r) => $r->batch ? ($r->batch->batch_number ?? ('#' . $r->batch->id)) : '-')
                ->addColumn('type_badge', function ($r) {
                    $color = $r->type === 'in' ? 'badge-success' : 'badge-danger';
                    return '<span class="badge ' . $color . '">' . strtoupper($r->type) . '</span>';
                })
                ->addColumn('quantity_display', function ($r) {
                    $sign = $r->type === 'in' ? '+' : '-';
                    return $sign . ' ' . number_format($r->quantity, 2) . ' ' . ($r->material->recipe_unit ?? '');
                })
                ->addColumn('stock_after_display', fn($r) => number_format($r->stock_after, 2) . ' ' . ($r->material->recipe_unit ?? ''))
                ->addColumn('date', fn($r) => $r->created_at->format('d/m/Y H:i'))
                ->rawColumns(['type_badge'])
                ->make(true);
        }

        $materials = Material::orderBy('name')->get();
        return view('reports.inventory', compact('materials'));
    }

    public function shifts(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $shifts = CashierShift::with(['user', 'outlet'])->latest();

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $shifts->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
            }
            if ($request->filled('outlet_id')) {
                $shifts->where('outlet_id', $request->outlet_id);
            }

            return DataTables::of($shifts)
                ->addIndexColumn()
                ->addColumn('cashier', fn($r) => $r->user->name ?? '-')
                ->addColumn('outlet', fn($r) => $r->outlet->name ?? '-')
                ->addColumn('start_cash_fmt', fn($r) => 'Rp ' . number_format($r->start_cash, 0, ',', '.'))
                ->addColumn('sales_cash_fmt', fn($r) => 'Rp ' . number_format($r->total_sales_cash, 0, ',', '.'))
                ->addColumn('sales_non_cash_fmt', fn($r) => 'Rp ' . number_format($r->total_sales_non_cash, 0, ',', '.'))
                ->addColumn('expected_fmt', fn($r) => 'Rp ' . number_format($r->expected_cash, 0, ',', '.'))
                ->addColumn('actual_fmt', fn($r) => $r->actual_cash !== null ? 'Rp ' . number_format($r->actual_cash, 0, ',', '.') : '-')
                ->addColumn('variance_badge', function ($r) {
                    if ($r->status === 'open') return '<span class="badge badge-info">Open</span>';
                    if ($r->variance == 0) return '<span class="badge badge-success">Cocok (Rp 0)</span>';
                    if ($r->variance > 0) return '<span class="badge badge-warning">+' . number_format($r->variance, 0, ',', '.') . '</span>';
                    return '<span class="badge badge-danger">-' . number_format(abs($r->variance), 0, ',', '.') . '</span>';
                })
                ->addColumn('date', fn($r) => $r->created_at->format('d/m/Y H:i'))
                ->rawColumns(['variance_badge'])
                ->make(true);
        }

        $outlets = Outlet::all();
        return view('reports.shifts', compact('outlets'));
    }

    public function productMargins(): View
    {
        $products = Product::with('recipeItems.material')->orderBy('name')->get()->map(function ($product) {
            $cost = $product->recipeItems->sum(fn ($recipe) => $recipe->quantity * (optional($recipe->material->batches()->latest()->first())->purchase_price ?? 0));
            return ['name' => $product->name, 'price' => $product->price, 'cost' => $cost, 'margin' => $product->price - $cost, 'margin_percent' => $product->price > 0 ? (($product->price - $cost) / $product->price) * 100 : 0];
        });
        return view('reports.product-margins', compact('products'));
    }

    public function profitLoss(Request $request): View
    {
        $sales = Sale::visibleToCurrentOutlet()->where('status', 'completed');
        if ($request->filled('start_date') && $request->filled('end_date')) $sales->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        $revenue = (clone $sales)->sum('total_price');
        $cogs = (clone $sales)->with('items.product.recipeItems.material')->get()->sum(fn ($sale) => $sale->items->sum(fn ($item) => $item->product->recipeItems->sum(fn ($recipe) => $recipe->quantity * (optional($recipe->material->batches()->latest()->first())->purchase_price ?? 0) * $item->quantity)));
        return view('reports.profit-loss', ['revenue' => $revenue, 'cogs' => $cogs, 'grossProfit' => $revenue - $cogs]);
    }

    public function analytics(): View
    {
        $topProducts = SaleItem::with('product')->selectRaw('product_id, SUM(quantity) quantity, SUM(subtotal) revenue')->groupBy('product_id')->orderByDesc('quantity')->limit(10)->get();
        $lowProducts = SaleItem::with('product')->selectRaw('product_id, SUM(quantity) quantity')->groupBy('product_id')->orderBy('quantity')->limit(10)->get();
        $criticalMaterials = Material::whereColumn('stock', '<=', 'reorder_point')->orderBy('stock')->get();
        return view('reports.analytics', compact('topProducts', 'lowProducts', 'criticalMaterials'));
    }

    public function exportProfitLoss(Request $request): StreamedResponse
    {
        $sales = Sale::visibleToCurrentOutlet()->where('status', 'completed');
        if ($request->filled('start_date') && $request->filled('end_date')) $sales->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        $rows = $sales->get();
        return response()->streamDownload(function () use ($rows) { $handle = fopen('php://output', 'w'); fputcsv($handle, ['Invoice', 'Tanggal', 'Penjualan', 'Metode']); foreach ($rows as $sale) fputcsv($handle, [$sale->invoice_number, $sale->created_at->format('Y-m-d H:i'), $sale->total_price, $sale->payment_method]); fclose($handle); }, 'laporan-laba-rugi.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function inventoryAlerts(): View
    {
        $criticalMaterials = Material::whereColumn('stock', '<=', 'reorder_point')->orderBy('stock')->get();
        $expiringBatches = \App\Models\MaterialBatch::with('material')->whereNotNull('expired_date')->whereBetween('expired_date', [today(), today()->addDays(30)])->orderBy('expired_date')->get();
        return view('reports.inventory-alerts', compact('criticalMaterials', 'expiringBatches'));
    }

    public function businessAnalytics(): View
    {
        $hourlySales = Sale::visibleToCurrentOutlet()->where('status', 'completed')->selectRaw('HOUR(created_at) hour, SUM(total_price) total')->groupBy('hour')->orderBy('hour')->get();
        $outletComparison = Sale::visibleToCurrentOutlet()->where('status', 'completed')->with('shift.outlet')->get()->groupBy(fn ($sale) => $sale->shift->outlet->name ?? 'Pusat')->map(fn ($sales) => $sales->sum('total_price'));
        $forecast = Material::select('name', 'stock', 'reorder_point')->whereColumn('stock', '<=', 'reorder_point')->get();
        return view('reports.business-analytics', compact('hourlySales', 'outletComparison', 'forecast'));
    }

    public function exportSalesCsv(Request $request): StreamedResponse
    {
        $sales = Sale::visibleToCurrentOutlet()->with(['user', 'shift.outlet', 'items.product'])->latest();
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $sales->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }
        $list = $sales->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="laporan_penjualan_' . date('Ymd_His') . '.csv"',
        ];

        return response()->stream(function () use ($list) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['No. Invoice', 'Tanggal & Waktu', 'Kasir', 'Outlet', 'Metode Bayar', 'Total Tagihan', 'Nominal Bayar', 'Kembalian']);

            foreach ($list as $s) {
                fputcsv($handle, [
                    $s->invoice_number,
                    $s->created_at->format('d/m/Y H:i'),
                    $s->user->name ?? '-',
                    $s->shift->outlet->name ?? '-',
                    strtoupper($s->payment_method),
                    $s->total_price,
                    $s->payment_amount,
                    $s->change_amount,
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }

    public function exportInventoryCsv(Request $request): StreamedResponse
    {
        $logs = InventoryLog::with(['material', 'batch'])->latest();
        if ($request->filled('material_id')) {
            $logs->where('material_id', $request->material_id);
        }
        $list = $logs->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="laporan_mutasi_stok_' . date('Ymd_His') . '.csv"',
        ];

        return response()->stream(function () use ($list) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['ID Log', 'Tanggal & Waktu', 'Nama Bahan Baku', 'No. Batch', 'Tipe Mutasi', 'Jumlah', 'Satuan Resep', 'Stok Akhir', 'Referensi Aktivitas']);

            foreach ($list as $l) {
                fputcsv($handle, [
                    $l->id,
                    $l->created_at->format('d/m/Y H:i'),
                    $l->material->name ?? '-',
                    $l->batch->batch_number ?? '-',
                    strtoupper($l->type),
                    $l->quantity,
                    $l->material->recipe_unit ?? '',
                    $l->stock_after,
                    $l->reference,
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }
}
