@extends('layouts.app')

@section('title', 'Dashboard')

@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
<!-- Metric Cards -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>Rp {{ number_format($todaySales, 0, ',', '.') }}</h3>
                <p>Omzet Penjualan Hari Ini</p>
            </div>
            <div class="icon">
                <i class="fas fa-shopping-bag"></i>
            </div>
            <a href="{{ route('reports.sales') }}" class="small-box-footer">Lihat Rekap Penjualan <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $todayOrders }}</h3>
                <p>Transaksi Kasir Hari Ini</p>
            </div>
            <div class="icon">
                <i class="fas fa-receipt"></i>
            </div>
            <a href="{{ route('pos.index') }}" class="small-box-footer">Buka Kasir POS <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $lowStockMaterials->count() }}</h3>
                <p>Bahan Kritis (Stok &le; ROP)</p>
            </div>
            <div class="icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <a href="{{ route('materials.index') }}" class="small-box-footer">Cek Stok Bahan <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $pendingPurchases->count() }}</h3>
                <p>Pending PO (Perlu Approval)</p>
            </div>
            <div class="icon">
                <i class="fas fa-truck-loading"></i>
            </div>
            <a href="{{ route('purchases.index') }}" class="small-box-footer">Review Pengadaan <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<!-- Section 2: Expiry & Shift Alert Status -->
<div class="row">
    <!-- Expiry Batch Alert -->
    <div class="col-md-6">
        <div class="card card-outline {{ ($expiredBatches->count() > 0 || $nearExpiryBatches->count() > 0) ? 'card-danger' : 'card-success' }}">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-calendar-times mr-1 text-danger"></i> Peringatan Kadaluarsa Batch (FIFO)
                </h3>
                <div class="card-tools">
                    <a href="{{ route('batches.index') }}" class="btn btn-tool text-primary">Lihat Semua Batch</a>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Bahan Baku</th>
                            <th>No. Batch</th>
                            <th>Sisa Stok</th>
                            <th>Tgl Expired</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($expiredBatches as $b)
                            <tr class="table-danger">
                                <td><strong>{{ $b->material->name }}</strong></td>
                                <td>{{ $b->batch_number ?? '#' . $b->id }}</td>
                                <td>{{ number_format($b->remaining_qty, 0) }} {{ $b->material->recipe_unit }}</td>
                                <td>{{ $b->expired_date ? $b->expired_date->format('d/m/Y') : '-' }}</td>
                                <td><span class="badge badge-danger">EXPIRED</span></td>
                            </tr>
                        @endforeach
                        @foreach($nearExpiryBatches as $b)
                            <tr class="table-warning">
                                <td><strong>{{ $b->material->name }}</strong></td>
                                <td>{{ $b->batch_number ?? '#' . $b->id }}</td>
                                <td>{{ number_format($b->remaining_qty, 0) }} {{ $b->material->recipe_unit }}</td>
                                <td>{{ $b->expired_date ? $b->expired_date->format('d/m/Y') : '-' }}</td>
                                <td><span class="badge badge-warning">&le; 7 Hari</span></td>
                            </tr>
                        @endforeach
                        @if($expiredBatches->isEmpty() && $nearExpiryBatches->isEmpty())
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">
                                    <i class="fas fa-check-circle text-success mr-1"></i> Tidak ada batch yang kadaluarsa atau mendekati kadaluarsa.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Active Cashier Shifts -->
    <div class="col-md-6">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-user-clock mr-1 text-info"></i> Shift Kasir Sedang Aktif
                </h3>
                <div class="card-tools">
                    <a href="{{ route('shifts.index') }}" class="btn btn-tool text-primary">Semua Shift</a>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Kasir</th>
                            <th>Outlet</th>
                            <th>Modal Awal</th>
                            <th>Penjualan Tunai</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activeShifts as $sh)
                            <tr>
                                <td><strong>{{ $sh->user->name }}</strong></td>
                                <td>{{ $sh->outlet->name ?? 'Pusat' }}</td>
                                <td>Rp {{ number_format($sh->start_cash, 0, ',', '.') }}</td>
                                <td class="text-success font-weight-bold">Rp {{ number_format($sh->total_sales_cash, 0, ',', '.') }}</td>
                                <td>
                                    <a href="{{ route('shifts.show', $sh->id) }}" class="btn btn-info btn-xs">
                                        <i class="fas fa-eye"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">
                                    Belum ada shift kasir yang aktif saat ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Section 3: ROP Warning & Pending PO -->
<div class="row">
    <!-- ROP Warning List -->
    <div class="col-md-6">
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title font-weight-bold text-danger">
                    <i class="fas fa-bell mr-1"></i> Peringatan Stok Kritis (Reorder Point / ROP)
                </h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Bahan Baku</th>
                            <th>Sisa Stok</th>
                            <th>Batas ROP</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lowStockMaterials as $mat)
                            <tr>
                                <td><strong>{{ $mat->name }}</strong></td>
                                <td class="text-danger font-weight-bold">{{ number_format($mat->stock, 2) }} {{ $mat->recipe_unit }}</td>
                                <td>{{ number_format($mat->reorder_point, 2) }} {{ $mat->recipe_unit }}</td>
                                <td><span class="badge badge-danger">PERLU RESTOCK</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">Semua bahan baku dalam kondisi aman di atas ROP.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pending POs -->
    <div class="col-md-6">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-file-invoice-dollar mr-1"></i> Pengadaan Bahan Menunggu Approval
                </h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-sm mb-0">
                    <thead>
                        <tr>
                            <th>No. PO</th>
                            <th>Supplier</th>
                            <th>Total Biaya</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pendingPurchases as $po)
                            <tr>
                                <td><strong>{{ $po->invoice_number }}</strong></td>
                                <td>{{ $po->supplier->name }}</td>
                                <td>Rp {{ number_format($po->total_cost, 0, ',', '.') }}</td>
                                <td>
                                    <a href="{{ route('purchases.show', $po->id) }}" class="btn btn-primary btn-xs">
                                        <i class="fas fa-check-circle"></i> Review
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">Tidak ada PO yang menunggu approval saat ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
