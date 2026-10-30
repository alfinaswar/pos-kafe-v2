@extends('layouts.app')

@section('title', 'Buka Shift Kasir')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('shifts.index') }}">Shifts</a></li>
    <li class="breadcrumb-item active">Buka Shift</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card card-outline card-success shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-cash-register mr-1 text-success"></i> Form Buka Shift Kasir
                </h3>
            </div>
            <form action="{{ route('shifts.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-1"></i>
                        Silakan hitung dan masukkan <strong>Modal Awal (Cash Drawer)</strong> sebelum memulai transaksi kasir.
                    </div>

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
                        <label>Nama Kasir</label>
                        <input type="text" class="form-control" value="{{ auth()->user()->name }}" readonly>
                    </div>

                    <div class="form-group">
                        <label for="outlet_id">Cabang Outlet</label>
                        <select name="outlet_id" id="outlet_id" class="form-control">
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" {{ (auth()->user()->outlet_id == $outlet->id) ? 'selected' : '' }}>
                                    {{ $outlet->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="start_cash">Modal Awal Kasir (Uang Kembalian Fisik) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text font-weight-bold">Rp</span>
                            </div>
                            <input type="number" step="1000" min="0" name="start_cash" id="start_cash" class="form-control form-control-lg font-weight-bold text-success" placeholder="Contoh: 200000" value="{{ old('start_cash', 200000) }}" required autofocus>
                        </div>
                        <small class="text-muted">Masukkan nominal uang tunai yang ada di laci kasir saat ini.</small>
                    </div>

                    <div class="form-group">
                        <label for="notes">Catatan Shift (Opsional)</label>
                        <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="Catatan shift pagi / siang / serah terima...">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ route('home') }}" class="btn btn-secondary">
                        <i class="fas fa-times mr-1"></i> Kembali ke Dashboard
                    </a>
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-check-circle mr-1"></i> Buka Shift & Masuk POS
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
