@extends('layouts.app')

@section('title', 'Pengaturan Pembayaran')

@section('content')
<div class="card card-primary">
    <div class="card-header"><h3 class="card-title">DOKU Payment Gateway</h3></div>
    <form method="POST" action="{{ route('settings.payment.update') }}">
        @csrf
        <div class="card-body">
            <div class="alert alert-warning">Secret key disimpan terenkripsi dan tidak pernah ditampilkan kembali.</div>
            <div class="form-group"><label>Client ID</label><input name="doku_client_id" class="form-control" value="{{ old('doku_client_id', $settings['doku_client_id'] ?? '') }}" required></div>
            <div class="form-group"><label>Secret Key</label><input name="doku_secret_key" type="password" class="form-control" placeholder="Kosongkan untuk mempertahankan secret saat ini"></div>
            <div class="form-group"><label>Mode</label><select name="doku_mode" class="form-control"><option value="sandbox" @selected(($settings['doku_mode'] ?? 'sandbox') === 'sandbox')>Sandbox</option><option value="production" @selected(($settings['doku_mode'] ?? '') === 'production')>Production</option></select></div>
            <div class="form-check"><input name="doku_enabled" value="1" type="checkbox" class="form-check-input" id="doku_enabled" @checked(($settings['doku_enabled'] ?? '0') === '1')><label class="form-check-label" for="doku_enabled">Aktifkan DOKU</label></div>
        </div>
        <div class="card-footer"><button class="btn btn-primary">Simpan Pengaturan</button></div>
    </form>
</div>
@endsection
