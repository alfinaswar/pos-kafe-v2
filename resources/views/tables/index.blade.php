@extends('layouts.app')

@section('title', 'Meja Kafe')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Meja Kafe</h3>
        <a href="{{ route('tables.create') }}" class="btn btn-primary btn-sm">Tambah Meja</a>
    </div>
    <div class="card-body table-responsive p-0">
        <form method="POST" action="{{ route('materials.transfer') }}" class="p-3 border-bottom">@csrf<div class="form-row"><select name="material_id" class="form-control col mr-2" required>@foreach(\App\Models\Material::orderBy('name')->get() as $material)<option value="{{ $material->id }}">{{ $material->name }}</option>@endforeach</select><select name="destination_outlet_id" class="form-control col mr-2" required>@foreach(\App\Models\Outlet::where('id','!=',auth()->user()->outlet_id)->get() as $outlet)<option value="{{ $outlet->id }}">{{ $outlet->name }}</option>@endforeach</select><input name="quantity" type="number" min="0.01" step="0.01" class="form-control col mr-2" placeholder="Qty" required><input name="reason" class="form-control col mr-2" placeholder="Alasan" required><button class="btn btn-primary">Transfer</button></div></form>
<div class="row p-3">@foreach($tables as $table)<div class="col-md-3"><div class="card border-{{ $table->occupancy_status === 'available' ? 'success' : 'warning' }}"><div class="card-body text-center"><h5>{{ $table->name }}</h5><span class="badge badge-{{ $table->occupancy_status === 'available' ? 'success' : 'warning' }}">{{ ucfirst($table->occupancy_status) }}</span></div></div></div>@endforeach</div>
<div class="alert alert-info">Gunakan endpoint pindah/gabung sesi untuk operasi meja aktif. Token sesi tetap dibatasi outlet.</div>
        <table class="table table-hover mb-0">
            <thead><tr><th>Nama</th><th>Outlet</th><th>Kapasitas</th><th>Status Operasional</th><th>QR</th></tr></thead>
            <tbody>
            @forelse ($tables as $table)
                <tr>
                    <td>{{ $table->name }}</td>
                    <td>{{ $table->outlet->name }}</td>
                    <td>{{ $table->capacity }} orang</td>
                    <td>
                        @php($occupancyClass = ['available' => 'success', 'occupied' => 'warning', 'cleaning' => 'info'][$table->occupancy_status] ?? 'secondary')
                        <span class="badge badge-{{ $occupancyClass }}">{{ ucfirst($table->occupancy_status) }}</span>
                    </td>
                    <td><a target="_blank" href="{{ route('tables.qr', $table) }}">Cetak QR</a> | <a target="_blank" href="{{ route('table-orders.menu', $table->qr_token) }}">Buka menu</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted">Belum ada meja.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($tables->hasPages()) <div class="card-footer">{{ $tables->links() }}</div> @endif
</div>
@endsection
