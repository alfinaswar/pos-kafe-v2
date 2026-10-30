<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Menu {{ $table->name }}</title>
</head>
<body>
    <main>
        <h1>{{ $table->name }}</h1>
        <p>Silakan pilih menu. Pembayaran dilakukan di kasir.</p>
        <ul>
            @forelse ($products as $product)
                <li>
                    <strong>{{ $product->name }}</strong>
                    <span>Rp {{ number_format($product->price, 0, ',', '.') }}</span>
                    @if ($product->detail)<p>{{ $product->detail }}</p>@endif
                </li>
            @empty
                <li>Menu belum tersedia.</li>
            @endforelse
        </ul>
    </main>
</body>
</html>
