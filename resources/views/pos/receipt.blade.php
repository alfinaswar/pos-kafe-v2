<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Struk #{{ $sale->invoice_number }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            width: 78mm;
            margin: 0 auto;
            padding: 8px;
            font-size: 12px;
            color: #000;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .divider { border-top: 1px dashed #000; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; }
        @media print {
            body { width: 100%; margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="text-center">
    <h3 style="margin: 0;">{{ $sale->shift->outlet->name ?? 'KEDAI KOPI KAYA RAYA' }}</h3>
    <small>{{ $sale->shift->outlet->address ?? 'Jl. Tengku Zainal Abidin No. 1, Kota Pekanbaru' }}<br>Telp: {{ $sale->shift->outlet->phone ?? '-' }}</small>
</div>

<div class="divider"></div>

<table>
    <tr>
        <td>No: {{ $sale->invoice_number }}</td>
        <td class="text-right">{{ $sale->created_at->format('d/m/y H:i') }}</td>
    </tr>
    <tr>
        <td>Kasir: {{ $sale->user->name }}</td>
        <td class="text-right">{{ strtoupper($sale->payment_method) }}</td>
    </tr>
</table>

<div class="divider"></div>

<table>
    @foreach($sale->items as $item)
        <tr>
            <td colspan="2">{{ $item->product->name }}</td>
        </tr>
        <tr>
            <td>{{ $item->quantity }} x {{ number_format($item->unit_price, 0, ',', '.') }}</td>
            <td class="text-right">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
        </tr>
    @endforeach
</table>

<div class="divider"></div>

<table>
    <tr>
        <td><strong>TOTAL</strong></td>
        <td class="text-right"><strong>Rp {{ number_format($sale->total_price, 0, ',', '.') }}</strong></td>
    </tr>
    <tr>
        <td>TUNAI / BAYAR</td>
        <td class="text-right">Rp {{ number_format($sale->payment_amount, 0, ',', '.') }}</td>
    </tr>
    <tr>
        <td>KEMBALIAN</td>
        <td class="text-right">Rp {{ number_format($sale->change_amount, 0, ',', '.') }}</td>
    </tr>
</table>

<div class="divider"></div>

<div class="text-center" style="margin-top: 10px;">
    <p style="margin: 0;">Terima Kasih Atas Kunjungan Anda!</p>
    <small>Instagram: @kedaikopi.kayaraya</small>
</div>

<div class="no-print" style="margin-top: 15px; text-align: center;">
    <button onclick="window.print()" style="padding: 5px 15px; cursor: pointer;">Cetak Ulang</button>
</div>

</body>
</html>
