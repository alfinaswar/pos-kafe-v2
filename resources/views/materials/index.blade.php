@extends('layouts.app')

@section('title', 'Raw Materials (Bahan Baku)')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item active">Materials</li>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/sweetalert2/sweetalert2.min.css') }}">
@endpush

@section('content')
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-boxes mr-1"></i> Inventory Materials & Conversion</h3>
        <div class="card-tools">
            @can('material-create')
                <a href="{{ route('materials.create') }}" class="btn btn-success btn-sm">
                    <i class="fas fa-plus mr-1"></i> Add Material
                </a>
            @endcan
        </div>
    </div>
    <div class="card-body">
        <table id="materials-table" class="table table-bordered table-striped table-hover dt-responsive nowrap" style="width:100%">
            <thead>
                <tr>
                    <th style="width: 5%">No</th>
                    <th>Material Name</th>
                    <th>Category</th>
                    <th>Current Stock (Recipe Unit)</th>
                    <th>Multi-Unit Ratio</th>
                    <th>ROP (Min Stock)</th>
                    <th style="width: 100px;" class="text-center">Action</th>
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
    <script src="{{ asset('assets/plugins/sweetalert2/sweetalert2.all.min.js') }}"></script>

    <script>
        $(function () {
            var table = $('#materials-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('materials.index') }}",
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                    {data: 'name', name: 'name'},
                    {data: 'category', name: 'category', defaultContent: '-'},
                    {data: 'stock_display', name: 'stock'},
                    {data: 'unit_info', name: 'unit_info', orderable: false, searchable: false},
                    {data: 'rop_display', name: 'reorder_point'},
                    {data: 'action', name: 'action', orderable: false, searchable: false}
                ]
            });

            $(document).on('click', '.btn-delete', function (e) {
                e.preventDefault();
                var url = $(this).data('url');

                Swal.fire({
                    title: 'Delete material?',
                    text: "Make sure no recipes or purchases are using this material!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete!'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: url,
                            type: 'DELETE',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function (response) {
                                table.ajax.reload(null, false);
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: response.message || 'Material deleted.',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            },
                            error: function (xhr) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to delete material.'
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush
