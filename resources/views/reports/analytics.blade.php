@extends('layouts.app')
@section('title', 'Analitik Bisnis')
@section('content')
<div class="row"><div class="col-md-6"><div class="card"><div class="card-header">Produk Terlaris</div><div class="card-body"><ul>@foreach($topProducts as $item)<li>{{ $item->product->name }} — {{ $item->quantity }} terjual</li>@endforeach</ul></div></div></div><div class="col-md-6"><div class="card"><div class="card-header">Produk Kurang Laku</div><div class="card-body"><ul>@foreach($lowProducts as $item)<li>{{ $item->product->name }} — {{ $item->quantity }} terjual</li>@endforeach</ul></div></div></div><div class="col-12"><div class="card"><div class="card-header">Stok Kritis</div><div class="card-body"><ul>@foreach($criticalMaterials as $material)<li>{{ $material->name }} — {{ $material->stock }} {{ $material->recipe_unit }}</li>@endforeach</ul></div></div></div></div>
@endsection
