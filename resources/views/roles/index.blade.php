@extends('layouts.app')

@section('title', 'Role Management')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item active">Roles</li>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/sweetalert2/sweetalert2.min.css') }}">
@endpush

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">List of Roles</h3>
            <div class="card-tools">
                @can('role-create')
                    <a href="{{ route('roles.create') }}" class="btn btn-success btn-sm">
                        <i class="fas fa-plus-circle"></i> Create New Role
                    </a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <table id="roles-table" class="table table-bordered table-striped table-hover dt-responsive nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th>Name</th>
                        <th>Permissions</th>
                        <th style="width: 120px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
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
            var table = $('#roles-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('roles.index') }}",
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '5%'},
                    {data: 'name', name: 'name'},
                    {data: 'permissions', name: 'permissions', orderable: false, searchable: false},
                    {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center'}
                ]
            });

            $(document).on('click', '.btn-delete', function (e) {
                e.preventDefault();
                var url = $(this).data('url') || $(this).attr('href');

                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
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
                                    text: response.message || 'Role has been deleted successfully.',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            },
                            error: function (xhr) {
                                var message = xhr.responseJSON && xhr.responseJSON.message 
                                    ? xhr.responseJSON.message 
                                    : 'Failed to delete role.';
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: message
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush
