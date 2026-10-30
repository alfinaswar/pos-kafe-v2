@extends('layouts.app')

@section('title', 'Laporan Mutasi Stok (Inventory Logs)')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item active">Inventory Logs</li>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
@endpush

@section('content')
<div class="card card-outline card-info">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold"><i class="fas fa-history mr-1"></i> Riwayat Mutasi Bahan Baku & Batch (FIFO Logs)</h3>
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
        <div class="row mb-3 bg-light p-3 rounded">
            <div class="col-md-4">
                <label>Filter Bahan Baku:</label>
                <select id="filter_material" class="form-control form-control-sm">
                    <option value="">-- Semua Bahan Baku --</option>
                    @foreach($materials as $mat)
                        <option value="{{ $mat->id }}">{{ $mat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label>Tipe Mutasi:</label>
                <select id="filter_type" class="form-control form-control-sm">
                    <option value="">-- Semua Tipe --</option>
                    <option value="in">Masuk (IN)</option>
                    <option value="out">Keluar (OUT)</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="button" id="btn-filter" class="btn btn-primary btn-sm mr-2"><i class="fas fa-filter mr-1"></i> Filter</button>
                <button type="button" id="btn-reset" class="btn btn-secondary btn-sm">Reset</button>
            </div>
        </div>

        <table id="inventory-report-table" class="table table-bordered table-striped table-hover dt-responsive nowrap" style="width:100%">
            <thead>
                <tr>
                    <th style="width: 5%">No</th>
                    <th>Waktu</th>
                    <th>Bahan Baku</th>
                    <th>No. Batch</th>
                    <th>Tipe</th>
                    <th>Jumlah Mutasi</th>
                    <th>Sisa Stok Akhir</th>
                    <th>Referensi / Keterangan</th>
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
            var table = $('#inventory-report-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('reports.inventory') }}",
                    data: function(d) {
                        d.material_id = $('#filter_material').val();
                        d.type = $('#filter_type').val();
                    }
                },
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                    {data: 'date', name: 'created_at'},
                    {data: 'material_name', name: 'material.name'},
                    {data: 'batch_info', name: 'batch.batch_number'},
                    {data: 'type_badge', name: 'type'},
                    {data: 'quantity_display', name: 'quantity'},
                    {data: 'stock_after_display', name: 'stock_after'},
                    {data: 'reference', name: 'reference'}
                ],
                order: [[1, 'desc']]
            });

            $('#btn-filter').click(function() {
                table.draw();
            });

            $('#btn-reset').click(function() {
                $('#filter_material').val('');
                $('#filter_type').val('');
                table.draw();
            });

            $('#btn-export-csv').click(function(e) {
                e.preventDefault();
                let m = $('#filter_material').val() || '';
                window.location.href = "{{ route('reports.inventory.export') }}?material_id=" + m;
            });
        });
    </script>
@endpush
