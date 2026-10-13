<?php

declare(strict_types=1);

/**
 * Test Suite Modul 5: Resiliensi Notifikasi WhatsApp & Integrasi Struk/Resi
 * Syaqil Laundry (LaundryFlow – Syaqil Laundry Ekspres)
 */

$baseDir = dirname(__DIR__, 2);
require $baseDir . '/vendor/autoload.php';

use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use App\Models\WhatsappLog;
use Illuminate\Http\Request;

$passed = 0;
$failed = 0;

function assertTest(string $desc, bool $condition, string $detail = ''): void {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "PASS: {$desc}\n";
    } else {
        $failed++;
        echo "FAIL: {$desc}" . ($detail ? " ({$detail})" : "") . "\n";
    }
}

function callApp(string $uri, string $method = 'GET', array $data = [], array $cookies = [], array $headers = []): array {
    global $baseDir;
    $app = require $baseDir . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $kernel->bootstrap();

    $server = array_merge([
        'HTTP_ACCEPT' => 'application/json',
        'REMOTE_ADDR' => '127.0.0.1',
    ], $headers);

    $content = $data ? json_encode($data) : null;
    if ($content) {
        $server['CONTENT_TYPE'] = 'application/json';
    }

    $req = Request::create($uri, $method, [], $cookies, [], $server, $content);
    $res = $kernel->handle($req);
    $kernel->terminate($req, $res);

    $responseCookies = [];
    foreach ($res->headers->getCookies() as $c) {
        $responseCookies[$c->getName()] = $c->getValue();
    }

    return [
        'status' => $res->getStatusCode(),
        'json' => json_decode($res->getContent() ?: '{}', true) ?: [],
        'cookies' => array_merge($cookies, $responseCookies),
    ];
}

echo "=== MEMULAI TEST MODUL 5: RESILIENSI WHATSAPP & INTEGRASI STRUK ===\n\n";

// 1. Unauthenticated checks
$unauthReceipt = callApp('/api/orders/1/receipt', 'GET');
assertTest("Endpoint /orders/{id}/receipt menolak request unauthenticated (401)", $unauthReceipt['status'] === 401, (string)$unauthReceipt['status']);

$unauthWa = callApp('/api/orders/1/send-whatsapp', 'POST', ['type' => 'receipt']);
assertTest("Endpoint /orders/{id}/send-whatsapp menolak request unauthenticated (401)", $unauthWa['status'] === 401, (string)$unauthWa['status']);

// 2. Login Kasir
$loginKasir = callApp('/api/login', 'POST', [
    'username' => 'kasir',
    'password' => 'KasirSyaqil!2026',
]);
$kasirCookies = $loginKasir['cookies'];
assertTest("Login kasir berhasil (HTTP 200)", $loginKasir['status'] === 200 && ($loginKasir['json']['user']['role'] ?? '') === 'cashier', "Status: {$loginKasir['status']}");

// 3. Pastikan konfigurasi WhatsApp gateway dalam keadaan unconfigured/disabled untuk uji resiliensi
$origWaEnabled = BusinessSetting::where('key', 'whatsapp_enabled')->value('value');
$origWaUrl = BusinessSetting::where('key', 'whatsapp_gateway_url')->value('value');

BusinessSetting::updateOrCreate(['key' => 'whatsapp_enabled'], ['value' => '0']);
BusinessSetting::updateOrCreate(['key' => 'whatsapp_gateway_url'], ['value' => '']);

// 4. Siapkan customer & layanan aktif
$testPhone = '0812' . rand(10000000, 99999999);
$custRes = callApp('/api/customers', 'POST', [
    'name' => 'Budi Santoso WA Test',
    'phone' => $testPhone,
    'address' => 'Jl. Pengujian WhatsApp No. 5',
], $kasirCookies);
$customerId = $custRes['json']['customer']['id'] ?? Customer::first()->id;

$activeService = Service::where('active', true)->first();
$serviceId = (int)$activeService->id;
$servicePrice = (int)$activeService->price;

$qty = 3.0;
$expectedSubtotal = (int)round($qty * $servicePrice);
$expectedTotal = $expectedSubtotal;

// 5. Kasir membuat Order baru: WhatsApp belum aktif
$orderPayload = [
    'customer_id' => $customerId,
    'items' => [
        ['service_id' => $serviceId, 'quantity' => $qty, 'category' => 'Kiloan']
    ],
    'discount_percent' => 0,
    'method' => 'cash',
    'paid' => $expectedTotal + 10000,
    'expected_total' => $expectedTotal,
    'request_key' => 'test-req-wa-' . bin2hex(random_bytes(8)),
    'perfume' => 'Ocean Fresh',
    'notes' => 'Uji coba Modul 5 WA Resiliensi',
];

$createOrderRes = callApp('/api/orders', 'POST', $orderPayload, $kasirCookies);
assertTest("Pembuatan order sukses (201 Created) meski WhatsApp belum dikonfigurasi", $createOrderRes['status'] === 201, "Status: {$createOrderRes['status']}");

$newOrder = $createOrderRes['json']['order'] ?? [];
$orderId = $newOrder['id'] ?? 0;
$orderNumber = $newOrder['number'] ?? '';

assertTest("Order memiliki ID valid dan nomor nota SYQ-", $orderId > 0 && str_starts_with($orderNumber, 'SYQ-'));
assertTest("Response order menyertakan info whatsapp resilient", isset($newOrder['whatsapp']));
assertTest("Status WhatsApp jujur 'not_configured'", ($newOrder['whatsapp']['status'] ?? '') === 'not_configured');
assertTest("wa_url fallback valid dihasilkan dengan format https://wa.me/62", str_starts_with($newOrder['whatsapp']['wa_url'] ?? '', 'https://wa.me/62'));

// 6. Cek tabel whatsapp_logs & notifications di database
$logEntry = WhatsappLog::where('order_id', $orderId)->where('type', 'receipt')->latest()->first();
assertTest("Log tercatat di tabel whatsapp_logs dengan status not_configured", $logEntry !== null && $logEntry->status === 'not_configured');

$notifEntry = Notification::where('order_id', $orderId)->latest()->first();
assertTest("Notifikasi tercatat di tabel notifications dengan status not_configured", $notifEntry !== null && $notifEntry->status === 'not_configured');

// 7. Uji Endpoint Cetak Struk (GET /api/orders/{id}/receipt)
$receiptRes = callApp("/api/orders/{$orderId}/receipt", 'GET', [], $kasirCookies);
assertTest("Endpoint /orders/{id}/receipt merespons 200 OK", $receiptRes['status'] === 200, "Status: {$receiptRes['status']}");

$receiptData = $receiptRes['json'] ?? [];
assertTest("Data struk menyertakan data order yang cocok", isset($receiptData['order']) && $receiptData['order']['number'] === $orderNumber);
assertTest("Data struk menyertakan identitas toko (nama, alamat, telepon)",
    !empty($receiptData['store']['name']) && !empty($receiptData['store']['address']) && !empty($receiptData['store']['phone'])
);
assertTest("Data struk menyertakan timestamp cetak print_at", !empty($receiptData['print_at']));
assertTest("Data struk menyertakan rincian item layanan", !empty($receiptData['order']['items']));

// 8. Uji transisi status ke 'Diproses' lalu ke 'Selesai'
$statusProses = callApp("/api/orders/{$orderId}/status", 'POST', ['status' => 'Diproses'], $kasirCookies);
assertTest("Transisi status ke Diproses sukses (HTTP 200)", $statusProses['status'] === 200 && ($statusProses['json']['order']['status'] ?? '') === 'Diproses');

$statusSelesai = callApp("/api/orders/{$orderId}/status", 'POST', ['status' => 'Selesai'], $kasirCookies);
assertTest("Transisi status ke Selesai sukses (HTTP 200)", $statusSelesai['status'] === 200 && ($statusSelesai['json']['order']['status'] ?? '') === 'Selesai');

// 9. Cek apakah trigger status_ready tercatat di whatsapp_logs
$statusReadyLog = WhatsappLog::where('order_id', $orderId)->where('type', 'status_ready')->latest()->first();
assertTest("Notifikasi status_ready otomatis dipicu saat status Selesai", $statusReadyLog !== null);
assertTest("Status ready log jujur 'not_configured' dan tidak menghambat transisi status", $statusReadyLog?->status === 'not_configured');

// 10. Uji coba pengiriman manual WhatsApp (POST /api/orders/{id}/send-whatsapp)
$manualWaRes = callApp("/api/orders/{$orderId}/send-whatsapp", 'POST', ['type' => 'receipt'], $kasirCookies);
assertTest("Endpoint manual send-whatsapp merespons 200 OK", $manualWaRes['status'] === 200, "Status: {$manualWaRes['status']}");
assertTest("Manual send-whatsapp menghasilkan URL wa.me fallback", str_starts_with($manualWaRes['json']['wa_url'] ?? '', 'https://wa.me/'));

// 11. Uji Resiliensi terhadap Kegagalan Koneksi Gateway (Simulasi Gateway Down / Error)
BusinessSetting::updateOrCreate(['key' => 'whatsapp_enabled'], ['value' => '1']);
BusinessSetting::updateOrCreate(['key' => 'whatsapp_gateway_url'], ['value' => 'http://127.0.0.1:59999/api/send']); // Port yang tidak aktif
BusinessSetting::updateOrCreate(['key' => 'whatsapp_api_key'], ['value' => 'fake_token_test']);

$failedGatewayRes = callApp("/api/orders/{$orderId}/send-whatsapp", 'POST', ['type' => 'status_ready'], $kasirCookies);
assertTest("Pengiriman dengan gateway mati tidak membuat server crash (status 200)", $failedGatewayRes['status'] === 200, "Status: {$failedGatewayRes['status']}");
assertTest("Response menandai status 'failed' secara jujur", ($failedGatewayRes['json']['status'] ?? '') === 'failed');
assertTest("Fallback wa_url tetap tersedia saat gateway gagal", !empty($failedGatewayRes['json']['wa_url']));

// Cek log kegagalan di database
$failedLog = WhatsappLog::where('order_id', $orderId)->where('status', 'failed')->latest()->first();
assertTest("Kegagalan gateway dicatat di database dengan pesan error", $failedLog !== null && !empty($failedLog->error_message));

// Kembalikan business setting ke semula
BusinessSetting::updateOrCreate(['key' => 'whatsapp_enabled'], ['value' => $origWaEnabled ?? '0']);
BusinessSetting::updateOrCreate(['key' => 'whatsapp_gateway_url'], ['value' => $origWaUrl ?? '']);

// 12. Role Owner juga dapat mengakses receipt dan send-whatsapp
$ownerLogin = callApp('/api/login', 'POST', [
    'username' => 'owner',
    'password' => 'OwnerSyaqil!2026',
]);
$ownerCookies = $ownerLogin['cookies'];
$ownerReceipt = callApp("/api/orders/{$orderId}/receipt", 'GET', [], $ownerCookies);
assertTest("Owner memiliki akses ke receipt order (HTTP 200)", $ownerReceipt['status'] === 200, "Status: {$ownerReceipt['status']}");

$ownerWa = callApp("/api/orders/{$orderId}/send-whatsapp", 'POST', ['type' => 'receipt'], $ownerCookies);
assertTest("Owner memiliki akses ke send-whatsapp order (HTTP 200)", $ownerWa['status'] === 200, "Status: {$ownerWa['status']}");

echo "\n============================================\n";
echo "HASIL PENGUJIAN MODUL 5:\n";
echo "Lulus (PASSED): {$passed}\n";
echo "Gagal (FAILED): {$failed}\n";
echo "============================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
