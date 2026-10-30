<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Material;
use App\Models\RecipeItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:product-list|product-create|product-edit|product-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:product-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:product-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:product-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $products = Product::with('recipeItems.material')->latest();
            return DataTables::of($products)
                ->addIndexColumn()
                ->addColumn('price_formatted', function ($row) {
                    return 'Rp ' . number_format($row->price, 0, ',', '.');
                })
                ->addColumn('recipe_summary', function ($row) {
                    $count = $row->recipeItems->count();
                    return '<span class="badge badge-info">' . $count . ' Ingredients (BOM)</span>';
                })
                ->addColumn('action', function ($row) {
                    $editBtn = auth()->user()->can('product-edit')
                        ? '<a href="'.route('products.edit', $row->id).'" class="btn btn-warning btn-sm mr-1" title="Edit"><i class="fas fa-edit"></i></a>'
                        : '';
                    $deleteBtn = auth()->user()->can('product-delete')
                        ? '<button type="button" class="btn btn-danger btn-sm btn-delete" data-url="'.route('products.destroy', $row->id).'" title="Delete"><i class="fas fa-trash"></i></button>'
                        : '';
                    return '<div class="text-center">' . $editBtn . $deleteBtn . '</div>';
                })
                ->rawColumns(['recipe_summary', 'action'])
                ->make(true);
        }

        return view('products.index');
    }

    public function create(): View
    {
        $materials = Material::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        return view('products.create', compact('materials', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'price' => 'required|numeric|min:0',
            'detail' => 'nullable|string',
            'materials' => 'nullable|array',
            'materials.*.id' => 'required_with:materials|exists:materials,id',
            'materials.*.qty' => 'required_with:materials|numeric|min:0.01',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $product = Product::create([
                    'name' => $request->name,
                    'category' => $request->category,
                    'price' => $request->price,
             'detail' => $request->detail,
                    'is_bundle' => $request->boolean('is_bundle'),
                ]);
                foreach ($request->input('bundle_items', []) as $productId => $bundle) {
                    if (!empty($bundle['selected']) && (int) $productId !== $product->id) $product->bundleItems()->create(['product_id' => $productId, 'quantity' => max(1, (int) ($bundle['quantity'] ?? 1))]);
                }

                // Simpan Bill of Materials (Resep)
                if ($request->has('materials')) {
                    foreach ($request->materials as $mat) {
                        if (!empty($mat['id']) && !empty($mat['qty'])) {
                            RecipeItem::create([
                                'product_id' => $product->id,
                                'material_id' => $mat['id'],
                                'quantity' => $mat['qty']
                            ]);
                        }
                    }
                }
            });

            return redirect()->route('products.index')
                ->with('success', 'Product and Recipe (BOM) saved successfully.');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal menyimpan menu: ' . $e->getMessage());
        }
    }

    public function show(Product $product): View
    {
        $product->load('recipeItems.material');
        return view('products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        $product->load('recipeItems.material');
        $materials = Material::orderBy('name')->get();
        return view('products.edit', compact('product', 'materials'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'price' => 'required|numeric|min:0',
            'detail' => 'nullable|string',
            'materials' => 'nullable|array',
            'materials.*.id' => 'required_with:materials|exists:materials,id',
            'materials.*.qty' => 'required_with:materials|numeric|min:0.01',
        ]);

        try {
            DB::transaction(function () use ($request, $product) {
                $product->update([
                    'name' => $request->name,
                    'category' => $request->category,
                    'price' => $request->price,
                    'detail' => $request->detail,
                ]);

                // Sync Resep
                RecipeItem::where('product_id', $product->id)->delete();

                if ($request->has('materials')) {
                    foreach ($request->materials as $mat) {
                        if (!empty($mat['id']) && !empty($mat['qty'])) {
                            RecipeItem::create([
                                'product_id' => $product->id,
                                'material_id' => $mat['id'],
                                'quantity' => $mat['qty']
                            ]);
                        }
                    }
                }
            });

            return redirect()->route('products.index')
                ->with('success', 'Product and Recipe updated successfully.');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui menu: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, Product $product)
    {
        try {
            if ($product->saleItems()->count() > 0) {
                throw new \Exception('Menu tidak dapat dihapus karena memiliki riwayat transaksi penjualan.');
            }

            $product->delete();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Product deleted successfully'
                ]);
            }

            return redirect()->route('products.index')
                ->with('success', 'Product deleted successfully.');
        } catch (\Throwable $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 422);
            }

            return redirect()->route('products.index')
                ->with('error', $e->getMessage());
        }
    }
}
