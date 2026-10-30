<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Material;
use App\Models\MaterialBatch;
use App\Models\Purchase;
use App\Models\Product;
use App\Models\CashierShift;
use App\Models\SaleItem;
use App\Models\Outlet;
use App\Support\OutletScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View
    {
        $cacheKey = 'dashboard:' . (OutletScope::current()?->id ?? 'all');
        $metrics = Cache::remember($cacheKey, now()->addSeconds(60), fn () => $this->dashboardMetrics());
        extract($metrics);

        return view('home', compact(
            'todaySales',
            'todayOrders',
            'avgTransaction',
            'grossMarginToday',
            'topProducts',
            'totalProducts',
            'totalOutlets',
            'lowStockMaterials',
            'nearExpiryBatches',
            'expiredBatches',
            'pendingPurchases',
            'activeShifts'
        ));
    }

    /**
     * Semua query dashboard dibungkus satu closure agar bisa di-cache 60 detik
     * per outlet — mengurangi beban DB saat banyak layar dashboard dibuka.
     */
    private function dashboardMetrics(): array
    {
        // 1. Total Penjualan Hari ini (hanya outlet user, kecuali Owner/Admin)
        $todaySales = Sale::visibleToCurrentOutlet()->whereDate('created_at', today())->sum('total_price');
        $todayOrders = Sale::visibleToCurrentOutlet()->whereDate('created_at', today())->count();

        // 2. Total Menu & Outlet
        $totalProducts = Product::count();
        $totalOutlets = Outlet::count();

        // 3. Stok Bahan Kritis (ROP Warning) — per outlet via material_outlet_stocks
        $lowStockMaterials = $this->lowStockMaterialsQuery()->get();

        // 4. Batch Kadaluarsa & Mendekati Kadaluarsa (<= 7 hari)
        $nearExpiryBatches = MaterialBatch::where('remaining_qty', '>', 0)
            ->whereNotNull('expired_date')
            ->where('expired_date', '>=', now())
            ->where('expired_date', '<=', now()->addDays(7))
            ->when(OutletScope::current(), fn ($q, $outletId) => $q->whereHas('material.outletStocks', fn ($s) => $s->where('outlet_id', $outletId)))
            ->with('material')
            ->get();

        $expiredBatches = MaterialBatch::where('remaining_qty', '>', 0)
            ->whereNotNull('expired_date')
            ->where('expired_date', '<', now())
            ->when(OutletScope::current(), fn ($q, $outletId) => $q->whereHas('material.outletStocks', fn ($s) => $s->where('outlet_id', $outletId)))
            ->with('material')
            ->get();

        // 5. Pending Purchase Orders (Menunggu Approval Owner) — per outlet
        $pendingPurchases = Purchase::visibleToCurrentOutlet()->where('status', 'pending')->with('supplier')->get();

        // 6. Shift Kasir Aktif — per outlet
        $activeShifts = CashierShift::visibleToCurrentOutlet()->where('status', 'open')->with(['user', 'outlet'])->get();

        // 7. Metrik bisnis ringkas: rata-rata transaksi & margin kotor hari ini (P2 dashboard lengkap)
        $completedToday = Sale::visibleToCurrentOutlet()->whereDate('created_at', today())->where('status', 'completed');
        $avgTransaction = $todayOrders > 0 ? round((clone $completedToday)->sum('total_price') / max(1, (clone $completedToday)->count()), 0) : 0;
        $cogsToday = (clone $completedToday)->with('items')->get()->sum(fn ($sale) => $sale->items->sum('cost_total'));
        $grossMarginToday = (clone $completedToday)->sum('total_price') - $cogsToday;

        // 8. Menu terlaris hari ini (filter outlet lewat relasi sale)
        $topProducts = SaleItem::query()
            ->whereHas('sale', fn ($q) => $q->visibleToCurrentOutlet()->whereDate('sales.created_at', today()))
            ->selectRaw('product_id, SUM(quantity) as qty, SUM(subtotal) as revenue')
            ->groupBy('product_id')
            ->orderByDesc('qty')
            ->with('product')
            ->limit(5)
            ->get();

        return [
            'todaySales' => $todaySales,
            'todayOrders' => $todayOrders,
            'avgTransaction' => $avgTransaction,
            'grossMarginToday' => $grossMarginToday,
            'topProducts' => $topProducts,
            'totalProducts' => $totalProducts,
            'totalOutlets' => $totalOutlets,
            'lowStockMaterials' => $lowStockMaterials,
            'nearExpiryBatches' => $nearExpiryBatches,
            'expiredBatches' => $expiredBatches,
            'pendingPurchases' => $pendingPurchases,
            'activeShifts' => $activeShifts,
        ];
    }

    /**
     * Query bahan dengan stok kritis (stok <= reorder point), dibatasi per outlet.
     *
     * Bila tabel material_outlet_stocks sudah terisi, stok per outlet yang dipakai.
     * Bila belum ada baris untuk suatu bahan, fallback ke kolom stok global materials.stock
     * agar dashboard tetap berguna saat data per-outlet belum dimigrasikan.
     */
    private function lowStockMaterialsQuery()
    {
        $outletId = OutletScope::current();

        $query = Material::query();

        if ($outletId) {
            // Bahan yang punya baris stok di outlet ini: pakai stok outlet tersebut.
            $hasOutletRow = fn ($q) => $q->whereHas('outletStocks', fn ($s) => $s->where('outlet_id', $outletId));

            // Bahan yang belum punya baris stok outlet: pakai stok global.
            $noOutletRow = fn ($q) => $q->whereDoesntHave('outletStocks', fn ($s) => $s->where('outlet_id', $outletId));

            $query->where(function ($q) use ($outletId, $hasOutletRow, $noOutletRow) {
                $q->where(function ($sub) use ($outletId, $hasOutletRow) {
                    $sub->where($hasOutletRow);
                    $sub->addSelect(
                        'materials.*',
                        \DB::raw('(SELECT mos.stock FROM material_outlet_stocks mos
                                   WHERE mos.material_id = materials.id AND mos.outlet_id = ? LIMIT 1) as stock')
                    );
                    $sub->addBinding($outletId, 'select');
                })->orWhere(function ($sub) use ($noOutletRow) {
                    $sub->where($noOutletRow);
                });
            });
        }

        return $query->whereColumn('stock', '<=', 'reorder_point')
            ->orderBy('stock', 'asc');
    }
}
