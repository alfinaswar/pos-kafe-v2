<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Menu {{ $table->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --primary: #ff6b35;
            --primary-dark: #e85522;
            --primary-light: #fff1eb;
            --accent: #ffc857;
            --bg: #faf7f2;
            --surface: #ffffff;
            --text: #1a1a1a;
            --text-muted: #6b6b6b;
            --border: #ececec;
            --success: #10b981;
            --danger: #ef4444;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, .04);
            --shadow: 0 4px 16px rgba(0, 0, 0, .06);
            --shadow-lg: 0 12px 32px rgba(0, 0, 0, .1);
            --radius: 16px;
            --radius-sm: 10px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
        }

        img {
            max-width: 100%;
            display: block;
        }

        button {
            font-family: inherit;
            cursor: pointer;
            border: none;
            background: none;
        }

        input,
        select,
        textarea {
            font-family: inherit;
            font-size: 14px;
        }

        /* ===== HEADER ===== */
        .hero {
            background: linear-gradient(135deg, #ff6b35 0%, #ff8c5a 100%);
            color: #fff;
            padding: 32px 20px 64px;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255, 255, 255, .15) 0%, transparent 70%);
            border-radius: 50%;
        }

        .hero-inner {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, .2);
            backdrop-filter: blur(10px);
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .hero-badge .dot {
            width: 6px;
            height: 6px;
            background: #4ade80;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: .5;
            }
        }

        .hero h1 {
            font-size: clamp(28px, 5vw, 40px);
            font-weight: 800;
            letter-spacing: -.02em;
            margin-bottom: 6px;
        }

        .hero p {
            opacity: .9;
            font-size: 14px;
            max-width: 500px;
        }

        /* ===== QUICK ACTIONS ===== */
        .quick-actions {
            max-width: 1200px;
            margin: -36px auto 0;
            padding: 0 20px;
            position: relative;
            z-index: 2;
        }

        .quick-actions-inner {
            background: var(--surface);
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            padding: 16px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .qa-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 14px;
            transition: all .2s;
        }

        .qa-btn svg {
            width: 18px;
            height: 18px;
        }

        .qa-btn.primary {
            background: var(--primary-light);
            color: var(--primary-dark);
        }

        .qa-btn.primary:hover {
            background: #ffe3d6;
        }

        .qa-btn.secondary {
            background: #f3f4f6;
            color: var(--text);
        }

        .qa-btn.secondary:hover {
            background: #e5e7eb;
        }

        /* ===== MAIN ===== */
        main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px 20px 140px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            margin: 24px 0 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title::before {
            content: '';
            width: 4px;
            height: 20px;
            background: var(--primary);
            border-radius: 2px;
        }

        /* ===== PRODUCT GRID ===== */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 16px;
        }

        .product-card {
            background: var(--surface);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            overflow: hidden;
            transition: all .25s;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            box-shadow: var(--shadow);
            transform: translateY(-2px);
        }

        .product-card.has-qty {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px var(--primary-light);
        }

        .product-header {
            padding: 16px 16px 12px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
        }

        .product-info {
            flex: 1;
            min-width: 0;
        }

        .product-name {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 4px;
            line-height: 1.3;
        }

        .product-detail {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 4px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-price {
            font-size: 16px;
            font-weight: 800;
            color: var(--primary-dark);
            white-space: nowrap;
        }

        /* Quantity stepper */
        .qty-stepper {
            display: flex;
            align-items: center;
            gap: 4px;
            background: var(--primary-light);
            border-radius: 999px;
            padding: 4px;
            margin: 0 16px 12px;
        }

        .qty-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--primary);
            color: #fff;
            font-size: 18px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform .15s;
        }

        .qty-btn:active {
            transform: scale(.9);
        }

        .qty-btn.minus {
            background: #fff;
            color: var(--primary);
            border: 1px solid var(--primary);
        }

        .qty-input {
            flex: 1;
            text-align: center;
            border: none;
            background: transparent;
            font-weight: 700;
            font-size: 15px;
            width: 40px;
            outline: none;
            color: var(--primary-dark);
            -moz-appearance: textfield;
        }

        .qty-input::-webkit-outer-spin-button,
        .qty-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        /* Modifiers */
        .modifiers-toggle {
            padding: 10px 16px;
            background: #fafafa;
            border-top: 1px dashed var(--border);
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            transition: background .2s;
        }

        .modifiers-toggle:hover {
            background: #f3f3f3;
        }

        .modifiers-toggle .chevron {
            transition: transform .2s;
        }

        .modifiers-toggle.open .chevron {
            transform: rotate(180deg);
        }

        .modifiers-panel {
            max-height: 0;
            overflow: hidden;
            transition: max-height .3s ease;
        }

        .modifiers-panel.open {
            max-height: 600px;
        }

        .modifiers-inner {
            padding: 14px 16px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .modifier-group label.modifier-label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: .05em;
            margin-bottom: 6px;
        }

        .chip-group {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .chip {
            position: relative;
        }

        .chip input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .chip span {
            display: inline-block;
            padding: 7px 12px;
            border: 1.5px solid var(--border);
            border-radius: 999px;
            font-size: 13px;
            font-weight: 500;
            color: var(--text);
            background: #fff;
            cursor: pointer;
            transition: all .15s;
        }

        .chip input:checked+span {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }

        .chip span:hover {
            border-color: var(--primary);
        }

        .notes-input {
            width: 100%;
            padding: 10px 12px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 13px;
            resize: none;
            transition: border-color .2s;
        }

        .notes-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        /* ===== ORDER SUMMARY BAR ===== */
        .order-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--surface);
            border-top: 1px solid var(--border);
            box-shadow: 0 -4px 20px rgba(0, 0, 0, .08);
            padding: 12px 20px;
            z-index: 50;
            transform: translateY(100%);
            transition: transform .3s;
        }

        .order-bar.visible {
            transform: translateY(0);
        }

        .order-bar-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .order-count {
            width: 44px;
            height: 44px;
            background: var(--primary);
            color: #fff;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 18px;
            flex-shrink: 0;
        }

        .order-info {
            flex: 1;
            min-width: 0;
        }

        .order-info .label {
            font-size: 12px;
            color: var(--text-muted);
        }

        .order-info .total {
            font-size: 16px;
            font-weight: 700;
        }

        .submit-btn {
            background: var(--primary);
            color: #fff;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all .2s;
            white-space: nowrap;
        }

        .submit-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        /* ===== ORDER NOTES ===== */
        .order-notes-card {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 16px;
            margin-top: 24px;
            border: 1px solid var(--border);
        }

        .order-notes-card label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .order-notes-card textarea {
            width: 100%;
            min-height: 80px;
            padding: 12px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            resize: vertical;
        }

        .order-notes-card textarea:focus {
            outline: none;
            border-color: var(--primary);
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }

        .empty-state svg {
            width: 64px;
            height: 64px;
            margin: 0 auto 16px;
            opacity: .4;
        }

        /* ===== STATUS BADGE ===== */
        .status-pill {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--surface);
            padding: 8px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 8px;
            z-index: 100;
            opacity: 0;
            transform: translateY(-10px);
            transition: all .3s;
        }

        .status-pill.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .status-pill .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--success);
            animation: pulse 1.5s infinite;
        }

        /* ===== TOAST ===== */
        .toast {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(-100px);
            background: var(--text);
            color: #fff;
            padding: 12px 20px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            box-shadow: var(--shadow-lg);
            z-index: 200;
            transition: transform .3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .toast.visible {
            transform: translateX(-50%) translateY(0);
        }

        .toast.success {
            background: var(--success);
        }

        .toast.error {
            background: var(--danger);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 640px) {
            .hero {
                padding: 24px 16px 56px;
            }

            .quick-actions {
                padding: 0 16px;
            }

            .quick-actions-inner {
                grid-template-columns: 1fr;
                padding: 12px;
            }

            main {
                padding: 20px 16px 140px;
            }

            .product-grid {
                grid-template-columns: 1fr;
            }

            .order-bar {
                padding: 10px 16px;
            }

            .submit-btn {
                padding: 12px 16px;
                font-size: 13px;
            }

            .status-pill {
                top: auto;
                bottom: 90px;
                right: 16px;
            }
        }

        @media (min-width: 641px) and (max-width: 1024px) {
            .product-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>

<body>
    <!-- Status Pill -->
    <div class="status-pill" id="statusPill">
        <span class="dot"></span>
        <span id="statusText">Memuat status...</span>
    </div>

    <!-- Toast -->
    <div class="toast" id="toast"></div>

    <!-- Hero Header -->
    <header class="hero">
        <div class="hero-inner">
            <div class="hero-badge">
                <span class="dot"></span>
                <span>Meja Aktif</span>
            </div>
            <h1>{{ $table->name }}</h1>
            <p>Silakan pilih menu favoritmu. Pembayaran dilakukan langsung di kasir ya ✨</p>
        </div>
    </header>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <div class="quick-actions-inner">
            <button type="button" class="qa-btn primary" onclick="sendTableRequest('call_cashier')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path
                        d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
                </svg>
                Panggil Kasir
            </button>
            <button type="button" class="qa-btn secondary" onclick="sendTableRequest('request_bill')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                    <polyline points="14 2 14 8 20 8" />
                    <line x1="9" y1="15" x2="15" y2="15" />
                </svg>
                Minta Bill
            </button>
        </div>
        </header>

        <main>
            <form method="POST" action="{{ route('table-orders.store', $table->qr_token) }}"
                onsubmit="return keepSelectedItems(this)" id="orderForm">
                @csrf

                <h2 class="section-title">Pilih Menu</h2>

                @forelse ($products as $product)
                    @if ($loop->first)
                        <div class="product-grid">
                    @endif

                    <div class="product-card" data-index="{{ $loop->index }}">
                        <div class="product-header">
                            <div class="product-info">
                                <div class="product-name">{{ $product->name }}</div>
                                <div class="product-price">Rp {{ number_format($product->price, 0, ',', '.') }}</div>
                                @if ($product->detail)
                                    <div class="product-detail">{{ $product->detail }}</div>
                                @endif
                            </div>
                        </div>

                        <!-- Quantity Stepper -->
                        <div class="qty-stepper">
                            <button type="button" class="qty-btn minus"
                                onclick="changeQty({{ $loop->index }}, -1)">−</button>
                            <input type="number" class="qty-input" name="items[{{ $loop->index }}][quantity]"
                                value="0" min="0" max="99" data-index="{{ $loop->index }}"
                                onchange="updateCardState({{ $loop->index }})">
                            <button type="button" class="qty-btn plus"
                                onclick="changeQty({{ $loop->index }}, 1)">+</button>
                        </div>

                        <!-- Modifiers Toggle -->
                        <div class="modifiers-toggle" onclick="toggleModifiers(this)">
                            <span>🎨 Kustomisasi pesanan</span>
                            <svg class="chevron" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9" />
                            </svg>
                        </div>

                        <!-- Modifiers Panel -->
                        <div class="modifiers-panel">
                            <div class="modifiers-inner">
                                <input type="hidden" name="items[{{ $loop->index }}][product_id]"
                                    value="{{ $product->id }}">

                                <div class="modifier-group">
                                    <label class="modifier-label">Ukuran</label>
                                    <div class="chip-group">
                                        <label class="chip"><input type="radio"
                                                name="items[{{ $loop->index }}][modifiers][size]" value="regular"
                                                checked><span>Regular</span></label>
                                        <label class="chip"><input type="radio"
                                                name="items[{{ $loop->index }}][modifiers][size]"
                                                value="large"><span>Large</span></label>
                                    </div>
                                </div>

                                <div class="modifier-group">
                                    <label class="modifier-label">Level Gula</label>
                                    <div class="chip-group">
                                        <label class="chip"><input type="radio"
                                                name="items[{{ $loop->index }}][modifiers][sugar]" value="normal"
                                                checked><span>Normal</span></label>
                                        <label class="chip"><input type="radio"
                                                name="items[{{ $loop->index }}][modifiers][sugar]"
                                                value="less"><span>Less</span></label>
                                        <label class="chip"><input type="radio"
                                                name="items[{{ $loop->index }}][modifiers][sugar]"
                                                value="none"><span>Tanpa</span></label>
                                    </div>
                                </div>

                                <div class="modifier-group">
                                    <label class="modifier-label">Es</label>
                                    <div class="chip-group">
                                        <label class="chip"><input type="radio"
                                                name="items[{{ $loop->index }}][modifiers][ice]" value="normal"
                                                checked><span>Normal</span></label>
                                        <label class="chip"><input type="radio"
                                                name="items[{{ $loop->index }}][modifiers][ice]"
                                                value="less"><span>Less</span></label>
                                        <label class="chip"><input type="radio"
                                                name="items[{{ $loop->index }}][modifiers][ice]"
                                                value="none"><span>Tanpa</span></label>
                                    </div>
                                </div>

                                <div class="modifier-group">
                                    <label class="modifier-label">Topping (+Rp 3-4rb)</label>
                                    <div class="chip-group">
                                        <label class="chip"><input type="checkbox"
                                                name="items[{{ $loop->index }}][modifiers][toppings][]"
                                                value="boba"><span>🧋 Boba +3rb</span></label>
                                        <label class="chip"><input type="checkbox"
                                                name="items[{{ $loop->index }}][modifiers][toppings][]"
                                                value="cheese"><span>🧀 Cheese +4rb</span></label>
                                    </div>
                                </div>

                                <div class="modifier-group">
                                    <label class="modifier-label">Catatan Item</label>
                                    <input type="text" class="notes-input"
                                        name="items[{{ $loop->index }}][notes]" maxlength="500"
                                        placeholder="Contoh: jangan terlalu manis...">
                                </div>
                            </div>
                        </div>
                    </div>

                    @if ($loop->last)
    </div>
    @endif
@empty
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
            stroke-linejoin="round">
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="8" x2="12" y2="12" />
            <line x1="12" y1="16" x2="12.01" y2="16" />
        </svg>
        <h3>Menu belum tersedia</h3>
        <p>Yah, menu masih dalam persiapan. Coba lagi nanti ya 🙏</p>
    </div>
    @endforelse

    <!-- Order Notes -->
    <div class="order-notes-card">
        <label for="orderNotes">📝 Catatan untuk pesanan ini (opsional)</label>
        <textarea id="orderNotes" name="notes" maxlength="1000" placeholder="Ada request khusus? Tulis di sini..."></textarea>
    </div>
    </form>
    </main>

    <!-- Sticky Order Bar -->
    <div class="order-bar" id="orderBar">
        <div class="order-bar-inner">
            <div class="order-count" id="orderCount">0</div>
            <div class="order-info">
                <div class="label">Item dipilih</div>
                <div class="total" id="orderTotal">Rp 0</div>
            </div>
            <button type="submit" form="orderForm" class="submit-btn">
                Kirim Pesanan
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="5" y1="12" x2="19" y2="12" />
                    <polyline points="12 5 19 12 12 19" />
                </svg>
            </button>
        </div>
    </div>

    <script>
        // ===== QUANTITY STEPPER =====
        function changeQty(index, delta) {
            const input = document.querySelector(`input[data-index="${index}"]`);
            let val = parseInt(input.value) || 0;
            val = Math.max(0, Math.min(99, val + delta));
            input.value = val;
            updateCardState(index);
        }

        function updateCardState(index) {
            const input = document.querySelector(`input[data-index="${index}"]`);
            const card = document.querySelector(`.product-card[data-index="${index}"]`);
            const qty = parseInt(input.value) || 0;
            card.classList.toggle('has-qty', qty > 0);
            updateOrderBar();
        }

        // ===== MODIFIERS TOGGLE =====
        function toggleModifiers(toggle) {
            toggle.classList.toggle('open');
            toggle.nextElementSibling.classList.toggle('open');
        }

        // ===== ORDER BAR =====
        function updateOrderBar() {
            let count = 0;
            let total = 0;
            document.querySelectorAll('.product-card').forEach(card => {
                const input = card.querySelector('.qty-input');
                const qty = parseInt(input.value) || 0;
                if (qty > 0) {
                    count += qty;
                    const priceText = card.querySelector('.product-price').textContent;
                    const price = parseInt(priceText.replace(/[^0-9]/g, '')) || 0;
                    total += price * qty;
                }
            });
            const bar = document.getElementById('orderBar');
            document.getElementById('orderCount').textContent = count;
            document.getElementById('orderTotal').textContent = 'Rp ' + total.toLocaleString('id-ID');
            bar.classList.toggle('visible', count > 0);
        }

        // ===== FORM SUBMIT =====
        function keepSelectedItems(form) {
            form.querySelectorAll('input[name^="items["][name$="][quantity]"]').forEach(function(input) {
                const card = input.closest('.product-card');
                card.querySelectorAll('input, select, textarea').forEach(function(field) {
                    if (field !== input && input.value === '0') field.disabled = true;
                });
                if (input.value === '0') input.disabled = true;
            });
            return true;
        }

        // ===== TABLE REQUESTS =====
        function sendTableRequest(type) {
            const label = type === 'call_cashier' ? 'Panggil Kasir' : 'Minta Bill';
            fetch('{{ route('table-requests.store', $tableSession->session_token) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        type: type
                    })
                })
                .then(r => {
                    if (r.ok) {
                        showToast(label + ' berhasil dikirim ✨', 'success');
                    } else {
                        showToast('Sesi meja tidak tersedia', 'error');
                    }
                })
                .catch(() => showToast('Gagal mengirim permintaan', 'error'));
        }

        // ===== TOAST =====
        function showToast(message, type = '') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast visible ' + type;
            setTimeout(() => toast.classList.remove('visible'), 3000);
        }

        // ===== STATUS POLLING =====
        setInterval(function() {
            fetch('{{ route('table-orders.status.customer', $tableSession->session_token) }}')
                .then(r => r.json())
                .then(data => {
                    const pill = document.getElementById('statusPill');
                    const text = document.getElementById('statusText');
                    if (data.orders && data.orders.length) {
                        const status = data.orders[0].status;
                        const map = {
                            'pending': 'Menunggu konfirmasi',
                            'accepted': 'Sedang disiapkan',
                            'preparing': 'Sedang disiapkan 👨‍🍳',
                            'ready': 'Siap diantar 🎉',
                            'completed': 'Selesai ✅'
                        };
                        text.textContent = map[status] || status.toUpperCase();
                        pill.classList.add('visible');
                        document.title = status.toUpperCase() + ' - {{ $table->name }}';
                    } else {
                        pill.classList.remove('visible');
                        document.title = '{{ $table->name }}';
                    }
                })
                .catch(() => {});
        }, 10000);

        // Listen to qty input changes
        document.querySelectorAll('.qty-input').forEach(input => {
            input.addEventListener('input', e => {
                const idx = e.target.dataset.index;
                updateCardState(idx);
            });
        });
    </script>
</body>

</html>
