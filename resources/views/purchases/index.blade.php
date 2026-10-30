@extends('layouts.app')

@section('title', 'Purchase Orders (PO)')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item active">Purchases</li>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
@endpush

@section('content')
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-truck mr-1"></i> Supplier Purchase Orders</h3>
        <div class="card-tools">
            @can('purchase-create')
                <a href="{{ route('purchases.create') }}" class="btn btn-success btn-sm">
                    <i class="fas fa-plus mr-1"></i> Create Purchase Order
                </a>
            @endcan
        </div>
    </div>
    <div class="card-body">
        <table id="purchases-table" class="table table-bordered table-striped table-hover dt-responsive nowrap" style="width:100%">
            <thead>
                <tr>
                    <th style="width: 5%">No</th>
                    <th>Invoice / PO #</th>
                    <th>Supplier</th>
                    <th>Created By</th>
                    <th>Total Cost</th>
                    <th>Status</th>
                    <th style="width: 80px;" class="text-center">Action</th>
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
            $('#purchases-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('purchases.index') }}",
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                    {data: 'invoice_number', name: 'invoice_number'},
                    {data: 'supplier.name', name: 'supplier.name'},
                    {data: 'user.name', name: 'user.name'},
                    {data: 'total_cost_formatted', name: 'total_cost'},
                    {data: 'status_badge', name: 'status'},
                    {data: 'action', name: 'action', orderable: false, searchable: false}
                ]
            });
        });
    </script>
@endpush
