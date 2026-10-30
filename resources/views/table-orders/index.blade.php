@extends('layouts.app')

@section('title', 'Order Meja')

@section('content')
<meta http-equiv="refresh" content="15">
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Order dari Meja</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>Order</th><th>Meja</th><th>Total</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            @forelse ($orders as $order)
                <tr>
                    <td>
                        <strong>{{ $order->order_number }}</strong><br>
                        @foreach ($order->items as $item)
                            <small>{{ $item->quantity }}x {{ $item->product->name }}</small>@if (!$loop->last), @endif
                        @endforeach
                        <br><small>{{ $order->created_at->format('d/m/Y H:i') }}</small>
                    </td>
                    <td>{{ $order->session->table->name }}</td>
                    <td>Rp {{ number_format($order->total, 0, ',', '.') }}</td>
                    <td><span class="badge badge-info">{{ strtoupper($order->status) }}</span></td>
                    <td>
                        <form method="POST" action="{{ route('table-orders.status', $order) }}" class="d-inline">
                            @csrf @method('PATCH')
                            <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                                @foreach (['pending', 'accepted', 'preparing', 'ready', 'completed', 'cancelled'] as $status)
                                    <option value="{{ $status }}" @selected($order->status === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </form>
                        @if (in_array($order->status, ['accepted', 'ready']))
                            <form method="POST" action="{{ route('table-orders.checkout', $order) }}" class="mt-1">
                                @csrf
                                <input type="hidden" name="payment_amount" value="{{ $order->total }}">
                                <input type="hidden" name="payment_method" value="cash">
                                <button class="btn btn-success btn-sm" type="submit">Bayar Tunai</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted">Belum ada order meja aktif.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($orders->hasPages()) <div class="card-footer">{{ $orders->links() }}</div> @endif
</div>
@endsection
