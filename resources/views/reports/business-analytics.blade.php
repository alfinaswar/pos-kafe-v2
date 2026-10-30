@extends('layouts.app')
@section('title', 'Business Analytics')
@section('content')
<div class="card"><div class="card-header">Penjualan per Jam</div><div class="card-body"><ul>@foreach($hourlySales as $row)<li>{{ $row->hour }}:00 — Rp {{ number_format($row->total, 0, ',', '.') }}</li>@endforeach</ul></div></div><div class="card"><div class="card-header">Perbandingan Outlet</div><div class="card-body"><ul>@foreach($outletComparison as $outlet => $total)<li>{{ $outlet }} — Rp {{ number_format($total, 0, ',', '.') }}</li>@endforeach</ul></div></div><div class="card"><div class="card-header">Forecast</div><div class="card-body"><ul>@foreach($forecast as $material)<li>{{ $material->name }} — stok {{ $material->stock }}, reorder {{ $material->reorder_point }}</li>@endforeach</ul></div></div>
@endsection
