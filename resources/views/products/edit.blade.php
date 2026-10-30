@extends('layouts.app')

@section('title', 'Edit Product & Recipe')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<form action="{{ route('products.update', $product->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="row">
        <!-- Info Produk -->
        <div class="col-md-5">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">Edit Product: <strong>{{ $product->name }}</strong></h3>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show">
                            <ul class="mb-0 pl-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    <div class="form-group">
                        <label for="name">Menu Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" id="name" value="{{ old('name', $product->name) }}" required>
                    </div>

                    <div class="form-group">
                        <label for="category">Category</label>
                        <input type="text" name="category" class="form-control" id="category" value="{{ old('category', $product->category) }}">
                    </div>

                    <div class="form-group">
                        <label for="price">Selling Price (Rp) <span class="text-danger">*</span></label>
                        <input type="number" step="any" name="price" class="form-control" id="price" value="{{ old('price', $product->price) }}" required>
                    </div>

                    <div class="form-group">
                        <label for="detail">Description / Detail</label>
                        <textarea name="detail" class="form-control" id="detail" rows="3">{{ old('detail', $product->detail) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resep Bahan Baku (Bill of Materials) -->
        <div class="col-md-7">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-receipt mr-1"></i> Bill of Materials (BOM / Resep)</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-success btn-sm" id="btn-add-ingredient">
                            <i class="fas fa-plus mr-1"></i> Add Ingredient
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered mb-0" id="table-ingredients">
                        <thead>
                            <tr>
                                <th>Material (Bahan)</th>
                                <th style="width: 160px;">Quantity</th>
                                <th style="width: 100px;">Unit</th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Dynamic Rows filled via JS -->
                        </tbody>
                    </table>
                    <div class="p-3 text-muted text-sm" id="no-ingredients-text" style="display: none;">
                        <i class="fas fa-info-circle mr-1"></i> Belum ada bahan baku ditambahkan.
                    </div>
                </div>
                <div class="card-footer text-right">
                    <a href="{{ route('products.index') }}" class="btn btn-default mr-2">Cancel</a>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Update Product & BOM</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    const materialsData = @json($materials);
    const existingRecipe = @json($product->recipeItems);
    let rowIdx = 0;

    function addIngredientRow(selectedMatId = '', qty = '') {
        $('#no-ingredients-text').hide();
        
        let options = '<option value="">-- Select Material --</option>';
        materialsData.forEach(mat => {
            let selected = (mat.id == selectedMatId) ? 'selected' : '';
            options += `<option value="${mat.id}" data-unit="${mat.recipe_unit}" ${selected}>${mat.name} (Stok: ${mat.stock} ${mat.recipe_unit})</option>`;
        });

        let rowHtml = `
            <tr id="row-${rowIdx}">
                <td>
                    <select name="materials[${rowIdx}][id]" class="form-control select-material" required>
                        ${options}
                    </select>
                </td>
                <td>
                    <input type="number" step="any" min="0.01" name="materials[${rowIdx}][qty]" class="form-control" value="${qty}" placeholder="Takaran" required>
                </td>
                <td class="align-middle unit-label text-muted font-weight-bold">-</td>
                <td class="text-center align-middle">
                    <button type="button" class="btn btn-danger btn-sm btn-remove-row"><i class="fas fa-times"></i></button>
                </td>
            </tr>
        `;
        $('#table-ingredients tbody').append(rowHtml);
        
        if (selectedMatId) {
            $(`#row-${rowIdx} .select-material`).trigger('change');
        }

        rowIdx++;
    }

    $(document).on('change', '.select-material', function() {
        let unit = $(this).find(':selected').data('unit') || '-';
        $(this).closest('tr').find('.unit-label').text(unit);
    });

    $(document).on('click', '.btn-remove-row', function() {
        $(this).closest('tr').remove();
        if ($('#table-ingredients tbody tr').length === 0) {
            $('#no-ingredients-text').show();
        }
    });

    $('#btn-add-ingredient').click(function() {
        addIngredientRow();
    });

    $(document).ready(function() {
        if (existingRecipe && existingRecipe.length > 0) {
            existingRecipe.forEach(item => {
                addIngredientRow(item.material_id, item.quantity);
            });
        } else {
            $('#no-ingredients-text').show();
        }
    });
</script>
@endpush
