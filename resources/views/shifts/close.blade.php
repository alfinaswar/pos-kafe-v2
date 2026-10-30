@extends('layouts.app')

@section('title', 'Tutup Shift Kasir (Rekonsiliasi Kas)')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('shifts.index') }}">Shifts</a></li>
    <li class="breadcrumb-item active">Tutup Shift #{{ $shift->id }}</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card card-outline card-warning shadow-sm">
            <div class="card-header bg-warning">
                <h3 class="card-title font-weight-bold text-dark">
                    <i class="fas fa-calculator mr-1"></i> Rekonsiliasi & Tutup Shift Kasir #{{ $shift->id }}
                </h3>
            </div>
            <form action="{{ route('shifts.close', $shift->id) }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th class="text-muted">Kasir:</th>
                                    <td><strong>{{ $shift->user->name }}</strong></td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Outlet:</th>
                                    <td>{{ $shift->outlet->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Waktu Buka:</th>
                                    <td>{{ $shift->created_at->format('d M Y, H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-bordered bg-light">
                                <tr>
                                    <th>Modal Awal</th>
                                    <td class="text-right font-weight-bold text-primary">Rp {{ number_format($shift->start_cash, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <th>+ Penjualan Tunai</th>
                                    <td class="text-right font-weight-bold text-success">+ Rp {{ number_format($shift->total_sales_cash, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <th>+ Kas Masuk (Petty Cash)</th>
                                    <td class="text-right font-weight-bold text-info">+ Rp {{ number_format($pettyIn, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <th>- Kas Keluar (Petty Cash)</th>
                                    <td class="text-right font-weight-bold text-danger">- Rp {{ number_format($pettyOut, 0, ',', '.') }}</td>
                                </tr>
                                <tr class="bg-secondary text-white font-weight-bold">
                                    <th>= Ekspektasi Kas Fisik</th>
                                    <td class="text-right font-weight-bold text-warning" style="font-size: 1.1rem;">
                                        Rp {{ number_format($expectedCash, 0, ',', '.') }}
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <hr>

                    <div class="form-group mt-3">
                        <label for="actual_cash" class="font-weight-bold text-lg">
                            <i class="fas fa-money-bill-wave text-success mr-1"></i> Uang Tunai Fisik di Laci Kasir (Hitung Aktual) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text font-weight-bold">Rp</span>
                            </div>
                            <input type="number" step="100" min="0" name="actual_cash" id="actual_cash" class="form-control form-control-lg font-weight-bold text-dark" placeholder="Masukkan total uang hasil hitung fisik..." required autofocus>
                        </div>
                        <small class="text-muted">Hitung seluruh lembar dan koin fisik yang ada di laci kasir saat ini.</small>
                    </div>

                    <div id="variance-box" class="alert alert-secondary mt-3 d-none">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="font-weight-bold">Status Selisih (Variance):</span>
                            <span id="variance-text" class="font-weight-bold font-size-lg">-</span>
                        </div>
                    </div>

                    <div class="form-group mt-3">
                        <label for="notes">Catatan Tutup Shift / Keterangan Selisih</label>
                        <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="Tulis alasan jika ada selisih uang, catatan serah terima kasir pengganti, dll."></textarea>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ route('pos.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Batal & Kembali ke POS
                    </a>
                    <button type="submit" class="btn btn-warning btn-lg font-weight-bold text-dark" onclick="return confirm('Apakah Anda yakin ingin menutup shift ini? Setelah ditutup, shift tidak dapat dibuka kembali.')">
                        <i class="fas fa-lock mr-1"></i> Konfirmasi & Tutup Shift
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const expected = {{ $expectedCash }};
    const inputActual = document.getElementById('actual_cash');
    const varianceBox = document.getElementById('variance-box');
    const varianceText = document.getElementById('variance-text');

    inputActual.addEventListener('input', function() {
        const val = parseFloat(this.value);
        if (isNaN(val)) {
            varianceBox.classList.add('d-none');
            return;
        }

        varianceBox.classList.remove('d-none');
        const diff = val - expected;
        const fmt = new Intl.NumberFormat('id-ID').format(Math.abs(diff));

        if (diff === 0) {
            varianceBox.className = 'alert alert-success mt-3';
            varianceText.innerHTML = '<i class="fas fa-check-circle mr-1"></i> Tepat / Seimbang (Rp 0)';
        } else if (diff > 0) {
            varianceBox.className = 'alert alert-warning mt-3';
            varianceText.innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i> Selisih Lebih: + Rp ' + fmt;
        } else {
            varianceBox.className = 'alert alert-danger mt-3';
            varianceText.innerHTML = '<i class="fas fa-times-circle mr-1"></i> Selisih Kurang: - Rp ' + fmt;
        }
    });
</script>
@endpush
