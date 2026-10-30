@extends('layouts.app')

@section('title', 'Manajemen Shift & Rekonsiliasi Kasir')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item active">Cashier Shifts</li>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
@endpush

@section('content')
<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            @php
                $activeShift = \App\Models\CashierShift::where('user_id', auth()->id())->where('status', 'open')->first();
            @endphp
            @if($activeShift)
                <span class="badge badge-success p-2">
                    <i class="fas fa-check-circle mr-1"></i> Shift Anda Aktif (#{{ $activeShift->id }}) - Modal: Rp {{ number_format($activeShift->start_cash, 0, ',', '.') }}
                </span>
                <a href="{{ route('shifts.close.form', $activeShift->id) }}" class="btn btn-warning btn-sm ml-2">
                    <i class="fas fa-cash-register mr-1"></i> Tutup Shift Sekarang
                </a>
            @else
                <a href="{{ route('shifts.create') }}" class="btn btn-success">
                    <i class="fas fa-unlock mr-1"></i> Buka Shift Baru
                </a>
            @endif
        </div>
    </div>
</div>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-history mr-1"></i> Riwayat Shift & Rekonsiliasi Kasir</h3>
    </div>
    <div class="card-body">
        <table id="shifts-table" class="table table-bordered table-striped table-hover dt-responsive nowrap" style="width:100%">
            <thead>
                <tr>
                    <th style="width: 5%">No</th>
                    <th>Kasir</th>
                    <th>Outlet</th>
                    <th>Modal Awal</th>
                    <th>Penjualan Tunai</th>
                    <th>Ekspektasi Kas</th>
                    <th>Kas Aktual</th>
                    <th>Selisih (Variance)</th>
                    <th>Status</th>
                    <th style="width: 100px;" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>

    <script>
        $(function () {
            $('#shifts-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('shifts.index') }}",
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                    {data: 'cashier_name', name: 'user.name'},
                    {data: 'outlet_name', name: 'outlet.name'},
                    {data: 'start_cash_fmt', name: 'start_cash'},
                    {data: 'total_sales_cash_fmt', name: 'total_sales_cash'},
                    {data: 'expected_cash_fmt', name: 'expected_cash'},
                    {data: 'actual_cash_fmt', name: 'actual_cash'},
                    {data: 'variance_fmt', name: 'variance'},
                    {data: 'status_badge', name: 'status'},
                    {data: 'action', name: 'action', orderable: false, searchable: false}
                ],
                order: [[0, 'desc']]
            });
        });
    </script>
@endpush
