<h1>Alert Inventory</h1>
<h2>Stok Kritis</h2>
<ul>@foreach($critical as $material)<li>{{ $material['name'] }}: {{ $material['stock'] }} {{ $material['unit'] }}</li>@endforeach</ul>
<h2>Kedaluwarsa</h2>
<ul>@foreach($expiring as $batch)<li>{{ $batch['name'] }}: {{ $batch['date'] }}</li>@endforeach</ul>
