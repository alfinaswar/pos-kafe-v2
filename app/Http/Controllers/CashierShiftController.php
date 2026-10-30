<?php

namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Models\PettyCash;
use App\Models\Outlet;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class CashierShiftController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:shift-manage');
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $shifts = CashierShift::with(['user', 'outlet'])->latest();

            if ($request->filled('outlet_id')) {
                $shifts->where('outlet_id', $request->outlet_id);
            }
            if ($request->filled('status')) {
                $shifts->where('status', $request->status);
            }

            return DataTables::of($shifts)
                ->addIndexColumn()
                ->addColumn('cashier_name', fn($r) => $r->user->name ?? '-')
                ->addColumn('outlet_name', fn($r) => $r->outlet->name ?? 'Semua Outlet')
                ->addColumn('start_cash_fmt', fn($r) => 'Rp ' . number_format($r->start_cash, 0, ',', '.'))
                ->addColumn('total_sales_cash_fmt', fn($r) => 'Rp ' . number_format($r->total_sales_cash, 0, ',', '.'))
                ->addColumn('expected_cash_fmt', fn($r) => 'Rp ' . number_format($r->expected_cash, 0, ',', '.'))
                ->addColumn('actual_cash_fmt', fn($r) => $r->actual_cash !== null ? 'Rp ' . number_format($r->actual_cash, 0, ',', '.') : '-')
                ->addColumn('variance_fmt', function ($r) {
                    if ($r->status === 'open') return '<span class="badge badge-info">Shift Aktif</span>';
                    $val = $r->variance;
                    if ($val == 0) {
                        return '<span class="badge badge-success">Cocok (Rp 0)</span>';
                    } elseif ($val > 0) {
                        return '<span class="badge badge-warning">Lebih (+Rp ' . number_format($val, 0, ',', '.') . ')</span>';
                    } else {
                        return '<span class="badge badge-danger">Kurang (-Rp ' . number_format(abs($val), 0, ',', '.') . ')</span>';
                    }
                })
                ->addColumn('status_badge', function ($r) {
                    return $r->status === 'open' 
                        ? '<span class="badge badge-success"><i class="fas fa-unlock mr-1"></i> OPEN</span>' 
                        : '<span class="badge badge-secondary"><i class="fas fa-lock mr-1"></i> CLOSED</span>';
                })
                ->addColumn('action', function ($r) {
                    $btn = '<a href="' . route('shifts.show', $r->id) . '" class="btn btn-info btn-sm mr-1" title="Detail Rekonsiliasi"><i class="fas fa-eye"></i></a>';
                    if ($r->status === 'open' && ($r->user_id === auth()->id() || auth()->user()->hasRole('Owner'))) {
                        $btn .= '<a href="' . route('shifts.close.form', $r->id) . '" class="btn btn-warning btn-sm" title="Tutup Shift"><i class="fas fa-cash-register"></i> Tutup</a>';
                    }
                    return '<div class="text-center">' . $btn . '</div>';
                })
                ->rawColumns(['variance_fmt', 'status_badge', 'action'])
                ->make(true);
        }

        $outlets = Outlet::all();
        return view('shifts.index', compact('outlets'));
    }

    public function create(): View|RedirectResponse
    {
        // Cek jika kasir sudah punya shift terbuka
        $activeShift = CashierShift::where('user_id', auth()->id())->where('status', 'open')->first();
        if ($activeShift) {
            return redirect()->route('pos.index')->with('info', 'Anda sudah memiliki shift yang sedang aktif.');
        }

        $outlets = Outlet::all();
        $userOutlet = auth()->user()->outlet;
        return view('shifts.create', compact('outlets', 'userOutlet'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'start_cash' => 'required|numeric|min:0',
            'outlet_id' => 'nullable|exists:outlets,id',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $activeShift = CashierShift::where('user_id', auth()->id())->where('status', 'open')->first();
            if ($activeShift) {
                return redirect()->route('pos.index')->with('error', 'Anda masih memiliki shift aktif.');
            }

            $shift = CashierShift::create([
                'user_id' => auth()->id(),
                'outlet_id' => $request->outlet_id ?? auth()->user()->outlet_id,
                'start_cash' => $request->start_cash,
                'expected_cash' => $request->start_cash,
                'status' => 'open',
                'notes' => $request->notes,
            ]);

            ActivityLog::record('OPEN_SHIFT', "Kasir " . auth()->user()->name . " membuka shift #{$shift->id} dengan modal awal Rp " . number_format($request->start_cash, 0, ',', '.'));

            return redirect()->route('pos.index')->with('success', 'Shift kasir berhasil dibuka. Selamat bertransaksi!');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal membuka shift kasir: ' . $e->getMessage());
        }
    }

    public function show(CashierShift $shift): View
    {
        $shift->load(['user', 'outlet', 'sales.items.product', 'pettyCashes']);

        $pettyIn = $shift->pettyCashes->where('type', 'in')->sum('amount');
        $pettyOut = $shift->pettyCashes->where('type', 'out')->sum('amount');
        $expectedCash = $shift->start_cash + $shift->total_sales_cash + $pettyIn - $pettyOut;

        return view('shifts.show', compact('shift', 'pettyIn', 'pettyOut', 'expectedCash'));
    }

    public function closeForm(CashierShift $shift): View|RedirectResponse
    {
        if ($shift->status !== 'open') {
            return redirect()->route('shifts.show', $shift->id)->with('info', 'Shift ini sudah ditutup.');
        }

        $pettyIn = $shift->pettyCashes()->where('type', 'in')->sum('amount');
        $pettyOut = $shift->pettyCashes()->where('type', 'out')->sum('amount');
        $expectedCash = $shift->start_cash + $shift->total_sales_cash + $pettyIn - $pettyOut;

        return view('shifts.close', compact('shift', 'pettyIn', 'pettyOut', 'expectedCash'));
    }

    public function close(Request $request, CashierShift $shift): RedirectResponse
    {
        if ($shift->status !== 'open') {
            return redirect()->route('shifts.show', $shift->id)->with('error', 'Shift ini sudah ditutup sebelumnya.');
        }

        $request->validate([
            'actual_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($request, $shift) {
                $pettyIn = (float) $shift->pettyCashes()->where('type', 'in')->sum('amount');
                $pettyOut = (float) $shift->pettyCashes()->where('type', 'out')->sum('amount');
                $expectedCash = (float) $shift->start_cash + (float) $shift->total_sales_cash + $pettyIn - $pettyOut;
                $actualCash = (float) $request->actual_cash;
                $variance = $actualCash - $expectedCash;

                $shift->update([
                    'end_cash' => $actualCash,
                    'expected_cash' => $expectedCash,
                    'actual_cash' => $actualCash,
                    'variance' => $variance,
                    'status' => 'closed',
                    'closed_at' => now(),
                    'notes' => ($shift->notes ? $shift->notes . " | " : "") . ($request->notes ?? 'Tutup Shift'),
                ]);

                ActivityLog::record(
                    'CLOSE_SHIFT',
                    "Tutup shift #{$shift->id}: Ekspektasi Rp " . number_format($expectedCash, 0, ',', '.') .
                    ", Aktual Rp " . number_format($actualCash, 0, ',', '.') .
                    ", Selisih Rp " . number_format($variance, 0, ',', '.')
                );
            });

            return redirect()->route('shifts.show', $shift->id)->with('success', 'Shift kasir berhasil ditutup dan direkonsiliasi.');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal menutup shift: ' . $e->getMessage());
        }
    }

    // Petty Cash Store via Ajax or Form
    public function storePettyCash(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'shift_id' => 'required|exists:cashier_shifts,id',
            'type' => 'required|in:in,out',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
        ]);

        try {
            $shift = CashierShift::findOrFail($request->shift_id);
            if ($shift->status !== 'open') {
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Shift sudah ditutup.'], 422);
                }
                return back()->with('error', 'Shift sudah ditutup.');
            }

            $petty = PettyCash::create([
                'user_id' => auth()->id(),
                'shift_id' => $shift->id,
                'type' => $request->type,
                'amount' => $request->amount,
                'description' => $request->description,
            ]);

            // Update expected cash on shift
            $shift->expected_cash = $shift->calculateExpectedCash();
            $shift->save();

            ActivityLog::record(
                'PETTY_CASH',
                "Kas kecil ({$request->type}) Rp " . number_format($request->amount, 0, ',', '.') . ": {$request->description}"
            );

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Kas kecil berhasil dicatat.',
                    'petty' => $petty,
                    'new_expected_cash' => $shift->expected_cash
                ]);
            }

            return back()->with('success', 'Kas kecil berhasil dicatat.');
        } catch (\Throwable $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', 'Gagal mencatat kas kecil: ' . $e->getMessage());
        }
    }
}
