@extends('layouts.app')

@section('title', 'Kitchen Display')

@section('content')
<meta http-equiv="refresh" content="15">
<script>let knownOrders=[...document.querySelectorAll('.card-header strong')].map(x=>x.textContent);setInterval(()=>fetch(location.href).then(r=>r.text()).then(html=>{let doc=new DOMParser().parseFromString(html,'text/html');let next=[...doc.querySelectorAll('.card-header strong')].map(x=>x.textContent);if(next.some(x=>!knownOrders.includes(x))){document.title='ORDER BARU';location.reload();}knownOrders=next;}),10000);</script>
<div class="mb-3"><a href="{{ route('kitchen.index', ['destination' => 'bar']) }}" class="btn btn-info">Bar</a> <a href="{{ route('kitchen.index', ['destination' => 'kitchen']) }}" class="btn btn-warning">Dapur</a> <a href="{{ route('kitchen.index') }}" class="btn btn-secondary">Semua</a><span class="ml-2 text-muted">Polling aktif: 10 detik</span></div>
<div class="row">
@forelse ($orders as $order)
    <div class="col-md-4">
        <div class="card card-outline card-warning">
            <div class="card-header"><strong>{{ $order->order_number }}</strong> · {{ $order->session->table->name }}</div>
            <div class="card-body">
                <ul>
                @foreach ($order->items as $item)
                    <li>{{ $item->quantity }}x {{ $item->product->name }} @if($item->notes) — {{ $item->notes }} @endif</li>
                @endforeach
                </ul>
                <span class="badge badge-warning">{{ strtoupper($order->status) }}</span>
            </div>
            <div class="card-footer">
                <form method="POST" action="{{ route('kitchen.orders.status', $order) }}">
                    @csrf @method('PATCH')
                    <select name="status" class="form-control" onchange="this.form.submit()">
                        @foreach (['accepted', 'preparing', 'ready', 'completed'] as $status)
                            <option value="{{ $status }}" @selected($order->status === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
    </div>
@empty
    <div class="col-12"><div class="alert alert-info">Tidak ada order aktif.</div></div>
@endforelse
</div>
@endsection
