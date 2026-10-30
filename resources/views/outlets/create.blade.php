@extends('layouts.app')

@section('title', 'Tambah Outlet')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('outlets.index') }}">Outlets</a></li>
    <li class="breadcrumb-item active">Tambah</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plus mr-1"></i> Form Tambah Cabang Outlet</h3>
            </div>
            <form action="{{ route('outlets.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="form-group">
                        <label for="name">Nama Outlet / Cabang <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" id="name" placeholder="Misal: Kedai Kopi Kaya Raya - Cabang Sudirman" value="{{ old('name') }}" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">No. Telepon / WhatsApp</label>
                        <input type="text" name="phone" class="form-control" id="phone" placeholder="Misal: 081234567890" value="{{ old('phone') }}">
                    </div>

                    <div class="form-group">
                        <label for="address">Alamat Lengkap</label>
                        <textarea name="address" class="form-control" id="address" rows="3" placeholder="Alamat cabang...">{{ old('address') }}</textarea>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ route('outlets.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save mr-1"></i> Simpan Outlet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
