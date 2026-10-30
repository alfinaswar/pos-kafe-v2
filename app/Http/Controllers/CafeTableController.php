<?php

namespace App\Http\Controllers;

use App\Models\CafeTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;

class CafeTableController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:table-manage');
    }

    public function index(): View
    {
        $tables = CafeTable::with('outlet')->where('outlet_id', auth()->user()->outlet_id)->latest()->paginate(20);
        return view('tables.index', compact('tables'));
    }

    public function create(): View
    {
        return view('tables.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        CafeTable::create($data + ['outlet_id' => $request->user()->outlet_id]);

        return redirect()->route('tables.index')->with('success', 'Meja berhasil dibuat.');
    }

    public function qr(CafeTable $table): View
    {
        abort_unless($table->outlet_id === auth()->user()->outlet_id, 403);
        return view('tables.qr', compact('table'));
    }

    public function qrImage(CafeTable $table)
    {
        abort_unless($table->outlet_id === auth()->user()->outlet_id, 403);
        $result = Builder::create()
            ->writer(new SvgWriter())
            ->data(url('/order/' . $table->qr_token))
            ->size(300)
            ->margin(10)
            ->build();

        return response($result->getString(), 200, ['Content-Type' => 'image/svg+xml']);
    }

    public function occupancy(Request $request, CafeTable $table): JsonResponse
    {
        abort_unless($table->outlet_id === $request->user()->outlet_id, 403);
        $data = $request->validate(['occupancy_status' => ['required', 'in:available,occupied,cleaning']]);
        $table->update(['occupancy_status' => $data['occupancy_status']]);
        return response()->json(['success' => true, 'occupancy_status' => $table->occupancy_status]);
    }

    public function moveSession(Request $request, \App\Models\TableSession $session): JsonResponse
    {
        $data = $request->validate(['target_table_id' => ['required', 'exists:cafe_tables,id']]);
        abort_unless($session->table->outlet_id === $request->user()->outlet_id, 403);
        $target = CafeTable::where('outlet_id', $request->user()->outlet_id)->findOrFail($data['target_table_id']);
        $session->update(['cafe_table_id' => $target->id]);
        return response()->json(['success' => true, 'table_id' => $target->id]);
    }

    public function mergeSession(Request $request, \App\Models\TableSession $session): JsonResponse
    {
        $data = $request->validate(['target_session_id' => ['required', 'exists:table_sessions,id']]);
        $target = \App\Models\TableSession::findOrFail($data['target_session_id']);
        abort_unless($session->table->outlet_id === $request->user()->outlet_id && $target->table->outlet_id === $request->user()->outlet_id, 403);
        $session->orders()->update(['table_session_id' => $target->id]);
        $session->update(['status' => 'closed', 'closed_at' => now()]);
        return response()->json(['success' => true, 'target_session_id' => $target->id]);
    }
}
