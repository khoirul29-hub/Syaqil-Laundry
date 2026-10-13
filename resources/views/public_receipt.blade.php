<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Digital #{{ $order->number }} - {{ $store['name'] }}</title>
    <meta name="description" content="Bukti transaksi dan nota digital {{ $order->number }} di {{ $store['name'] }}.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Mono:wght@700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0755ee;
            --primary-dark: #003db3;
            --primary-light: #ebf2ff;
            --success: #10b981;
            --success-light: #ecfdf5;
            --warning: #f59e0b;
            --warning-light: #fffbeb;
            --info: #0284c7;
            --info-light: #f0f9ff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --bg-page: #f1f5f9;
            --bg-card: #ffffff;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-page);
            color: var(--text-main);
            min-height: 100vh;
            padding: 24px 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
        }

        .receipt-container {
            width: 100%;
            max-width: 520px;
            background: var(--bg-card);
            border-radius: 20px;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.04);
            border: 1px solid var(--border);
            overflow: hidden;
            position: relative;
        }

        .receipt-header {
            background: linear-gradient(135deg, #0755ee 0%, #003db3 100%);
            color: #ffffff;
            padding: 28px 24px 24px;
            text-align: center;
            position: relative;
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(8px);
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-bottom: 12px;
            border: 1px solid rgba(255, 255, 255, 0.25);
        }

        .receipt-header h1 {
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 4px;
            letter-spacing: -0.02em;
        }

        .receipt-header p {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.85);
            line-height: 1.4;
            max-width: 360px;
            margin: 0 auto;
        }

        .status-tracker-card {
            background: #ffffff;
            margin: -14px 20px 20px;
            border-radius: 14px;
            padding: 16px 18px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
            border: 1px solid var(--border);
            position: relative;
            z-index: 2;
        }

        .tracker-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
        }

        .tracker-title {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
        }

        .status-diterima { background: var(--info-light); color: var(--info); }
        .status-diproses { background: var(--warning-light); color: #d97706; }
        .status-selesai { background: var(--success-light); color: var(--success); }
        .status-diambil { background: #f1f5f9; color: #475569; }

        .steps-bar {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px;
            text-align: center;
        }

        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }

        .step-indicator {
            width: 100%;
            height: 6px;
            border-radius: 9999px;
            background: #e2e8f0;
            transition: background 0.3s ease;
        }

        .step-item.active .step-indicator {
            background: var(--primary);
        }

        .step-item.completed .step-indicator {
            background: var(--success);
        }

        .step-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
        }

        .step-item.active .step-label,
        .step-item.completed .step-label {
            color: var(--text-main);
        }

        .receipt-body {
            padding: 0 24px 24px;
        }

        .order-meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            background: #f8fafc;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 20px;
            border: 1px solid #edf2f7;
        }

        .meta-group {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .meta-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .meta-value {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-main);
            word-break: break-word;
        }

        .meta-value.mono {
            font-family: 'Space Mono', monospace;
            color: var(--primary);
            font-size: 13px;
        }

        .section-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-main);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e2e8f0;
        }

        .items-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 20px;
        }

        .item-card {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 10px 12px;
            background: #ffffff;
            border-radius: 10px;
            border: 1px solid #eef2f6;
        }

        .item-card:hover {
            border-color: #cbd5e1;
        }

        .item-info {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .item-name {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-main);
        }

        .item-sub {
            font-size: 12px;
            color: var(--text-muted);
        }

        .item-price {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-main);
            text-align: right;
            white-space: nowrap;
        }

        .perfume-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fdf2f8;
            color: #db2777;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid #fce7f3;
            margin-bottom: 20px;
            width: fit-content;
        }

        .notes-card {
            background: #f8fafc;
            border-left: 3px solid #94a3b8;
            padding: 10px 14px;
            border-radius: 0 8px 8px 0;
            font-size: 12px;
            color: #334155;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .payment-summary {
            background: #f8fafc;
            border-radius: 12px;
            padding: 16px;
            border: 1px solid #e2e8f0;
            margin-bottom: 24px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .summary-row.discount {
            color: #dc2626;
            font-weight: 600;
        }

        .summary-divider {
            height: 1px;
            background: #e2e8f0;
            margin: 10px 0;
        }

        .summary-row.total {
            font-size: 16px;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 0;
        }

        .summary-row.total span:last-child {
            color: var(--primary);
            font-size: 18px;
        }

        .payment-status-badge {
            margin-top: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--success-light);
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
        }

        .actions-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 20px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 12px 18px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            border: none;
        }

        .btn-primary {
            background: var(--primary);
            color: #ffffff;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-success {
            background: #25d366;
            color: #ffffff;
        }

        .btn-success:hover {
            background: #1eb855;
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
        }

        .receipt-footer {
            text-align: center;
            padding-top: 16px;
            border-top: 1px dashed var(--border);
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .receipt-footer b {
            color: var(--text-main);
        }

        .verified-stamp {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: var(--success);
            font-size: 11px;
            font-weight: 700;
            margin-top: 6px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .receipt-container {
                box-shadow: none;
                border: none;
                max-width: 100%;
            }
            .actions-group,
            .btn {
                display: none !important;
            }
            .status-tracker-card {
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <!-- Header -->
        <header class="receipt-header">
            <div class="brand-badge">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                Nota Resmi Pelanggan
            </div>
            <h1>{{ $store['name'] }}</h1>
            <p>{{ $store['address'] }} &bull; {{ $store['phone'] }}</p>
        </header>

        <!-- Status Tracker -->
        @php
            $statuses = ['Diterima', 'Diproses', 'Selesai', 'Diambil'];
            $currentIndex = array_search($order->status, $statuses);
            if ($currentIndex === false) $currentIndex = 0;
            $statusClass = 'status-' . strtolower($order->status);
        @endphp
        <div class="status-tracker-card">
            <div class="tracker-header">
                <span class="tracker-title">Status Cucian</span>
                <span class="status-pill {{ $statusClass }}">
                    @if($order->status === 'Selesai')
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                        Siap Diambil
                    @else
                        {{ $order->status }}
                    @endif
                </span>
            </div>
            <div class="steps-bar">
                @foreach($statuses as $idx => $st)
                    <div class="step-item {{ $idx < $currentIndex ? 'completed' : ($idx === $currentIndex ? 'active' : '') }}">
                        <div class="step-indicator"></div>
                        <span class="step-label">{{ $st === 'Selesai' ? 'Siap' : $st }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="receipt-body">
            <!-- Metadata Grid -->
            <div class="order-meta-grid">
                <div class="meta-group">
                    <span class="meta-label">No. Nota</span>
                    <span class="meta-value mono">{{ $order->number }}</span>
                </div>
                <div class="meta-group">
                    <span class="meta-label">Nama Pelanggan</span>
                    <span class="meta-value">{{ $order->customer_name }}</span>
                </div>
                <div class="meta-group">
                    <span class="meta-label">Tanggal Masuk</span>
                    <span class="meta-value">{{ $order->created_at ? $order->created_at->format('d M Y, H:i') : '-' }}</span>
                </div>
                <div class="meta-group">
                    <span class="meta-label">Estimasi Selesai</span>
                    <span class="meta-value">{{ $order->due_at ? $order->due_at->format('d M Y, H:i') : 'Sesuai antrean' }}</span>
                </div>
            </div>

            <!-- Items -->
            <div class="section-title">Rincian Layanan Cucian</div>
            <div class="items-list">
                @foreach($order->items as $item)
                    @php
                        $qtyFormatted = rtrim(rtrim(number_format((float)$item->quantity, 2, ',', '.'), '0'), ',');
                    @endphp
                    <div class="item-card">
                        <div class="item-info">
                            <span class="item-name">{{ $item->service_name }}</span>
                            <span class="item-sub">
                                {{ $item->category }} &bull; {{ $qtyFormatted }} {{ $item->unit }} &times; Rp {{ number_format($item->price, 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="item-price">
                            Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Catatan -->
            @if(!empty($order->notes))
                <div class="notes-card">
                    <strong>Catatan Khusus:</strong><br>
                    {{ $order->notes }}
                </div>
            @endif

            <!-- Payment Summary -->
            <div class="section-title">Rincian Pembayaran</div>
            <div class="payment-summary">
                <div class="summary-row total">
                    <span>Total Pembayaran</span>
                    <span>Rp {{ number_format($order->total, 0, ',', '.') }}</span>
                </div>
                @php
                    $isUnpaid = ($order->payment_status === 'unpaid') || ($order->method === 'later');
                @endphp
                @if($isUnpaid)
                    <div class="payment-status-badge" style="background:#fffbeb;border-color:#fde68a;color:#b45309;">
                        <span>Metode: Bayar Nanti</span>
                        <span>&#9888; BELUM BAYAR</span>
                    </div>
                @else
                    <div class="payment-status-badge">
                        <span>Metode: {{ ucfirst($order->method) }}</span>
                        <span>&#10003; LUNAS</span>
                    </div>
                @endif
            </div>

            <!-- Action Buttons -->
            <div class="actions-group">
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    Cetak / Simpan Nota (PDF)
                </button>
                @php
                    $cleanStorePhone = preg_replace('/[^0-9]/', '', $store['phone'] ?? '');
                    if (str_starts_with($cleanStorePhone, '0')) $cleanStorePhone = '62' . substr($cleanStorePhone, 1);
                    $waStoreUrl = "https://wa.me/{$cleanStorePhone}?text=" . rawurlencode("Halo {$store['name']}, saya ingin menanyakan pesanan #{$order->number}.");
                @endphp
                <a href="{{ $waStoreUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-success">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                    Hubungi Laundry via WhatsApp
                </a>
            </div>

            <!-- Footer -->
            <footer class="receipt-footer">
                <p>Terima kasih telah mempercayakan pakaian Anda kepada <b>{{ $store['name'] }}</b>.</p>
                <p>Tunjukkan nota digital ini saat pengambilan pakaian di outlet kami.</p>
                <div class="verified-stamp">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    Tautan Nota Terverifikasi Resmi
                </div>
            </footer>
        </div>
    </div>
</body>
</html>
