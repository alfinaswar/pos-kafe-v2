@extends('layouts.app')

@section('title', 'Create Product & Recipe')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection

@section('content')
<form action="{{ route('products.store') }}" method="POST">
    @csrf
    <div class="row">
        <!-- Info Produk -->
        <div class="col-md-5">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Product Details</h3>
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
                        <div class="form-check"><input type="checkbox" name="is_bundle" value="1" class="form-check-input" id="is_bundle"><label class="form-check-label" for="is_bundle">Bundle menu</label></div>
                    </div>
                    <div class="form-group">
                        <label>Bundle Components</label>
                        @foreach($products as $product)
                            <div class="input-group mb-1"><div class="input-group-prepend"><div class="input-group-text"><input type="checkbox" name="bundle_items[{{ $product->id }}][selected]" value="1"></div></div><input class="form-control" value="{{ $product->name }}" readonly><input type="number" min="1" name="bundle_items[{{ $product->id }}][quantity]" class="form-control" value="1" style="max-width:100px"></div>
                        @endforeach
                    </div>
                    <div class="form-group">
                        <label for="name">Menu Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" id="name" value="{{ old('name') }}" placeholder="e.g. Es Kopi Susu Kaya Raya" required>
                    </div>

                    <div class="form-group">
                        <label for="category">Category</label>
                        <input type="text" name="category" class="form-control" id="category" value="{{ old('category') }}" placeholder="e.g. Coffee, Non-Coffee, Snack">
                    </div>

                    <div class="form-group">
                        <label for="price">Selling Price (Rp) <span class="text-danger">*</span></label>
                        <input type="number" step="any" name="price" class="form-control" id="price" value="{{ old('price', 0) }}" required>
                    </div>

                    <div class="form-group">
                        <label for="detail">Description / Detail</label>
                        <textarea name="detail" class="form-control" id="detail" rows="3" placeholder="Deskripsi menu...">{{ old('detail') }}</textarea>
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
                            <!-- Dynamic Row -->
                        </tbody>
                    </table>
                    <div class="p-3 text-muted text-sm" id="no-ingredients-text">
                        <i class="fas fa-info-circle mr-1"></i> Belum ada bahan baku ditambahkan. Klik tombol "Add Ingredient" untuk menghubungkan resep pemotongan stok otomatis saat POS terjual.
                    </div>
                </div>
                <div class="card-footer text-right">
                    <a href="{{ route('products.index') }}" class="btn btn-default mr-2">Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save Product & BOM</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    const materialsData = @json($materials);
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
</script>
@endpush
