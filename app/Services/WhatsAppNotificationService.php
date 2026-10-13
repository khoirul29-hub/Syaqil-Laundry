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
        $phone = $order->customer_phone;
        $totalFormatted = 'Rp ' . number_format($order->total, 0, ',', '.');
        $due = $order->due_at ? $order->due_at->format('d M Y H:i') : 'Sesuai antrean';

        $message = "Halo *{$order->customer_name}*,\n\n"
            . "Terima kasih telah mencuci di *Syaqil Laundry*!\n"
            . "Pesanan Anda telah kami terima:\n"
            . "• No. Nota: *{$order->number}*\n"
            . "• Total: *{$totalFormatted}*\n"
            . "• Pembayaran: *" . ucfirst($order->method) . " (Lunas)*\n"
            . "• Estimasi Selesai: *{$due}*\n\n"
            . "Kami akan mengabari Anda kembali saat cucian siap diambil.\n"
            . "_Syaqil Laundry - Bersih. Cepat. Beres._";

        return $this->dispatchMessage($order, $phone, 'receipt', $message);
    }

    /**
     * Kirim notifikasi bahwa cucian telah selesai dan siap diambil.
     */
    public function sendStatusReadyNotification(Order $order): array
    {
        $phone = $order->customer_phone;
        $totalFormatted = 'Rp ' . number_format($order->total, 0, ',', '.');

        $message = "Halo *{$order->customer_name}*,\n\n"
            . "Cucian Anda di *Syaqil Laundry* dengan No. Nota *{$order->number}* sudah *SELESAI* dan siap diambil!\n"
            . "• Total: *{$totalFormatted}*\n"
            . "• Status: *Siap Diambil*\n\n"
            . "Silakan tunjukkan nota ini saat pengambilan di outlet kami.\n"
            . "Terima kasih!\n"
            . "_Syaqil Laundry - Bersih. Cepat. Beres._";

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
        }

        $waMeUrl = "https://wa.me/{$cleanPhone}?text=" . rawurlencode($message);

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
