@extends('layouts.app')

@section('title', 'Tambah Meja')

@section('content')
<div class="card card-primary">
    <div class="card-header"><h3 class="card-title">Tambah Meja</h3></div>
    <form method="POST" action="{{ route('tables.store') }}">
        @csrf
        <div class="card-body">
            <div class="form-group"><label>Nama Meja</label><input name="name" class="form-control" value="{{ old('name') }}" required></div>
            <div class="form-group"><label>Kapasitas</label><input name="capacity" type="number" min="1" max="100" class="form-control" value="{{ old('capacity', 2) }}" required></div>
        </div>
        <div class="card-footer"><button class="btn btn-primary">Simpan</button></div>
    </form>
</div>
@endsection
