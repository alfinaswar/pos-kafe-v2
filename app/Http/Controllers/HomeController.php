<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Material;
use App\Models\MaterialBatch;
use App\Models\Purchase;
use App\Models\Product;
use App\Models\CashierShift;
use App\Models\Outlet;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View
    {
        // 1. Total Penjualan Hari ini
        $todaySales = Sale::whereDate('created_at', today())->sum('total_price');
        $todayOrders = Sale::whereDate('created_at', today())->count();

        // 2. Total Menu & Outlet
        $totalProducts = Product::count();
        $totalOutlets = Outlet::count();

        // 3. Stok Bahan Kritis (ROP Warning)
        $lowStockMaterials = Material::whereRaw('stock <= reorder_point')->get();

        // 4. Batch Kadaluarsa & Mendekati Kadaluarsa (<= 7 hari)
        $nearExpiryBatches = MaterialBatch::where('remaining_qty', '>', 0)
            ->whereNotNull('expired_date')
            ->where('expired_date', '>=', now())
            ->where('expired_date', '<=', now()->addDays(7))
            ->with('material')
            ->get();

        $expiredBatches = MaterialBatch::where('remaining_qty', '>', 0)
            ->whereNotNull('expired_date')
            ->where('expired_date', '<', now())
            ->with('material')
            ->get();

        // 5. Pending Purchase Orders (Menunggu Approval Owner)
        $pendingPurchases = Purchase::where('status', 'pending')->with('supplier')->get();

        // 6. Shift Kasir Aktif
        $activeShifts = CashierShift::where('status', 'open')->with(['user', 'outlet'])->get();

        return view('home', compact(
            'todaySales',
            'todayOrders',
            'totalProducts',
            'totalOutlets',
            'lowStockMaterials',
            'nearExpiryBatches',
            'expiredBatches',
            'pendingPurchases',
            'activeShifts'
        ));
    }
}
