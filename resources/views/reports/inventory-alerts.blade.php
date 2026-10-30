@extends('layouts.app')
@section('title', 'Alert Inventory')
@section('content')
<div class="row"><div class="col-md-6"><div class="card"><div class="card-header">Stok Kritis</div><div class="card-body"><ul>@forelse($criticalMaterials as $material)<li>{{ $material->name }} — {{ $material->stock }} {{ $material->recipe_unit }}</li>@empty<li>Tidak ada stok kritis.</li>@endforelse</ul></div></div></div><div class="col-md-6"><div class="card"><div class="card-header">Kedaluwarsa</div><div class="card-body"><ul>@forelse($expiringBatches as $batch)<li>{{ $batch->material->name }} — {{ $batch->expired_date->format('d/m/Y') }}</li>@empty<li>Tidak ada batch mendekati kedaluwarsa.</li>@endforelse</ul></div></div></div></div>
@endsection
