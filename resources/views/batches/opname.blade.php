@extends('layouts.app')

@section('title', 'Stock Opname & Waste Log')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('batches.index') }}">Batches</a></li>
    <li class="breadcrumb-item active">Stock Opname</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card card-outline card-warning shadow-sm">
            <div class="card-header bg-warning">
                <h3 class="card-title font-weight-bold text-dark">
                    <i class="fas fa-clipboard-check mr-1"></i> Form Stock Opname & Pencatatan Waste (Bahan Rusak/Basi)
                </h3>
            </div>
            <form action="{{ route('batches.opname.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-1"></i> Gunakan formulir ini untuk menyesuaikan stok gudang hasil audit fisik atau mencatat bahan baku yang terbuang/basi/rusak (waste) agar stok sistem akurat.
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="form-group">
                        <label for="material_id">Pilih Bahan Baku <span class="text-danger">*</span></label>
                        <select name="material_id" id="material_id" class="form-control" required>
                            <option value="">-- Pilih Bahan Baku --</option>
                            @foreach($materials as $mat)
                                <option value="{{ $mat->id }}" {{ old('material_id') == $mat->id ? 'selected' : '' }} data-unit="{{ $mat->recipe_unit }}" data-stock="{{ $mat->stock }}">
                                    {{ $mat->name }} (Stok Sistem: {{ number_format($mat->stock, 0, ',', '.') }} {{ $mat->recipe_unit }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="batch_id">Pilih Batch Spesifik (Opsional - Jika Dikosongkan FIFO Otomatis)</label>
                        <select name="batch_id" id="batch_id" class="form-control" data-selected="{{ old('batch_id') }}">
                            <option value="">-- Potong FIFO Otomatis (Batch Tertua) --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="type">Tipe Penyesuaian <span class="text-danger">*</span></label>
                        <select name="type" id="type" class="form-control" required>
                            <option value="waste" {{ old('type') == 'waste' ? 'selected' : '' }}>Waste (Bahan Rusak / Basi / Kadaluarsa / Tumpah)</option>
                            <option value="opname_loss" {{ old('type') == 'opname_loss' ? 'selected' : '' }}>Opname Selisih Kurang (Hilang / Tak Terdata)</option>
                            <option value="opname_gain" {{ old('type') == 'opname_gain' ? 'selected' : '' }}>Opname Selisih Lebih (Stok Fisik Lebih Banyak)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="quantity">Jumlah Penyesuaian (<span id="unit-label">satuan resep</span>) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0.01" name="quantity" id="quantity" class="form-control font-weight-bold" placeholder="Contoh: 250" value="{{ old('quantity') }}" required>
                            <div class="input-group-append">
                                <span class="input-group-text" id="unit-addon">recipe_unit</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reason">Alasan / Catatan Audit <span class="text-danger">*</span></label>
                        <textarea name="reason" id="reason" class="form-control" rows="3" placeholder="Contoh: Susu basi sebelum tanggal kadaluarsa karena kulkas mati..." required>{{ old('reason') }}</textarea>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ route('batches.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-warning font-weight-bold text-dark" onclick="return confirm('Proses penyesuaian stok ini?')">
                        <i class="fas fa-save mr-1"></i> Simpan Penyesuaian Stok
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const materialsData = @json($materials);

    $('#material_id').change(function() {
        const matId = $(this).val();
        const selectedOpt = $(this).find('option:selected');
        const unit = selectedOpt.data('unit') || 'unit';

        $('#unit-label').text(unit);
        $('#unit-addon').text(unit);

        const batchSelect = $('#batch_id');
        batchSelect.empty();
        batchSelect.append('<option value="">-- Potong FIFO Otomatis (Batch Tertua) --</option>');

        if (!matId) return;

        const mat = materialsData.find(m => m.id == matId);
        if (mat && mat.batches) {
            mat.batches.forEach(b => {
                const bCode = b.batch_number || ('BATCH #' + b.id);
                const exp = b.expired_date ? (' (Exp: ' + b.expired_date + ')') : '';
                batchSelect.append(`<option value="${b.id}">${bCode} - Sisa: ${b.remaining_qty} ${unit}${exp}</option>`);
            });
        }

        const selectedBatch = batchSelect.data('selected');
        if (selectedBatch) {
            batchSelect.val(selectedBatch);
        }
    });

    if ($('#material_id').val()) {
        $('#material_id').trigger('change');
    }
</script>
@endpush
