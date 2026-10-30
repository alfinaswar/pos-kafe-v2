<?php

namespace App\Http\Controllers;

use App\Models\Outlet;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Yajra\DataTables\Facades\DataTables;

class OutletController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:outlet-list|outlet-create|outlet-edit|outlet-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:outlet-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:outlet-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:outlet-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $data = Outlet::withCount(['shifts', 'users'])->latest();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $btn = '';
                    if (auth()->user()->can('outlet-edit')) {
                        $btn .= '<a href="' . route('outlets.edit', $row->id) . '" class="btn btn-primary btn-sm mr-1"><i class="fas fa-edit"></i> Edit</a>';
                    }
                    if (auth()->user()->can('outlet-delete')) {
                        $btn .= '<form action="' . route('outlets.destroy', $row->id) . '" method="POST" class="d-inline" onsubmit="return confirm(\'Hapus cabang outlet ini?\')">'
                            . csrf_field() . method_field('DELETE')
                            . '<button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Hapus</button></form>';
                    }
                    return '<div class="text-center">' . $btn . '</div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('outlets.index');
    }

    public function create(): View
    {
        return view('outlets.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);

        try {
            $outlet = Outlet::create($request->all());

            ActivityLog::record('CREATE_OUTLET', "Membuat outlet baru: {$outlet->name}");

            return redirect()->route('outlets.index')
                ->with('success', 'Outlet berhasil ditambahkan.');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal menambahkan outlet: ' . $e->getMessage());
        }
    }

    public function edit(Outlet $outlet): View
    {
        return view('outlets.edit', compact('outlet'));
    }

    public function update(Request $request, Outlet $outlet): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);

        try {
            $outlet->update($request->all());

            ActivityLog::record('UPDATE_OUTLET', "Memperbarui outlet: {$outlet->name}");

            return redirect()->route('outlets.index')
                ->with('success', 'Outlet berhasil diperbarui.');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui outlet: ' . $e->getMessage());
        }
    }

    public function destroy(Outlet $outlet): RedirectResponse
    {
        try {
            if ($outlet->shifts()->count() > 0) {
                return back()->with('error', 'Outlet tidak dapat dihapus karena sudah memiliki riwayat shift/transaksi.');
            }
            if ($outlet->users()->count() > 0) {
                return back()->with('error', 'Outlet tidak dapat dihapus karena masih memiliki user/staff terdaftar.');
            }

            $name = $outlet->name;
            $outlet->delete();

            ActivityLog::record('DELETE_OUTLET', "Menghapus outlet: {$name}");

            return redirect()->route('outlets.index')
                ->with('success', 'Outlet berhasil dihapus.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menghapus outlet: ' . $e->getMessage());
        }
    }
}
