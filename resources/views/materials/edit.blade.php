@extends('layouts.app')

@section('title', 'Edit Material')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('materials.index') }}">Materials</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-9">
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title">Edit Material: <strong>{{ $material->name }}</strong></h3>
                <div class="card-tools">
                    <a class="btn btn-secondary btn-sm" href="{{ route('materials.index') }}">
                        <i class="fas fa-arrow-left mr-1"></i> Back
                    </a>
                </div>
            </div>

            <form action="{{ route('materials.update', $material->id) }}" method="POST">
                @csrf
                @method('PUT')
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
                            <input type="text" name="name" class="form-control" id="name" value="{{ old('name', $material->name) }}" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="category">Category</label>
                            <input type="text" name="category" class="form-control" id="category" value="{{ old('category', $material->category) }}">
                        </div>
                    </div>

                    <div class="callout callout-info mt-3">
                        <h5><i class="fas fa-calculator mr-1"></i> Multi-Unit Conversion Engine</h5>
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label for="purchase_unit">Purchase Unit (Beli) <span class="text-danger">*</span></label>
                                <input type="text" name="purchase_unit" class="form-control" id="purchase_unit" value="{{ old('purchase_unit', $material->purchase_unit) }}" required>
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="recipe_unit">Recipe Unit (Pakai) <span class="text-danger">*</span></label>
                                <input type="text" name="recipe_unit" class="form-control" id="recipe_unit" value="{{ old('recipe_unit', $material->recipe_unit) }}" required>
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="conversion_factor">Conversion Factor <span class="text-danger">*</span></label>
                                <input type="number" step="any" name="conversion_factor" class="form-control" id="conversion_factor" value="{{ old('conversion_factor', $material->conversion_factor) }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6 form-group">
                            <label>Current Stock</label>
                            <input type="text" class="form-control" value="{{ number_format($material->stock, 2) }} {{ $material->recipe_unit }}" disabled>
                            <small class="text-muted">Stok disesuaikan via Goods Receipt atau Penjualan POS</small>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="reorder_point">Reorder Point (ROP) <span class="text-danger">*</span></label>
                            <input type="number" step="any" name="reorder_point" class="form-control" id="reorder_point" value="{{ old('reorder_point', $material->reorder_point) }}" required>
                        </div>
                    </div>
                </div>

                <div class="card-footer text-right">
                    <a href="{{ route('materials.index') }}" class="btn btn-default mr-2">Cancel</a>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Update Material</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
