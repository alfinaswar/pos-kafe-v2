@extends('layouts.app')

@section('title', 'Permintaan Meja')

@section('content')
<meta http-equiv="refresh" content="15">
<div class="card"><div class="card-header"><h3 class="card-title">Panggilan Customer</h3></div><div class="card-body p-0"><table class="table mb-0"><thead><tr><th>Meja</th><th>Permintaan</th><th>Waktu</th><th>Aksi</th></tr></thead><tbody>
@forelse ($requests as $request)
<tr><td>{{ $request->session->table->name }}</td><td>{{ $request->type === 'call_cashier' ? 'Panggil Kasir' : 'Minta Bill' }}</td><td>{{ $request->created_at->format('d/m/Y H:i') }}</td><td><form method="POST" action="{{ route('table-requests.update', $request) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="handled"><button class="btn btn-success btn-sm">Tandai Selesai</button></form></td></tr>
@empty
<tr><td colspan="4" class="text-center text-muted">Tidak ada permintaan aktif.</td></tr>
@endforelse
</tbody></table></div></div>
@endsection
