@extends('layouts.app')

@section('title', 'Create Material')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('materials.index') }}">Materials</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-9">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Add Raw Material & Conversion</h3>
                <div class="card-tools">
                    <a class="btn btn-secondary btn-sm" href="{{ route('materials.index') }}">
                        <i class="fas fa-arrow-left mr-1"></i> Back
                    </a>
                </div>
            </div>

            <form action="{{ route('materials.store') }}" method="POST">
                @csrf
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

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="name">Material Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" id="name" value="{{ old('name') }}" placeholder="e.g. Susu UHT, Biji Kopi Arabika" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="category">Category</label>
                            <input type="text" name="category" class="form-control" id="category" value="{{ old('category') }}" placeholder="e.g. Dairy, Coffee Beans, Syrup">
                        </div>
                    </div>

                    <div class="callout callout-info mt-3">
                        <h5><i class="fas fa-calculator mr-1"></i> Multi-Unit Conversion Engine</h5>
                        <p class="text-muted text-sm">Contoh: Susu UHT dibeli per <b>Karton</b>, dipakai di resep per <b>ml</b>. 1 Karton = 12.000 ml.</p>
                        
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label for="purchase_unit">Purchase Unit (Beli) <span class="text-danger">*</span></label>
                                <input type="text" name="purchase_unit" class="form-control" id="purchase_unit" value="{{ old('purchase_unit') }}" placeholder="e.g. Karton, Pack, Botol" required>
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="recipe_unit">Recipe Unit (Pakai) <span class="text-danger">*</span></label>
                                <input type="text" name="recipe_unit" class="form-control" id="recipe_unit" value="{{ old('recipe_unit') }}" placeholder="e.g. ml, gram, pcs" required>
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="conversion_factor">Conversion Factor <span class="text-danger">*</span></label>
                                <input type="number" step="any" name="conversion_factor" class="form-control" id="conversion_factor" value="{{ old('conversion_factor', 1) }}" required>
                                <small class="text-muted">1 Unit Beli = berapa Unit Pakai?</small>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6 form-group">
                            <label for="stock">Initial Stock (dalam Recipe Unit)</label>
                            <input type="number" step="any" name="stock" class="form-control" id="stock" value="{{ old('stock', 0) }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="reorder_point">Reorder Point (ROP / Batas Kritis) <span class="text-danger">*</span></label>
                            <input type="number" step="any" name="reorder_point" class="form-control" id="reorder_point" value="{{ old('reorder_point', 0) }}" required>
                            <small class="text-muted">Peringatan aktif jika stok &le; ROP</small>
                        </div>
                    </div>
                </div>

                <div class="card-footer text-right">
                    <a href="{{ route('materials.index') }}" class="btn btn-default mr-2">Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save Material</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
