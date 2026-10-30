@extends('layouts.app')

@section('title', 'Audit Trail (Activity Logs)')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item active">Activity Logs</li>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
@endpush

@section('content')
<div class="card card-outline card-dark">
    <div class="card-header">
        <h3 class="card-title font-weight-bold">
            <i class="fas fa-shield-alt mr-1"></i> Log Aktivitas Sensitif Sistem (Audit Trail)
        </h3>
        <div class="card-tools d-flex align-items-center">
            <select id="filter_action" class="form-control form-control-sm mr-2" style="width: 200px;">
                <option value="">-- Semua Aksi --</option>
                @foreach($actions as $act)
                    <option value="{{ $act }}">{{ $act }}</option>
                @endforeach
            </select>
            <button id="btn-filter" class="btn btn-secondary btn-sm"><i class="fas fa-filter mr-1"></i> Filter</button>
        </div>
    </div>
    <div class="card-body">
        <table id="activity-table" class="table table-bordered table-striped table-hover dt-responsive nowrap" style="width:100%">
            <thead>
                <tr>
                    <th style="width: 5%">No</th>
                    <th style="width: 15%">Waktu</th>
                    <th style="width: 15%">Pengguna</th>
                    <th style="width: 15%">Aksi</th>
                    <th>Deskripsi Aktivitas</th>
                    <th style="width: 12%">IP Address</th>
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
            var table = $('#activity-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('activity-logs.index') }}",
                    data: function(d) {
                        d.action = $('#filter_action').val();
                    }
                },
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                    {data: 'time', name: 'created_at'},
                    {data: 'user_name', name: 'user.name'},
                    {data: 'action_badge', name: 'action'},
                    {data: 'description', name: 'description'},
                    {data: 'ip_address', name: 'ip_address', defaultContent: '-'}
                ],
                order: [[1, 'desc']]
            });

            $('#btn-filter').click(function() {
                table.draw();
            });
            $('#filter_action').change(function() {
                table.draw();
            });
        });
    </script>
@endpush
