@extends('layouts.app')

@section('title', 'Laporan Penjualan (Sales Report)')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item active">Sales Report</li>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
@endpush

@section('content')
<div class="card card-outline card-primary">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold"><i class="fas fa-chart-line mr-1"></i> Rekap Penjualan POS Kasir</h3>
        <div class="card-tools">
            <a href="#" id="btn-export-csv" class="btn btn-success btn-sm font-weight-bold">
                <i class="fas fa-file-excel mr-1"></i> Export Excel (CSV)
            </a>
            <button onclick="window.print()" class="btn btn-default btn-sm ml-1">
                <i class="fas fa-print mr-1"></i> Cetak
            </button>
        </div>
    </div>
    <div class="card-body">
        <!-- Date & Method Filter -->
        <div class="row mb-3 bg-light p-3 rounded">
            <div class="col-md-3">
                <label>Dari Tanggal:</label>
                <input type="date" id="start_date" class="form-control form-control-sm" value="{{ date('Y-m-01') }}">
            </div>
            <div class="col-md-3">
                <label>Sampai Tanggal:</label>
                <input type="date" id="end_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
            </div>
            <div class="col-md-3">
                <label>Metode Pembayaran:</label>
                <select id="payment_method" class="form-control form-control-sm">
                    <option value="">-- Semua Metode --</option>
                    <option value="cash">Tunai (Cash)</option>
                    <option value="qris">QRIS</option>
                    <option value="transfer">Transfer Bank</option>
                    <option value="debit">Kartu Debit</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="button" id="btn-filter" class="btn btn-primary btn-sm mr-2"><i class="fas fa-filter mr-1"></i> Filter</button>
                <button type="button" id="btn-reset" class="btn btn-secondary btn-sm">Reset</button>
            </div>
        </div>

        <table id="sales-report-table" class="table table-bordered table-striped table-hover dt-responsive nowrap" style="width:100%">
            <thead>
                <tr>
                    <th style="width: 5%">No</th>
                    <th>Invoice #</th>
                    <th>Kasir</th>
                    <th>Outlet</th>
                    <th>Tanggal Transaksi</th>
                    <th>Metode</th>
                    <th>Total Omzet</th>
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
            var table = $('#sales-report-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('reports.sales') }}",
                    data: function(d) {
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                        d.payment_method = $('#payment_method').val();
                    }
                },
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                    {data: 'invoice_number', name: 'invoice_number'},
                    {data: 'cashier', name: 'user.name'},
                    {data: 'outlet', name: 'shift.outlet.name'},
                    {data: 'date', name: 'created_at'},
                    {data: 'method', name: 'payment_method'},
                    {data: 'total_formatted', name: 'total_price', className: 'text-right font-weight-bold text-success'}
                ],
                order: [[4, 'desc']]
            });

            $('#btn-filter').click(function() {
                table.draw();
            });

            $('#btn-reset').click(function() {
                $('#start_date').val('');
                $('#end_date').val('');
                $('#payment_method').val('');
                table.draw();
            });

            $('#btn-export-csv').click(function(e) {
                e.preventDefault();
                let s = $('#start_date').val() || '';
                let eDate = $('#end_date').val() || '';
                window.location.href = "{{ route('reports.sales.export') }}?start_date=" + s + "&end_date=" + eDate;
            });
        });
    </script>
@endpush
