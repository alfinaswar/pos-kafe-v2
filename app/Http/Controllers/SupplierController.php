<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Yajra\DataTables\Facades\DataTables;

class SupplierController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:supplier-list|supplier-create|supplier-edit|supplier-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:supplier-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:supplier-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:supplier-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $suppliers = Supplier::query()->latest();
            return DataTables::of($suppliers)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $editBtn = auth()->user()->can('supplier-edit')
                        ? '<a href="'.route('suppliers.edit', $row->id).'" class="btn btn-warning btn-sm mr-1"><i class="fas fa-edit"></i></a>'
                        : '';
                    $deleteBtn = auth()->user()->can('supplier-delete')
                        ? '<button type="button" class="btn btn-danger btn-sm btn-delete" data-url="'.route('suppliers.destroy', $row->id).'"><i class="fas fa-trash"></i></button>'
                        : '';
                    return '<div class="text-center">' . $editBtn . $deleteBtn . '</div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('suppliers.index');
    }

    public function create(): View
    {
        return view('suppliers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);

        try {
            Supplier::create($request->all());

            return redirect()->route('suppliers.index')
                ->with('success', 'Supplier created successfully.');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal menambahkan supplier: ' . $e->getMessage());
        }
    }

    public function edit(Supplier $supplier): View
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);

        try {
            $supplier->update($request->all());

            return redirect()->route('suppliers.index')
                ->with('success', 'Supplier updated successfully.');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui supplier: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, Supplier $supplier)
    {
        try {
            if ($supplier->purchases()->count() > 0) {
                throw new \Exception('Supplier tidak dapat dihapus karena memiliki riwayat Purchase Order.');
            }

            $supplier->delete();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Supplier deleted successfully'
                ]);
            }

            return redirect()->route('suppliers.index')
                ->with('success', 'Supplier deleted successfully.');
        } catch (\Throwable $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 422);
            }

            return redirect()->route('suppliers.index')
                ->with('error', $e->getMessage());
        }
    }
}
