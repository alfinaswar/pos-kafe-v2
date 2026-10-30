@extends('layouts.app')

@section('title', 'Manajemen Batch Bahan Baku (FIFO)')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item active">Material Batches</li>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
@endpush

@section('content')
<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="m-0 font-weight-bold text-dark">
                <i class="fas fa-layer-group text-primary mr-2"></i> Pelacakan Stok FIFO Berbasis Batch Kedatangan
            </h4>
        </div>
        <div>
            @can('stock-opname')
                <a href="{{ route('batches.opname') }}" class="btn btn-warning">
                    <i class="fas fa-clipboard-check mr-1"></i> Stock Opname & Waste Log
                </a>
            @endcan
        </div>
    </div>
</div>

<div class="card card-outline card-primary">
    <div class="card-header">
        <div class="row">
            <div class="col-md-4">
                <label>Filter Bahan Baku:</label>
                <select id="filter_material" class="form-control form-control-sm">
                    <option value="">-- Semua Bahan Baku --</option>
                    @foreach($materials as $mat)
                        <option value="{{ $mat->id }}">{{ $mat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label>Filter Status Batch:</label>
                <select id="filter_status" class="form-control form-control-sm">
                    <option value="">-- Semua Status --</option>
                    <option value="active" selected>Aktif (Tersedia)</option>
                    <option value="near_expiry">Mendekati Kadaluarsa (&le; 7 hari)</option>
                    <option value="expired">Sudah Kadaluarsa</option>
                    <option value="depleted">Habis</option>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button id="btn-filter" class="btn btn-primary btn-sm mr-2"><i class="fas fa-filter mr-1"></i> Terapkan</button>
                <button id="btn-reset" class="btn btn-secondary btn-sm"><i class="fas fa-sync mr-1"></i> Reset</button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <table id="batches-table" class="table table-bordered table-striped table-hover dt-responsive nowrap" style="width:100%">
            <thead>
                <tr>
                    <th style="width: 5%">No</th>
                    <th>No. Batch</th>
                    <th>Nama Bahan Baku</th>
                    <th>Qty Awal</th>
                    <th>Sisa Stok (FIFO)</th>
                    <th>Harga Satuan Beli</th>
                    <th>Tanggal Kadaluarsa</th>
                    <th>Status</th>
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
            var table = $('#batches-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('batches.index') }}",
                    data: function(d) {
                        d.material_id = $('#filter_material').val();
                        d.status = $('#filter_status').val();
                    }
                },
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                    {data: 'batch_code', name: 'batch_number'},
                    {data: 'material_name', name: 'material.name'},
                    {data: 'initial_qty_fmt', name: 'initial_qty'},
                    {data: 'remaining_qty_fmt', name: 'remaining_qty'},
                    {data: 'purchase_price_fmt', name: 'purchase_price'},
                    {data: 'expired_date_fmt', name: 'expired_date'},
                    {data: 'status_badge', name: 'status_badge', orderable: false, searchable: false}
                ],
                order: [[1, 'desc']]
            });

            $('#btn-filter').click(function() {
                table.draw();
            });

            $('#btn-reset').click(function() {
                $('#filter_material').val('');
                $('#filter_status').val('');
                table.draw();
            });
        });
    </script>
@endpush
