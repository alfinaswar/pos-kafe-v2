@extends('layouts.app')
@section('title', 'Laba Rugi')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Laba Rugi</h3></div><div class="card-body"><dl><dt>Penjualan</dt><dd>Rp {{ number_format($revenue, 0, ',', '.') }}</dd><dt>HPP</dt><dd>Rp {{ number_format($cogs, 0, ',', '.') }}</dd><dt>Laba Kotor</dt><dd>Rp {{ number_format($grossProfit, 0, ',', '.') }}</dd></dl></div></div>
@endsection
