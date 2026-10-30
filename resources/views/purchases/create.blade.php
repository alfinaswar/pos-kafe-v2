@extends('layouts.app')

@section('title', 'Create Purchase Order')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('purchases.index') }}">Purchases</a></li>
    <li class="breadcrumb-item active">Create PO</li>
@endsection

@section('content')
<form action="{{ route('purchases.store') }}" method="POST">
    @csrf
    <div class="row">
        <div class="col-md-4">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">PO Header Info</h3>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show">
                            <ul class="mb-0 pl-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    <div class="form-group">
                        <label for="supplier_id">Select Supplier <span class="text-danger">*</span></label>
                        <select name="supplier_id" id="supplier_id" class="form-control" required>
                            <option value="">-- Choose Supplier --</option>
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea name="notes" class="form-control" id="notes" rows="4" placeholder="Catatan pembelian bahan baku..."></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-boxes mr-1"></i> Order Items (Purchase Units)</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-success btn-sm" id="btn-add-item">
                            <i class="fas fa-plus mr-1"></i> Add Material
                        </button>
                    </div>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-bordered mb-0" id="table-items">
                        <thead>
                            <tr>
                                <th>Material</th>
                                <th style="width: 140px;">Qty (Purchase Unit)</th>
                                <th style="width: 160px;">Unit Price (Rp)</th>
                                <th style="width: 140px;">Tgl Expired</th>
                                <th style="width: 140px;">Subtotal (Rp)</th>
                                <th style="width: 40px;"></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-right">Estimated Total Cost:</th>
                                <th id="grand-total-text" class="text-primary font-weight-bold">Rp 0</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                    <div class="p-3 text-muted text-sm" id="empty-item-msg">
                        <i class="fas fa-info-circle mr-1"></i> Belum ada bahan baku dipilih. Klik "Add Material" di atas.
                    </div>
                </div>
                <div class="card-footer text-right">
                    <a href="{{ route('purchases.index') }}" class="btn btn-default mr-2">Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane mr-1"></i> Submit PO</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    const materialsData = @json($materials);
    let rowIdx = 0;

    function addItemRow() {
        $('#empty-item-msg').hide();
        let options = '<option value="">-- Choose Material --</option>';
        materialsData.forEach(mat => {
            options += `<option value="${mat.id}" data-punit="${mat.purchase_unit}">${mat.name} (${mat.purchase_unit})</option>`;
        });

        let rowHtml = `
            <tr id="row-${rowIdx}">
                <td>
                    <select name="materials[${rowIdx}][id]" class="form-control select-mat" required>
                        ${options}
                    </select>
                </td>
                <td>
                    <div class="input-group">
                        <input type="number" step="any" min="0.01" name="materials[${rowIdx}][qty]" class="form-control item-qty" placeholder="Qty" required>
                        <div class="input-group-append">
                            <span class="input-group-text punit-badge text-xs">-</span>
                        </div>
                    </div>
                </td>
                <td>
                    <input type="number" step="any" min="0" name="materials[${rowIdx}][price]" class="form-control item-price" placeholder="Harga satuan" required>
                </td>
                <td>
                    <input type="date" name="materials[${rowIdx}][expired_date]" class="form-control form-control-sm">
                </td>
                <td class="align-middle font-weight-bold item-subtotal text-right">Rp 0</td>
                <td class="text-center align-middle">
                    <button type="button" class="btn btn-danger btn-sm btn-del-row"><i class="fas fa-times"></i></button>
                </td>
            </tr>
        `;
        $('#table-items tbody').append(rowHtml);
        rowIdx++;
    }

    $(document).on('change', '.select-mat', function() {
        let punit = $(this).find(':selected').data('punit') || '-';
        $(this).closest('tr').find('.punit-badge').text(punit);
    });

    $(document).on('input', '.item-qty, .item-price', function() {
        let row = $(this).closest('tr');
        let qty = parseFloat(row.find('.item-qty').val()) || 0;
        let price = parseFloat(row.find('.item-price').val()) || 0;
        let subtotal = qty * price;
        row.find('.item-subtotal').text('Rp ' + subtotal.toLocaleString('id-ID'));
        calcGrandTotal();
    });

    $(document).on('click', '.btn-del-row', function() {
        $(this).closest('tr').remove();
        if ($('#table-items tbody tr').length === 0) {
            $('#empty-item-msg').show();
        }
        calcGrandTotal();
    });

    function calcGrandTotal() {
        let total = 0;
        $('#table-items tbody tr').each(function() {
            let qty = parseFloat($(this).find('.item-qty').val()) || 0;
            let price = parseFloat($(this).find('.item-price').val()) || 0;
            total += (qty * price);
        });
        $('#grand-total-text').text('Rp ' + total.toLocaleString('id-ID'));
    }

    $('#btn-add-item').click(function() {
        addItemRow();
    });

    $(document).ready(function() {
        addItemRow();
    });
</script>
@endpush
