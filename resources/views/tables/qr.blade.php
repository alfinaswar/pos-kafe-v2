<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR {{ $table->name }}</title>
    <style>body{font-family:Arial,sans-serif;text-align:center;padding:32px}.qr{display:inline-block;border:1px solid #ddd;padding:16px;margin:16px}button{padding:8px 16px}@media print{button{display:none}}</style>
</head>
<body>
    <h1>{{ $table->name }}</h1>
    <p>Scan untuk membuka menu dan memesan.</p>
    <div class="qr">
        <img src="{{ route('tables.qr.image', $table) }}" width="300" height="300" alt="QR code {{ $table->name }}">
    </div>
    <p><small style="word-break:break-all">{{ url('/order/' . $table->qr_token) }}</small></p>
    <button onclick="window.print()">Cetak</button>
</body>
</html>
