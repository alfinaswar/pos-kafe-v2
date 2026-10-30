@extends('layouts.app')
@section('title', 'Margin Produk')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">HPP dan Margin Produk</h3></div><div class="card-body p-0"><table class="table mb-0"><thead><tr><th>Produk</th><th>Harga</th><th>HPP</th><th>Margin</th><th>Margin %</th></tr></thead><tbody>
@foreach($products as $product)<tr><td>{{ $product['name'] }}</td><td>Rp {{ number_format($product['price'], 0, ',', '.') }}</td><td>Rp {{ number_format($product['cost'], 0, ',', '.') }}</td><td>Rp {{ number_format($product['margin'], 0, ',', '.') }}</td><td>{{ number_format($product['margin_percent'], 2) }}%</td></tr>@endforeach
</tbody></table></div></div>
@endsection
