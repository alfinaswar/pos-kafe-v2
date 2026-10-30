@extends('layouts.app')
@section('title', 'Approval')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Approval Transaksi Berisiko</h3></div><div class="card-body p-0"><table class="table mb-0"><thead><tr><th>Jenis</th><th>Referensi</th><th>Alasan</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
@forelse($approvals as $approval)<tr><td>{{ strtoupper($approval->type) }}</td><td>{{ $approval->reference_type }} #{{ $approval->reference_id }}</td><td>{{ $approval->reason }}</td><td>{{ strtoupper($approval->status) }}</td><td>@if($approval->status === 'pending')<form method="POST" action="{{ route('approvals.update', $approval) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="approved"><button class="btn btn-success btn-sm">Setujui</button></form>@endif</td></tr>@empty<tr><td colspan="5" class="text-center">Tidak ada approval.</td></tr>@endforelse
</tbody></table></div></div>
@endsection
