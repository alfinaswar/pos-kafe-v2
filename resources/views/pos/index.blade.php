@extends('layouts.app')

@section('title', 'Point of Sale (POS Kasir)')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item active">POS</li>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/sweetalert2/sweetalert2.min.css') }}">
    <style>
        @media (max-width: 767.98px) { .product-card{min-height:110px}.cart-table{font-size:14px}.card-footer{position:sticky;bottom:0;z-index:10}.btn-lg{min-height:52px}.form-control{min-height:44px}.category-btn{min-height:42px} }
        .product-card {
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s;
            border-radius: 8px;
        }
        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.12);
        }
        .cart-table th, .cart-table td {
            vertical-align: middle !important;
        }
    </style>
@endpush

@section('content')
<!-- Shift Information & Quick Actions Bar -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card card-outline card-success shadow-sm mb-0">
            <div class="card-body p-2 d-flex flex-wrap justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <span class="badge badge-success px-3 py-2 mr-3 font-weight-bold">
                        <i class="fas fa-cash-register mr-1"></i> SHIFT AKTIF #{{ $activeShift->id }}
                    </span>
                    <span class="mr-3 text-sm">
                        <i class="fas fa-store text-muted mr-1"></i> Outlet: <strong>{{ $activeShift->outlet->name ?? 'Pusat' }}</strong>
                    </span>
                    <span class="mr-3 text-sm">
                        <i class="fas fa-wallet text-muted mr-1"></i> Modal Awal: <strong>Rp {{ number_format($activeShift->start_cash, 0, ',', '.') }}</strong>
                    </span>
                    <span class="mr-3 text-sm">
                        <i class="fas fa-coins text-success mr-1"></i> Penjualan Tunai: <strong id="bar-sales-cash" class="text-success">Rp {{ number_format($activeShift->total_sales_cash, 0, ',', '.') }}</strong>
                    </span>
                    <span class="mr-3 text-sm">
                        <i class="fas fa-money-bill-wave text-primary mr-1"></i> Ekspektasi Kas: <strong id="bar-expected-cash" class="text-primary">Rp {{ number_format($expectedCash, 0, ',', '.') }}</strong>
                    </span>
                </div>
                <div class="mt-2 mt-md-0">
                    <button type="button" class="btn btn-outline-info btn-sm mr-2" data-toggle="modal" data-target="#modal-petty-cash">
                        <i class="fas fa-hand-holding-usd mr-1"></i> Kas Operasional (Petty Cash)
                    </button>
                    <a href="{{ route('shifts.close.form', $activeShift->id) }}" class="btn btn-warning btn-sm font-weight-bold text-dark">
                        <i class="fas fa-lock mr-1"></i> Tutup Shift
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Menu Grid -->
    <div class="col-md-7 col-lg-8">
        <!-- Category Filter -->
        <div class="mb-3 d-flex flex-wrap gap-2">
            <button class="btn btn-primary btn-sm category-btn active mr-1 mb-1" data-cat="all">Semua Menu</button>
            @foreach($categories as $cat)
                <button class="btn btn-outline-secondary btn-sm category-btn mr-1 mb-1" data-cat="{{ strtolower($cat) }}">{{ $cat }}</button>
            @endforeach
        </div>

        <!-- Product Cards -->
        <div class="row" id="product-container">
            @forelse($products as $prod)
                <div class="col-sm-6 col-md-4 col-lg-3 mb-3 product-item" data-category="{{ strtolower($prod->category ?? 'other') }}">
                    <div class="card h-100 product-card shadow-sm border-0" 
                         onclick="addToCart({{ $prod->id }}, '{{ addslashes($prod->name) }}', {{ $prod->price }})">
                        <div class="card-body p-3 d-flex flex-column justify-content-between text-center">
                            <div>
                                <span class="badge badge-light border text-muted mb-2">{{ $prod->category ?? 'Menu' }}</span>
                                <h6 class="font-weight-bold mb-1 text-dark">{{ $prod->name }}</h6>
                            </div>
                            <div class="mt-3">
                                <h6 class="text-success font-weight-bold mb-0">Rp {{ number_format($prod->price, 0, ',', '.') }}</h6>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5 text-muted">
                    <i class="fas fa-mug-hot fa-3x mb-3"></i>
                    <p>Belum ada produk menu. Silakan tambahkan produk di Master Data Menu.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- POS Cart & Checkout -->
    <div class="col-md-5 col-lg-4">
        <div class="card card-outline card-primary shadow">
            <div class="card-header bg-white">
                <h3 class="card-title font-weight-bold"><i class="fas fa-shopping-cart mr-1"></i> Pesanan Kasir</h3>
                <div class="card-tools">
                    <button class="btn btn-outline-warning btn-xs mr-1" id="btn-hold" onclick="holdOrder()" title="Simpan order sementara"><i class="fas fa-pause"></i> Hold</button>
                    <button class="btn btn-outline-info btn-xs mr-1" data-toggle="modal" data-target="#modal-held-orders" onclick="loadHeldOrders()" title="Panggil order yang di-hold"><i class="fas fa-history"></i> Recall</button>
                    <button class="btn btn-outline-danger btn-xs" onclick="clearCart()"><i class="fas fa-trash"></i> Reset</button>
                </div>
            </div>
            <div class="card-body p-0 table-responsive" style="max-height: 340px;">
                <table class="table table-sm cart-table mb-0" id="cart-table">
                    <thead class="bg-light">
                        <tr>
                            <th>Menu</th>
                            <th style="width: 90px;" class="text-center">Qty</th>
                            <th style="width: 100px;" class="text-right">Subtotal</th>
                            <th style="width: 58px;" class="text-center">Cat/Dsk</th>
                            <th style="width: 30px;"></th>
                        </tr>
                    </thead>
                    <tbody id="cart-body">
                        <!-- Cart Items -->
                    </tbody>
                </table>
                <div id="cart-empty" class="text-center py-5 text-muted">
                    <i class="fas fa-cash-register fa-2x mb-2 text-gray"></i>
                    <p class="mb-0 text-sm">Keranjang kosong. Pilih menu di sebelah kiri.</p>
                </div>
            </div>

            <!-- Summary & Payment -->
            <div class="card-footer bg-light p-3">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Total Tagihan:</span>
                    <h4 class="font-weight-bold text-primary mb-0" id="grand-total-display">Rp 0</h4>
                </div>

                <div class="form-group mb-2">
                    <label class="text-xs mb-1">Metode Pembayaran:</label>
                    <select id="payment-method" class="form-control form-control-sm font-weight-bold">
                        <option value="cash">Tunai (Cash)</option>
                        <option value="qris">QRIS (Non-Tunai)</option>
                        <option value="transfer">Transfer Bank (Non-Tunai)</option>
                        <option value="debit">Kartu Debit (Non-Tunai)</option>
                    </select>
                </div>

                <div class="form-group mb-2" id="payment-input-group">
                    <label class="text-xs mb-1">Uang Diterima (Bayar):</label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                        <input type="number" id="payment-input" class="form-control font-weight-bold text-success" placeholder="0" min="0">
                    </div>
                </div>

                <!-- Quick Cash Buttons -->
                <div class="d-flex flex-wrap gap-1 mb-2" id="quick-cash-buttons">
                    <button type="button" class="btn btn-outline-secondary btn-xs mr-1 mb-1 btn-quick" onclick="setExactPayment()">Uang Pas</button>
                    <button type="button" class="btn btn-outline-secondary btn-xs mr-1 mb-1 btn-quick" onclick="addPayment(20000)">20k</button>
                    <button type="button" class="btn btn-outline-secondary btn-xs mr-1 mb-1 btn-quick" onclick="addPayment(50000)">50k</button>
                    <button type="button" class="btn btn-outline-secondary btn-xs mr-1 mb-1 btn-quick" onclick="addPayment(100000)">100k</button>
                </div>

                <div class="row mb-2">
                    <div class="col-6 pr-1">
                        <label class="text-xs mb-1">Voucher Promo</label>
                        <input type="text" id="voucher-code" class="form-control form-control-sm text-uppercase" placeholder="Kode voucher" onchange="recalcTotals()">
                    </div>
                    <div class="col-6 pl-1">
                        <label class="text-xs mb-1">Member (ID)</label>
                        <input type="number" id="customer-id" class="form-control form-control-sm" placeholder="Opsional" min="1">
                    </div>
                </div>

                <div class="d-flex justify-content-between mb-1 text-sm">
                    <span class="text-muted">Diskon:</span>
                    <strong id="discount-display" class="text-warning">Rp 0</strong>
                </div>
                <div class="d-flex justify-content-between mb-3 text-sm">
                    <span>Kembalian:</span>
                    <strong id="change-display" class="text-secondary">Rp 0</strong>
                </div>

                <button type="button" class="btn btn-success btn-block btn-lg font-weight-bold" id="btn-checkout" onclick="processCheckout()" disabled>
                    <i class="fas fa-check-circle mr-1"></i> BAYAR & CETAK STRUK
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Petty Cash -->
<div class="modal fade" id="modal-petty-cash" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-hand-holding-usd mr-1"></i> Catat Kas Operasional (Petty Cash)</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="form-petty-cash">
                @csrf
                <input type="hidden" name="shift_id" value="{{ $activeShift->id }}">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Tipe Kas Operasional <span class="text-danger">*</span></label>
                        <select name="type" class="form-control" required>
                            <option value="out">Kas Keluar (Beli es batu, galon, transport, supplies darurat)</option>
                            <option value="in">Kas Masuk (Tambah modal laci, setor tunai kasir)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nominal (Rp) <span class="text-danger">*</span></label>
                        <input type="number" step="1000" min="1" name="amount" class="form-control font-weight-bold" placeholder="Contoh: 15000" required>
                    </div>
                    <div class="form-group">
                        <label>Keterangan / Keperluan <span class="text-danger">*</span></label>
                        <input type="text" name="description" class="form-control" placeholder="Contoh: Beli es kristal 1 pack" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info font-weight-bold"><i class="fas fa-save mr-1"></i> Simpan Catatan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal Order Ter-hold -->
<div class="modal fade" id="modal-held-orders" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-history mr-1"></i> Order Di-Hold</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-0">
                <div id="held-list" class="list-group list-group-flush"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Catatan & Diskon Item -->
<div class="modal fade" id="modal-item-note" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-sticky-note mr-1"></i> <span id="item-note-title">Catatan Item</span></h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Catatan Kasir (mis. less sugar, tanpa es)</label>
                    <input type="text" id="item-notes-input" class="form-control" maxlength="255">
                </div>
                <div class="form-group mb-0">
                    <label>Diskon Nominal (Rp) — maks = subtotal item</label>
                    <input type="number" id="item-discount-input" class="form-control" min="0" step="500">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary font-weight-bold" onclick="saveItemNote()">Simpan</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/plugins/sweetalert2/sweetalert2.all.min.js') }}"></script>
<script>
    let cart = [];

    // Subtotal kotor dan bersih (setelah diskon item) per baris keranjang.
    function grossSub(item) { return item.quantity * item.price; }
    function itemDisc(item) { return Math.min(parseFloat(item.discount) || 0, grossSub(item)); }
    function netSub(item) { return grossSub(item) - itemDisc(item); }
    function cartGross() { return cart.reduce((a, i) => a + grossSub(i), 0); }
    function cartItemDiscount() { return cart.reduce((a, i) => a + itemDisc(i), 0); }
    function payableTotal() { return cart.reduce((a, i) => a + netSub(i), 0); }

    // Filter Kategori
    $('.category-btn').click(function() {
        $('.category-btn').removeClass('btn-primary active').addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary').addClass('btn-primary active');

        let cat = $(this).data('cat');
        if (cat === 'all') {
            $('.product-item').show();
        } else {
            $('.product-item').hide();
            $(`.product-item[data-category="${cat}"]`).show();
        }
    });

    $('#payment-method').change(function() {
        let method = $(this).val();
        let total = cart.reduce((acc, i) => acc + (i.quantity * i.price), 0);
        if (method !== 'cash') {
            $('#payment-input').val(total);
            calcChange(total);
        }
    });

    function setExactPayment() {
        let total = payableTotal();
        $('#payment-input').val(total);
        calcChange(total);
    }

    function addPayment(amt) {
        let current = parseFloat($('#payment-input').val()) || 0;
        $('#payment-input').val(current + amt);
        calcChange(payableTotal());
    }

    function addToCart(id, name, price) {
        let existing = cart.find(i => i.product_id === id);
        if (existing) {
            existing.quantity++;
        } else {
            cart.push({
                product_id: id,
                name: name,
                price: price,
                quantity: 1
            });
        }
        renderCart();
    }

    function updateQty(id, delta) {
        let item = cart.find(i => i.product_id === id);
        if (item) {
            item.quantity += delta;
            if (item.quantity <= 0) {
                cart = cart.filter(i => i.product_id !== id);
            }
        }
        renderCart();
    }

    function removeFromCart(id) {
        cart = cart.filter(i => i.product_id !== id);
        renderCart();
    }

    function clearCart() {
        cart = [];
        $('#payment-input').val('');
        $('#voucher-code').val('');
        currentIdempotencyKey = null; // transaksi baru = kunci idempotency baru
        renderCart();
    }

    function renderCart() {
        let tbody = $('#cart-body');
        tbody.empty();
        let total = 0;

        if (cart.length === 0) {
            $('#cart-empty').show();
            $('#btn-checkout').prop('disabled', true);
        } else {
            $('#cart-empty').hide();
            $('#btn-checkout').prop('disabled', false);

            cart.forEach(item => {
                total += netSub(item);
                let noteBadge = item.notes ? `<small class="d-block text-info text-truncate" style="max-width:140px" title="${item.notes}"><i class="fas fa-sticky-note"></i> ${item.notes}</small>` : '';
                let discBadge = itemDisc(item) > 0 ? `<small class="d-block text-warning"><i class="fas fa-tag"></i> -Rp ${itemDisc(item).toLocaleString('id-ID')}</small>` : '';
                tbody.append(`
                    <tr>
                        <td class="font-weight-bold text-sm">${item.name}${noteBadge}${discBadge}</td>
                        <td class="text-center">
                            <div class="btn-group btn-group-xs">
                                <button type="button" class="btn btn-outline-secondary btn-xs px-2" onclick="updateQty(${item.product_id}, -1)">-</button>
                                <span class="px-2 align-middle font-weight-bold">${item.quantity}</span>
                                <button type="button" class="btn btn-outline-secondary btn-xs px-2" onclick="updateQty(${item.product_id}, 1)">+</button>
                            </div>
                        </td>
                        <td class="text-right text-sm">Rp ${netSub(item).toLocaleString('id-ID')}</td>
                        <td class="text-center">
                            <i class="fas fa-sticky-note text-info cursor-pointer mr-1" title="Catatan & diskon" onclick="openItemNote(${item.product_id})"></i>
                            <i class="fas fa-times text-danger cursor-pointer" onclick="removeFromCart(${item.product_id})"></i>
                        </td>
                    </tr>
                `);
            });
        }

        recalcTotals();
    }

    function recalcTotals() {
        let payable = payableTotal();
        $('#grand-total-display').text('Rp ' + payable.toLocaleString('id-ID'));
        $('#discount-display').text('Rp ' + cartItemDiscount().toLocaleString('id-ID'));
        if ($('#payment-method').val() !== 'cash') {
            $('#payment-input').val(payable);
        }
        calcChange(payable);
    }

    $('#payment-input').on('input', function() {
        calcChange(payableTotal());
    });

    function calcChange(total) {
        let pay = parseFloat($('#payment-input').val()) || 0;
        let change = pay - total;
        if (change >= 0) {
            $('#change-display').text('Rp ' + change.toLocaleString('id-ID')).removeClass('text-danger').addClass('text-success font-weight-bold');
        } else {
            $('#change-display').text('Kurang: Rp ' + Math.abs(change).toLocaleString('id-ID')).removeClass('text-success').addClass('text-danger');
        }
    }

    let editingItemId = null;
    function openItemNote(id) {
        let item = cart.find(i => i.product_id === id);
        if (!item) return;
        editingItemId = id;
        $('#item-note-title').text('Catatan: ' + item.name);
        $('#item-notes-input').val(item.notes || '');
        $('#item-discount-input').val(item.discount || '').attr('max', grossSub(item));
        $('#modal-item-note').modal('show');
    }

    function saveItemNote() {
        let item = cart.find(i => i.product_id === editingItemId);
        if (item) {
            item.notes = $('#item-notes-input').val().trim();
            item.discount = Math.min(Math.max(0, parseFloat($('#item-discount-input').val()) || 0), grossSub(item));
        }
        $('#modal-item-note').modal('hide');
        renderCart();
    }

    function holdOrder() {
        if (cart.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Keranjang kosong', text: 'Tambahkan item sebelum hold order.' });
            return;
        }
        $.ajax({
            url: "{{ route('pos.hold') }}",
            type: "POST",
            data: { _token: $('meta[name="csrf-token"]').attr('content'), items: cart.map(i => ({ product_id: i.product_id, quantity: i.quantity, price: i.price, name: i.name, notes: i.notes || '', discount: itemDisc(i) })), notes: '' },
            success: function () {
                Swal.fire({ icon: 'success', title: 'Order di-hold!', timer: 1200, showConfirmButton: false });
                clearCart();
            },
            error: function () { Swal.fire({ icon: 'error', title: 'Gagal hold order.' }); }
        });
    }

    function loadHeldOrders() {
        $.get("{{ route('pos.held-orders') }}", function (rows) {
            let box = $('#held-list').empty();
            if (!rows.length) {
                box.append('<div class="list-group-item text-center text-muted py-4">Tidak ada order yang di-hold.</div>');
                return;
            }
            rows.forEach(h => {
                box.append(`<button type="button" class="list-group-item list-group-item-action" onclick="recallOrder(${h.id})">
                    <strong>#${h.id}</strong> — ${new Date(h.created_at).toLocaleTimeString('id-ID')}
                    <span class="float-right font-weight-bold text-success">Rp ${(h.total || 0).toLocaleString('id-ID')}</span>
                    ${h.notes ? '<small class="d-block text-muted">' + h.notes + '</small>' : ''}
                </button>`);
            });
        });
    }

    function recallOrder(id) {
        if (cart.length > 0) {
            Swal.fire({ icon: 'warning', title: 'Keranjang belum kosong', text: 'Hold atau selesaikan order aktif dulu sebelum recall.' });
            return;
        }
        $.get("{{ url('pos/held-orders') }}/" + id, function (h) {
            cart = (h.items || []).map(i => ({ product_id: i.product_id, name: i.name || ('Item #' + i.product_id), price: i.price, quantity: i.quantity, notes: i.notes || '', discount: i.discount || 0 }));
            currentIdempotencyKey = null;
            $('#modal-held-orders').modal('hide');
            renderCart();
        }).fail(function () {
            Swal.fire({ icon: 'error', title: 'Gagal recall', text: 'Order mungkin sudah dipanggil.');
        });
    }

    // Kunci unik per transaksi untuk mencegah dobel-klik menyimpan transaksi ganda.
    let currentIdempotencyKey = null;
    function newIdempotencyKey() {
        return 'pos-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 10);
    }

    function processCheckout() {
        let total = payableTotal();
        let payment = parseFloat($('#payment-input').val()) || 0;
        let method = $('#payment-method').val();

        if (payment < total) {
            Swal.fire({
                icon: 'warning',
                title: 'Uang Kurang!',
                text: 'Jumlah pembayaran kurang dari total tagihan.'
            });
            return;
        }

        Swal.fire({
            title: 'Konfirmasi Pembayaran?',
            text: `Metode: ${method.toUpperCase()} | Total: Rp ${total.toLocaleString('id-ID')} | Bayar: Rp ${payment.toLocaleString('id-ID')}`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Proses Transaksi'
        }).then(res => {
            if (res.isConfirmed) {
                if (!currentIdempotencyKey) currentIdempotencyKey = newIdempotencyKey();
                $.ajax({
                    url: "{{ route('pos.store') }}",
                    type: "POST",
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        items: cart,
                        payment_amount: payment,
                        payment_method: method,
                        idempotency_key: currentIdempotencyKey,
                        voucher_code: $('#voucher-code').val().trim(),
                        customer_id: $('#customer-id').val() || ''
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Transaksi Berhasil!',
                            text: `Kembalian: Rp ${response.change.toLocaleString('id-ID')}`,
                            confirmButtonText: 'Cetak Struk'
                        }).then(() => {
                            window.open("{{ url('/pos/receipt') }}/" + response.sale_id, '_blank', 'width=400,height=600');
                            clearCart();
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        let msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Gagal memproses transaksi.';
                        Swal.fire({
                            icon: 'error',
                            title: 'Transaksi Gagal / Stok Tidak Cukup!',
                            text: msg
                        });
                    }
                });
            }
        });
    }

    // Submit Petty Cash
    $('#form-petty-cash').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: "{{ route('shifts.petty_cash') }}",
            type: "POST",
            data: $(this).serialize(),
            success: function(res) {
                $('#modal-petty-cash').modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: res.message
                }).then(() => {
                    location.reload();
                });
            },
            error: function(xhr) {
                let msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Gagal mencatat kas operasional.';
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: msg
                });
            }
        });
    });
</script>
@endpush
