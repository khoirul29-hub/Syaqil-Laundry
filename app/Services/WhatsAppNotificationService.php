<?php

namespace App\Services;

use App\Models\BusinessSetting;
use App\Models\Notification;
use App\Models\Order;
use App\Models\WhatsappLog;
use Illuminate\Support\Facades\Http;
use Throwable;

class WhatsAppNotificationService
{
    /**
     * Kirim notifikasi bukti transaksi/nota setelah order dibuat.
     */
    public function sendReceiptNotification(Order $order): array
    {
        $order->loadMissing('items');

        if (empty($order->public_token)) {
            $order->public_token = bin2hex(random_bytes(16));
            $order->saveQuietly();
        }

        $settings = BusinessSetting::allAsArray();
        $storeName = $settings['name'] ?? config('app.business_name', 'Syaqil Laundry');
        $storeAddress = $settings['address'] ?? config('app.business_address', 'Jl. Merdeka No. 45, Padang');

        $receiptUrl = $this->getPublicReceiptUrl($order->public_token);

        $phone = (string)($order->customer_phone ?? '');
        $createdAt = $order->created_at ? $order->created_at->format('d M Y H:i') : now()->format('d M Y H:i');
        $due = $order->due_at ? $order->due_at->format('d M Y H:i') : 'Sesuai antrean';

        $itemLines = [];
        foreach ($order->items as $item) {
            $qty = rtrim(rtrim(number_format((float)$item->quantity, 2, ',', '.'), '0'), ',');
            $priceFormatted = 'Rp ' . number_format($item->price, 0, ',', '.');
            $subtotalFormatted = 'Rp ' . number_format($item->subtotal, 0, ',', '.');
            $categoryText = $item->category ? " ({$item->category})" : '';
            $itemLines[] = "• {$item->service_name}{$categoryText}: {$qty} {$item->unit} × {$priceFormatted} = {$subtotalFormatted}";
        }
        $itemsText = !empty($itemLines) ? implode("\n", $itemLines) : "• Layanan laundry: Rp " . number_format($order->subtotal, 0, ',', '.');

        $subtotalFormatted = 'Rp ' . number_format($order->subtotal, 0, ',', '.');
        $totalFormatted = 'Rp ' . number_format($order->total, 0, ',', '.');
        $methodLabel = match($order->method) {
            'qris' => 'QRIS',
            'transfer' => 'Transfer Bank',
            'later' => 'Bayar Nanti',
            default => 'Tunai',
        };

        $isUnpaid = ($order->payment_status === 'unpaid') || ($order->method === 'later');
        $paymentStatusText = $isUnpaid 
            ? "• Status Pembayaran: *BELUM BAYAR (Bayar Nanti)*" 
            : "• Status Pembayaran: *Lunas ({$methodLabel})*";

        $message = "*{$storeName}*\n"
            . "{$storeAddress}\n\n"
            . "Halo *{$order->customer_name}*,\n"
            . "Terima kasih telah mempercayakan cucian Anda di *{$storeName}*.\n\n"
            . "Berikut rincian nota transaksi Anda:\n"
            . "• No. Nota: *{$order->number}*\n"
            . "• Pelanggan: *{$order->customer_name}*\n"
            . "• Tanggal Masuk: {$createdAt}\n"
            . "• Estimasi Selesai: *{$due}*\n\n"
            . "🧺 *Rincian Layanan:*\n"
            . "{$itemsText}\n\n"
            . "💵 *Rincian Pembayaran:*\n"
            . "• Total: *{$totalFormatted}*\n"
            . "{$paymentStatusText}\n\n"
            . "📱 *Tautan Nota Digital:*\n"
            . "{$receiptUrl}\n\n"
            . "Terima kasih telah mempercayakan cucian Anda kepada kami!\n"
            . "_{$storeName} - Bersih. Cepat. Beres._";

        return $this->dispatchMessage($order, $phone, 'receipt', $message);
    }

    /**
     * Kirim notifikasi bahwa cucian telah selesai dan siap diambil.
     */
    public function sendStatusReadyNotification(Order $order): array
    {
        $phone = (string)($order->customer_phone ?? '');
        $totalFormatted = 'Rp ' . number_format($order->total, 0, ',', '.');

        if (empty($order->public_token)) {
            $order->public_token = bin2hex(random_bytes(16));
            $order->saveQuietly();
        }

        $settings = BusinessSetting::allAsArray();
        $storeName = $settings['name'] ?? config('app.business_name', 'Syaqil Laundry');
        $storeAddress = $settings['address'] ?? config('app.business_address', 'Jl. Merdeka No. 45, Padang');

        $receiptUrl = $this->getPublicReceiptUrl($order->public_token);

        $isUnpaid = ($order->payment_status === 'unpaid') || ($order->method === 'later');
        $paymentNotice = $isUnpaid ? "• Status Pembayaran: *BELUM BAYAR (Silakan bayar saat ambil)*\n" : "• Status Pembayaran: *Lunas*\n";

        $message = "*{$storeName}*\n"
            . "{$storeAddress}\n\n"
            . "Halo *{$order->customer_name}*,\n\n"
            . "Cucian Anda di *{$storeName}* dengan No. Nota *{$order->number}* sudah *SELESAI* dan siap diambil!\n"
            . "• Total: *{$totalFormatted}*\n"
            . "• Status: *Siap Diambil*\n"
            . $paymentNotice . "\n"
            . "📱 *Tautan Nota Digital:*\n"
            . "{$receiptUrl}\n\n"
            . "Silakan tunjukkan nota ini saat pengambilan di outlet kami.\n"
            . "Terima kasih!\n"
            . "_{$storeName} - Bersih. Cepat. Beres._";

        return $this->dispatchMessage($order, $phone, 'status_ready', $message);
    }

    /**
     * Resilient message dispatcher:
     * - Memproses payload ke gateway jika terhubung.
     * - Menangani kegagalan koneksi/timeout tanpa membatalkan order.
     * - Mencatat status jujur ke whatsapp_logs dan notifications.
     * - Menyediakan URL fallback wa.me langsung.
     */
    public function dispatchMessage(Order $order, string $phone, string $type, string $message): array
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '62' . substr($cleanPhone, 1);
        } elseif (str_starts_with($cleanPhone, '8')) {
            $cleanPhone = '62' . $cleanPhone;
        }

        $isValidPhone = !empty($cleanPhone) && (bool)preg_match('/^628[1-9][0-9]{7,11}$/D', $cleanPhone);
        $waMeUrl = !empty($cleanPhone) ? "https://wa.me/{$cleanPhone}?text=" . rawurlencode($message) : null;

        $settings = BusinessSetting::allAsArray();
        $gatewayEnabled = !empty($settings['whatsapp_enabled']) && $settings['whatsapp_enabled'] === '1';
        $gatewayUrl = trim($settings['whatsapp_gateway_url'] ?? '');
        $apiKey = trim($settings['whatsapp_api_key'] ?? '');

        // Kasus 1: Gateway belum aktif/belum dikonfigurasi
        if (!$gatewayEnabled || empty($gatewayUrl)) {
            $log = WhatsappLog::create([
                'order_id' => $order->id,
                'phone' => $phone,
                'type' => $type,
                'payload' => $message,
                'status' => 'not_configured',
                'error_message' => 'WhatsApp gateway belum dikonfigurasi. Hubungi pelanggan secara manual.',
                'sent_at' => null,
                'created_at' => now(),
            ]);

            Notification::create([
                'order_id' => $order->id,
                'status' => 'not_configured',
                'message' => 'WhatsApp belum terhubung. Hubungi pelanggan secara manual.',
                'created_at' => now(),
            ]);

            return [
                'status' => 'not_configured',
                'message' => 'WhatsApp belum terhubung.',
                'wa_url' => $waMeUrl,
                'phone_valid' => $isValidPhone,
                'clean_phone' => $cleanPhone,
                'log_id' => $log->id,
            ];
        }

        // Kasus 2: Gateway dikonfigurasi, kirim HTTP request dengan timeout aman
        try {
            $response = Http::timeout(4)->withHeaders([
                'Authorization' => $apiKey ? "Bearer {$apiKey}" : '',
                'Accept' => 'application/json',
            ])->post($gatewayUrl, [
                'phone' => $cleanPhone,
                'message' => $message,
                'order_number' => $order->number,
            ]);

            if ($response->successful()) {
                $log = WhatsappLog::create([
                    'order_id' => $order->id,
                    'phone' => $phone,
                    'type' => $type,
                    'payload' => $message,
                    'status' => 'sent',
                    'error_message' => null,
                    'sent_at' => now(),
                    'created_at' => now(),
                ]);

                Notification::create([
                    'order_id' => $order->id,
                    'status' => 'sent',
                    'message' => 'Pesan WhatsApp berhasil dikirim.',
                    'created_at' => now(),
                ]);

                return [
                    'status' => 'sent',
                    'message' => 'Notifikasi WhatsApp berhasil dikirim.',
                    'wa_url' => $waMeUrl,
                    'phone_valid' => $isValidPhone,
                    'clean_phone' => $cleanPhone,
                    'log_id' => $log->id,
                ];
            }

            // HTTP error response dari gateway
            $errText = "Gateway merespons HTTP {$response->status()}: " . substr($response->body(), 0, 150);
            $log = WhatsappLog::create([
                'order_id' => $order->id,
                'phone' => $phone,
                'type' => $type,
                'payload' => $message,
                'status' => 'failed',
                'error_message' => $errText,
                'sent_at' => null,
                'created_at' => now(),
            ]);

            Notification::create([
                'order_id' => $order->id,
                'status' => 'failed',
                'message' => 'Gagal mengirim WhatsApp: ' . $errText,
                'created_at' => now(),
            ]);

            return [
                'status' => 'failed',
                'message' => $errText,
                'wa_url' => $waMeUrl,
                'phone_valid' => $isValidPhone,
                'clean_phone' => $cleanPhone,
                'log_id' => $log->id,
            ];
        } catch (Throwable $e) {
            // Network timeout / DNS / connection error
            $errText = "Koneksi gateway gagal: " . $e->getMessage();
            $log = WhatsappLog::create([
                'order_id' => $order->id,
                'phone' => $phone,
                'type' => $type,
                'payload' => $message,
                'status' => 'failed',
                'error_message' => $errText,
                'sent_at' => null,
                'created_at' => now(),
            ]);

            Notification::create([
                'order_id' => $order->id,
                'status' => 'failed',
                'message' => $errText,
                'created_at' => now(),
            ]);

            return [
                'status' => 'failed',
                'message' => $errText,
                'wa_url' => $waMeUrl,
                'phone_valid' => $isValidPhone,
                'clean_phone' => $cleanPhone,
                'log_id' => $log->id,
            ];
        }
    }

    /**
     * Coba ulang pengiriman pesan WhatsApp berdasarkan log_id.
     */
    public function retryLog(int $logId): array
    {
        $log = WhatsappLog::with('order')->find($logId);
        if (!$log || !$log->order) {
            return ['status' => 'failed', 'message' => 'Log pengiriman tidak ditemukan.'];
        }

        return $this->dispatchMessage($log->order, $log->phone, $log->type, $log->payload);
    }

    /**
     * Membentuk URL nota digital publik yang aman dan dapat dibuka pelanggan melalui internet.
     * Mengutamakan BASE_URL produksi dari environment (PUBLIC_RECEIPT_BASE_URL) tanpa hardcode,
     * lalu fallback ke konfigurasi toko, APP_URL, atau request host saat development.
     */
    public function getPublicReceiptUrl(string $token): string
    {
        // 1. Prioritas utama: konfigurasi environment produksi PUBLIC_RECEIPT_BASE_URL
        $envPublicBase = trim((string)config('app.public_receipt_base_url', env('PUBLIC_RECEIPT_BASE_URL', '')));
        if (!empty($envPublicBase)) {
            return rtrim($envPublicBase, '/') . "/nota/{$token}";
        }

        // 2. Prioritas kedua: pengaturan toko publik di database jika dikustomisasi oleh owner
        $settings = BusinessSetting::allAsArray();
        if (!empty($settings['public_receipt_base_url'])) {
            return rtrim($settings['public_receipt_base_url'], '/') . "/nota/{$token}";
        }

        // 3. Prioritas ketiga: APP_URL jika disetel ke domain publik non-localhost (misal saat deployment)
        $appUrl = trim((string)config('app.url', ''));
        if (!empty($appUrl) && !str_contains($appUrl, '127.0.0.1') && !str_contains($appUrl, 'localhost')) {
            return rtrim($appUrl, '/') . "/nota/{$token}";
        }

        // 4. Fallback saat development lokal (request host atau default app url)
        $requestHost = null;
        if (app()->bound('request')) {
            try {
                $requestHost = request()?->getSchemeAndHttpHost();
            } catch (\Throwable) {
                $requestHost = null;
            }
        }
        if (!empty($requestHost)) {
            return rtrim($requestHost, '/') . "/nota/{$token}";
        }

        return rtrim($appUrl ?: 'http://127.0.0.1:8000', '/') . "/nota/{$token}";
    }
}
