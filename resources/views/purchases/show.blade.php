@extends('layouts.app')

@section('title', 'Purchase Order Detail')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('purchases.index') }}">Purchases</a></li>
    <li class="breadcrumb-item active">{{ $purchase->invoice_number }}</li>
@endsection

@section('content')
<div class="invoice p-3 mb-3">
    <!-- title row -->
    <div class="row">
        <div class="col-12">
            <h4>
                <i class="fas fa-file-invoice"></i> {{ $purchase->invoice_number }}
                <small class="float-right">Date: {{ $purchase->created_at->format('d/m/Y H:i') }}</small>
            </h4>
        </div>
    </div>

    <!-- info row -->
    <div class="row invoice-info my-3">
        <div class="col-sm-4 invoice-col">
            Supplier Info
            <address>
                <strong>{{ $purchase->supplier->name }}</strong><br>
                Phone: {{ $purchase->supplier->phone ?? '-' }}<br>
                Address: {{ $purchase->supplier->address ?? '-' }}
            </address>
        </div>
        <div class="col-sm-4 invoice-col">
            Issuer
            <address>
                <strong>Created By:</strong> {{ $purchase->user->name }}<br>
                <strong>Notes:</strong> {{ $purchase->notes ?? '-' }}
            </address>
        </div>
        <div class="col-sm-4 invoice-col">
            <b>Status:</b> 
            @php
                $badges = ['pending'=>'badge-warning', 'approved'=>'badge-primary', 'completed'=>'badge-success', 'rejected'=>'badge-danger'];
            @endphp
            <span class="badge {{ $badges[$purchase->status] ?? 'badge-secondary' }} p-2">{{ strtoupper($purchase->status) }}</span><br>
            <b>Total Cost:</b> <span class="text-primary font-weight-bold">Rp {{ number_format($purchase->total_cost, 0, ',', '.') }}</span>
        </div>
    </div>

    <!-- Table row -->
    <div class="row">
        <div class="col-12 table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Material</th>
                        <th>Qty Beli (Purchase Unit)</th>
                        <th>Konversi ke Recipe Unit</th>
                        <th>Unit Price</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchase->details as $item)
                        <tr>
                            <td>{{ $item->material->name }}</td>
                            <td>{{ number_format($item->qty_purchase, 2) }} {{ $item->material->purchase_unit }}</td>
                            <td>
                                <span class="badge badge-info">
                                    + {{ number_format($item->qty_purchase * $item->material->conversion_factor, 2) }} {{ $item->material->recipe_unit }}
                                </span>
                            </td>
                            <td>Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Action buttons -->
    <div class="row no-print mt-4">
        <div class="col-12 text-right">
            <a href="{{ route('purchases.index') }}" class="btn btn-default mr-2"><i class="fas fa-arrow-left"></i> Back to List</a>

            <!-- Owner Action: Approve or Reject -->
            @can('purchase-approve')
                @if($purchase->status === 'pending')
                    <form action="{{ route('purchases.reject', $purchase->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-danger mr-2" onclick="return confirm('Reject this PO?')">
                            <i class="fas fa-times mr-1"></i> Reject PO
                        </button>
                    </form>

                    <form action="{{ route('purchases.approve', $purchase->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-primary mr-2" onclick="return confirm('Approve this PO?')">
                            <i class="fas fa-check mr-1"></i> Approve PO
                        </button>
                    </form>
                @endif
            @endcan

            <!-- Manager Action: Goods Receipt (Penerimaan Barang) -->
            @can('purchase-receive')
                @if($purchase->status === 'approved')
                    <form action="{{ route('purchases.receive', $purchase->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success" onclick="return confirm('Confirm Goods Receipt? Inventory will be converted and updated.')">
                            <i class="fas fa-truck-loading mr-1"></i> Receive Goods (Penerimaan Barang)
                        </button>
                    </form>
                @endif
            @endcan
        </div>
    </div>
</div>
@endsection
