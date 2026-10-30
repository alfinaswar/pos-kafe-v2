@extends('layouts.app')

@section('title', 'Edit Supplier')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('suppliers.index') }}">Suppliers</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title">Edit Supplier: <strong>{{ $supplier->name }}</strong></h3>
                <div class="card-tools">
                    <a class="btn btn-secondary btn-sm" href="{{ route('suppliers.index') }}">
                        <i class="fas fa-arrow-left mr-1"></i> Back
                    </a>
                </div>
            </div>

            <form action="{{ route('suppliers.update', $supplier->id) }}" method="POST">
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

                    <div class="form-group">
                        <label for="name">Supplier Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" id="name" value="{{ old('name', $supplier->name) }}" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" id="phone" value="{{ old('phone', $supplier->phone) }}">
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea name="address" class="form-control @error('address') is-invalid @enderror" id="address" rows="3">{{ old('address', $supplier->address) }}</textarea>
                    </div>
                </div>

                <div class="card-footer text-right">
                    <a href="{{ route('suppliers.index') }}" class="btn btn-default mr-2">Cancel</a>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Update Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
