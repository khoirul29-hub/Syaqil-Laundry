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

        $baseUrl = request()?->getSchemeAndHttpHost() ?: rtrim(config('app.url', 'http://127.0.0.1:8000'), '/');
        $receiptUrl = "{$baseUrl}/nota/{$order->public_token}";

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

        $perfumeLine = '';
        if (!empty($order->perfume) && $order->perfume !== '-' && strtolower($order->perfume) !== 'tanpa parfum') {
            $perfumeLine = "🌸 *Parfum:* " . $order->perfume . "\n";
        }

        $subtotalFormatted = 'Rp ' . number_format($order->subtotal, 0, ',', '.');
        $totalFormatted = 'Rp ' . number_format($order->total, 0, ',', '.');
        $methodLabel = match($order->method) {
            'qris' => 'QRIS',
            'transfer' => 'Transfer Bank',
            default => 'Tunai',
        };

        $discountText = '';
        if ((int)$order->discount > 0) {
            $discountFormatted = 'Rp ' . number_format($order->discount, 0, ',', '.');
            $discountPercent = $order->discount_percent ? " ({$order->discount_percent}%)" : '';
            $discountText = "• Diskon{$discountPercent}: -{$discountFormatted}\n";
        }

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
            . "{$itemsText}\n"
            . ($perfumeLine ? "{$perfumeLine}\n" : "\n")
            . "💵 *Rincian Pembayaran:*\n"
            . "• Subtotal: {$subtotalFormatted}\n"
            . $discountText
            . "• *Total:* *{$totalFormatted}*\n"
            . "• Status Pembayaran: *Lunas ({$methodLabel})*\n\n"
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

        $baseUrl = request()?->getSchemeAndHttpHost() ?: rtrim(config('app.url', 'http://127.0.0.1:8000'), '/');
        $receiptUrl = "{$baseUrl}/nota/{$order->public_token}";

        $message = "*{$storeName}*\n"
            . "{$storeAddress}\n\n"
            . "Halo *{$order->customer_name}*,\n\n"
            . "Cucian Anda di *{$storeName}* dengan No. Nota *{$order->number}* sudah *SELESAI* dan siap diambil!\n"
            . "• Total: *{$totalFormatted}*\n"
            . "• Status: *Siap Diambil*\n\n"
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
}
