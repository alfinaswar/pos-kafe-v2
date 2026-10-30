<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Yajra\DataTables\Facades\DataTables;

class MaterialController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:material-list|material-create|material-edit|material-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:material-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:material-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:material-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $materials = Material::query()->latest();
            return DataTables::of($materials)
                ->addIndexColumn()
                ->addColumn('stock_display', function ($row) {
                    $badge = $row->isLowStock() ? 'badge-danger' : 'badge-success';
                    return '<span class="badge ' . $badge . '">' . number_format($row->stock, 2) . ' ' . e($row->recipe_unit) . '</span>';
                })
                ->addColumn('unit_info', function ($row) {
                    return '1 ' . e($row->purchase_unit) . ' = ' . number_format($row->conversion_factor, 2) . ' ' . e($row->recipe_unit);
                })
                ->addColumn('rop_display', function ($row) {
                    return number_format($row->reorder_point, 2) . ' ' . e($row->recipe_unit);
                })
                ->addColumn('action', function ($row) {
                    $editBtn = auth()->user()->can('material-edit')
                        ? '<a href="'.route('materials.edit', $row->id).'" class="btn btn-warning btn-sm mr-1"><i class="fas fa-edit"></i></a>'
                        : '';
                    $deleteBtn = auth()->user()->can('material-delete')
                        ? '<button type="button" class="btn btn-danger btn-sm btn-delete" data-url="'.route('materials.destroy', $row->id).'"><i class="fas fa-trash"></i></button>'
                        : '';
                    return '<div class="text-center">' . $editBtn . $deleteBtn . '</div>';
                })
                ->rawColumns(['stock_display', 'unit_info', 'action'])
                ->make(true);
        }

        return view('materials.index');
    }

    public function create(): View
    {
        return view('materials.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'stock' => 'nullable|numeric|min:0',
            'purchase_unit' => 'required|string|max:50',
            'recipe_unit' => 'required|string|max:50',
            'conversion_factor' => 'required|numeric|min:0.01',
            'reorder_point' => 'required|numeric|min:0',
        ]);

        try {
            $input = $request->all();
            $input['stock'] = $request->input('stock', 0);

            Material::create($input);

            return redirect()->route('materials.index')
                ->with('success', 'Material created successfully.');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal menambahkan bahan baku: ' . $e->getMessage());
        }
    }

    public function edit(Material $material): View
    {
        return view('materials.edit', compact('material'));
    }

    public function update(Request $request, Material $material): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'purchase_unit' => 'required|string|max:50',
            'recipe_unit' => 'required|string|max:50',
            'conversion_factor' => 'required|numeric|min:0.01',
            'reorder_point' => 'required|numeric|min:0',
        ]);

        try {
            $material->update($request->except(['stock']));

            return redirect()->route('materials.index')
                ->with('success', 'Material updated successfully.');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui bahan baku: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, Material $material)
    {
        try {
            if ($material->recipeItems()->count() > 0) {
                throw new \Exception('Bahan baku tidak dapat dihapus karena masih digunakan pada menu/resep.');
            }
            if ($material->batches()->count() > 0) {
                throw new \Exception('Bahan baku tidak dapat dihapus karena memiliki riwayat batch inventaris.');
            }

            $material->delete();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Material deleted successfully'
                ]);
            }

            return redirect()->route('materials.index')
                ->with('success', 'Material deleted successfully.');
        } catch (\Throwable $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 422);
            }

            return redirect()->route('materials.index')
                ->with('error', $e->getMessage());
        }
    }
}
