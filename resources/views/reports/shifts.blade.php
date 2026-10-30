@extends('layouts.app')

@section('title', 'Laporan Rekonsiliasi Shift Kasir')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('shifts.index') }}">Shifts</a></li>
    <li class="breadcrumb-item active">Laporan Shift</li>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
@endpush

@section('content')
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title font-weight-bold"><i class="fas fa-file-invoice-dollar mr-1"></i> Rekapitulasi Kas & Rekonsiliasi Selisih Shift Kasir</h3>
    </div>
    <div class="card-body">
        <div class="row mb-3 bg-light p-3 rounded">
            <div class="col-md-3">
                <label>Dari Tanggal:</label>
                <input type="date" id="start_date" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label>Sampai Tanggal:</label>
                <input type="date" id="end_date" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label>Filter Cabang Outlet:</label>
                <select id="outlet_id" class="form-control form-control-sm">
                    <option value="">-- Semua Cabang --</option>
                    @foreach($outlets as $out)
                        <option value="{{ $out->id }}">{{ $out->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button id="btn-filter" class="btn btn-primary btn-sm mr-2"><i class="fas fa-filter mr-1"></i> Filter</button>
                <button id="btn-reset" class="btn btn-secondary btn-sm"><i class="fas fa-sync mr-1"></i> Reset</button>
            </div>
        </div>

        <table id="shifts-report-table" class="table table-bordered table-striped table-hover dt-responsive nowrap" style="width:100%">
            <thead>
                <tr>
                    <th style="width: 5%">No</th>
                    <th>Waktu Buka</th>
                    <th>Kasir</th>
                    <th>Outlet</th>
                    <th>Modal Awal</th>
                    <th>Penjualan Tunai</th>
                    <th>Non-Tunai</th>
                    <th>Ekspektasi Kas</th>
                    <th>Kas Aktual</th>
                    <th>Selisih (Variance)</th>
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
            var table = $('#shifts-report-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('reports.shifts') }}",
                    data: function(d) {
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                        d.outlet_id = $('#outlet_id').val();
                    }
                },
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                    {data: 'date', name: 'created_at'},
                    {data: 'cashier', name: 'user.name'},
                    {data: 'outlet', name: 'outlet.name'},
                    {data: 'start_cash_fmt', name: 'start_cash'},
                    {data: 'sales_cash_fmt', name: 'total_sales_cash'},
                    {data: 'sales_non_cash_fmt', name: 'total_sales_non_cash'},
                    {data: 'expected_fmt', name: 'expected_cash'},
                    {data: 'actual_fmt', name: 'actual_cash'},
                    {data: 'variance_badge', name: 'variance'}
                ],
                order: [[1, 'desc']]
            });

            $('#btn-filter').click(function() {
                table.draw();
            });

            $('#btn-reset').click(function() {
                $('#start_date').val('');
                $('#end_date').val('');
                $('#outlet_id').val('');
                table.draw();
            });
        });
    </script>
@endpush
