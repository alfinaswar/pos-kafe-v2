@extends('layouts.app')

@section('title', 'Detail Rekonsiliasi Shift #' . $shift->id)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('shifts.index') }}">Shifts</a></li>
    <li class="breadcrumb-item active">Shift #{{ $shift->id }}</li>
@endsection

@section('content')
<div class="row mb-3 d-print-none">
    <div class="col-12 d-flex justify-content-between">
        <a href="{{ route('shifts.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Kembali ke Riwayat Shift
        </a>
        <div>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print mr-1"></i> Cetak Rekap Shift
            </button>
            @if($shift->status === 'open' && ($shift->user_id === auth()->id() || auth()->user()->hasRole('Owner')))
                <a href="{{ route('shifts.close.form', $shift->id) }}" class="btn btn-warning">
                    <i class="fas fa-lock mr-1"></i> Tutup Shift Ini
                </a>
            @endif
        </div>
    </div>
</div>

<div class="card card-outline card-info">
    <div class="card-header">
        <h3 class="card-title font-weight-bold">
            <i class="fas fa-file-invoice mr-1"></i> Rekonsiliasi Keuangan Shift #{{ $shift->id }}
        </h3>
        <div class="card-tools">
            @if($shift->status === 'open')
                <span class="badge badge-success p-2"><i class="fas fa-unlock mr-1"></i> SHIFT AKTIF (OPEN)</span>
            @else
                <span class="badge badge-secondary p-2"><i class="fas fa-lock mr-1"></i> SHIFT DITUTUP (CLOSED)</span>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <th style="width: 35%" class="text-muted">Kasir:</th>
                        <td><strong>{{ $shift->user->name }}</strong></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Cabang Outlet:</th>
                        <td>{{ $shift->outlet->name ?? 'Semua Outlet' }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted">Waktu Buka Shift:</th>
                        <td>{{ $shift->created_at->format('d M Y, H:i') }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted">Waktu Tutup Shift:</th>
                        <td>{{ $shift->closed_at ? $shift->closed_at->format('d M Y, H:i') : '-' }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted">Catatan:</th>
                        <td>{{ $shift->notes ?? '-' }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <div class="card bg-light">
                    <div class="card-header font-weight-bold bg-dark text-white">Ringkasan Kas</div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-striped mb-0">
                            <tr>
                                <td>Modal Awal</td>
                                <td class="text-right font-weight-bold text-primary">Rp {{ number_format($shift->start_cash, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>+ Penjualan Kasir (Tunai)</td>
                                <td class="text-right font-weight-bold text-success">+ Rp {{ number_format($shift->total_sales_cash, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>+ Kas Masuk Operasional (Petty Cash)</td>
                                <td class="text-right font-weight-bold text-info">+ Rp {{ number_format($pettyIn, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>- Kas Keluar Operasional (Petty Cash)</td>
                                <td class="text-right font-weight-bold text-danger">- Rp {{ number_format($pettyOut, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="bg-secondary text-white font-weight-bold">
                                <td>= Ekspektasi Kas Fisik</td>
                                <td class="text-right font-weight-bold text-warning">Rp {{ number_format($expectedCash, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>Kas Aktual Dihitung</td>
                                <td class="text-right font-weight-bold">
                                    {{ $shift->actual_cash !== null ? 'Rp ' . number_format($shift->actual_cash, 0, ',', '.') : '-' }}
                                </td>
                            </tr>
                            <tr class="font-weight-bold">
                                <td>Selisih Kas (Variance)</td>
                                <td class="text-right">
                                    @if($shift->status === 'open')
                                        <span class="text-muted">-</span>
                                    @elseif($shift->variance == 0)
                                        <span class="text-success">Cocok (Rp 0)</span>
                                    @elseif($shift->variance > 0)
                                        <span class="text-warning">Lebih (+ Rp {{ number_format($shift->variance, 0, ',', '.') }})</span>
                                    @else
                                        <span class="text-danger">Kurang (- Rp {{ number_format(abs($shift->variance), 0, ',', '.') }})</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td>Penjualan Non-Tunai (QRIS / Transfer)</td>
                                <td class="text-right font-weight-bold text-secondary">
                                    Rp {{ number_format($shift->total_sales_non_cash, 0, ',', '.') }}
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6">
                <h5 class="font-weight-bold"><i class="fas fa-wallet mr-1 text-info"></i> Kas Operasional (Petty Cash)</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm table-striped">
                        <thead class="bg-secondary text-white">
                            <tr>
                                <th>Waktu</th>
                                <th>Tipe</th>
                                <th>Keterangan</th>
                                <th class="text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($shift->pettyCashes as $petty)
                                <tr>
                                    <td>{{ $petty->created_at->format('H:i') }}</td>
                                    <td>
                                        @if($petty->type === 'in')
                                            <span class="badge badge-success">Masuk</span>
                                        @else
                                            <span class="badge badge-danger">Keluar</span>
                                        @endif
                                    </td>
                                    <td>{{ $petty->description }}</td>
                                    <td class="text-right font-weight-bold">
                                        Rp {{ number_format($petty->amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Tidak ada catatan petty cash.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-md-6">
                <h5 class="font-weight-bold"><i class="fas fa-receipt mr-1 text-success"></i> Transaksi Penjualan Shift Ini</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm table-striped">
                        <thead class="bg-secondary text-white">
                            <tr>
                                <th>No. Invoice</th>
                                <th>Metode</th>
                                <th>Menu Terjual</th>
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($shift->sales as $sale)
                                <tr>
                                    <td>
                                        <a href="{{ route('pos.receipt', $sale->id) }}" target="_blank">
                                            {{ $sale->invoice_number }}
                                        </a>
                                    </td>
                                    <td><span class="badge badge-light border">{{ strtoupper($sale->payment_method) }}</span></td>
                                    <td>
                                        @foreach($sale->items as $item)
                                            <small class="d-block">{{ $item->product->name }} x{{ $item->quantity }}</small>
                                        @endforeach
                                    </td>
                                    <td class="text-right font-weight-bold">
                                        Rp {{ number_format($sale->total_price, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Belum ada transaksi penjualan pada shift ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
