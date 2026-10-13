<?php

declare(strict_types=1);

/**
 * Test Suite Modul 6: Verifikasi Pembayaran Non-Tunai & Uji End-to-End (E2E)
 * Syaqil Laundry (LaundryFlow – Syaqil Laundry Ekspres)
 */

$baseDir = dirname(__DIR__, 2);
require $baseDir . '/vendor/autoload.php';

use App\Models\BusinessSetting;
use App\Models\Customer;
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

echo "=== MEMULAI TEST MODUL 6: VERIFIKASI PEMBAYARAN & UJI END-TO-END ===\n\n";

// 1. Login Kasir
$loginKasir = callApp('/api/login', 'POST', [
    'username' => 'kasir',
    'password' => 'KasirSyaqil!2026',
]);
$kasirCookies = $loginKasir['cookies'];
assertTest("1. Login kasir berhasil (HTTP 200)", $loginKasir['status'] === 200 && ($loginKasir['json']['user']['role'] ?? '') === 'cashier');

// 2. Siapkan Customer & Layanan
$testPhone = '0813' . rand(10000000, 99999999);
$custRes = callApp('/api/customers', 'POST', [
    'name' => 'Dewi Sartika E2E',
    'phone' => $testPhone,
    'address' => 'Jl. Veteran No. 12',
], $kasirCookies);
$customerId = $custRes['json']['customer']['id'] ?? Customer::first()->id;

$activeService = Service::where('active', true)->first();
$serviceId = (int)$activeService->id;
$servicePrice = (int)$activeService->price;
$qty = 2.0;
$expectedTotal = (int)round($qty * $servicePrice);

// 3. Simpan setting awal
$origQris = BusinessSetting::where('key', 'qris_image')->value('value');
$origBankName = BusinessSetting::where('key', 'bank_name')->value('value');
$origBankAccount = BusinessSetting::where('key', 'bank_account')->value('value');
$origBankHolder = BusinessSetting::where('key', 'bank_holder')->value('value');

// --- UJI PEMBAYARAN QRIS ---

// Kasus A: QRIS belum dikonfigurasi -> Ditolak (422)
BusinessSetting::updateOrCreate(['key' => 'qris_image'], ['value' => '']);
$qrisNoConfigRes = callApp('/api/orders', 'POST', [
    'customer_id' => $customerId,
    'items' => [['service_id' => $serviceId, 'quantity' => $qty, 'category' => 'Kiloan']],
    'discount_percent' => 0,
    'method' => 'qris',
    'paid' => $expectedTotal,
    'confirmed' => true,
    'expected_total' => $expectedTotal,
    'request_key' => 'test-req-qris-noconfig-' . bin2hex(random_bytes(6)),
], $kasirCookies);
assertTest("2. Pembayaran QRIS tanpa konfigurasi merchant ditolak (HTTP 422)", $qrisNoConfigRes['status'] === 422, "Status: {$qrisNoConfigRes['status']}");

// Kasus B: QRIS dikonfigurasi, tapi belum dikonfirmasi kasir (`confirmed: false`) -> Ditolak (422)
BusinessSetting::updateOrCreate(['key' => 'qris_image'], ['value' => 'https://example.com/qris-merchant.png']);
$qrisUnconfRes = callApp('/api/orders', 'POST', [
    'customer_id' => $customerId,
    'items' => [['service_id' => $serviceId, 'quantity' => $qty, 'category' => 'Kiloan']],
    'discount_percent' => 0,
    'method' => 'qris',
    'paid' => $expectedTotal,
    'confirmed' => false,
    'expected_total' => $expectedTotal,
    'request_key' => 'test-req-qris-unconf-' . bin2hex(random_bytes(6)),
], $kasirCookies);
assertTest("3. Pembayaran QRIS tanpa konfirmasi kasir ditolak (HTTP 422)", $qrisUnconfRes['status'] === 422, "Status: {$qrisUnconfRes['status']}");

// Kasus C: QRIS dikonfigurasi dan dikonfirmasi kasir -> Sukses (201)
$qrisReqKey = 'test-req-qris-valid-' . bin2hex(random_bytes(6));
$qrisValidRes = callApp('/api/orders', 'POST', [
    'customer_id' => $customerId,
    'items' => [['service_id' => $serviceId, 'quantity' => $qty, 'category' => 'Kiloan']],
    'discount_percent' => 0,
    'method' => 'qris',
    'paid' => $expectedTotal,
    'confirmed' => true,
    'expected_total' => $expectedTotal,
    'request_key' => $qrisReqKey,
    'notes' => 'Pembayaran via QRIS Statis BCA',
], $kasirCookies);
$qrisOrder = $qrisValidRes['json']['order'] ?? [];
assertTest("4. Pembayaran QRIS valid berhasil disimpan (HTTP 201)",
    $qrisValidRes['status'] === 201 && ($qrisOrder['method'] ?? '') === 'qris' && (int)($qrisOrder['paid'] ?? 0) === $expectedTotal && (int)($qrisOrder['change_amount'] ?? 1) === 0,
    "Status: {$qrisValidRes['status']}, msg: " . json_encode($qrisValidRes['json'])
);

// --- UJI PEMBAYARAN TRANSFER BANK ---

// Kasus A: Bank belum lengkap dikonfigurasi -> Ditolak (422)
BusinessSetting::updateOrCreate(['key' => 'bank_name'], ['value' => '']);
BusinessSetting::updateOrCreate(['key' => 'bank_account'], ['value' => '']);
BusinessSetting::updateOrCreate(['key' => 'bank_holder'], ['value' => '']);

$bankNoConfigRes = callApp('/api/orders', 'POST', [
    'customer_id' => $customerId,
    'items' => [['service_id' => $serviceId, 'quantity' => $qty, 'category' => 'Kiloan']],
    'discount_percent' => 0,
    'method' => 'transfer',
    'paid' => $expectedTotal,
    'confirmed' => true,
    'expected_total' => $expectedTotal,
    'request_key' => 'test-req-bank-noconfig-' . bin2hex(random_bytes(6)),
], $kasirCookies);
assertTest("5. Pembayaran Transfer Bank tanpa data rekening merchant ditolak (HTTP 422)", $bankNoConfigRes['status'] === 422, "Status: {$bankNoConfigRes['status']}");

// Kasus B: Bank lengkap dikonfigurasi, tapi belum dikonfirmasi kasir -> Ditolak (422)
BusinessSetting::updateOrCreate(['key' => 'bank_name'], ['value' => 'Bank Mandiri']);
BusinessSetting::updateOrCreate(['key' => 'bank_account'], ['value' => '123-00-9876543-2']);
BusinessSetting::updateOrCreate(['key' => 'bank_holder'], ['value' => 'Syaqil Laundry']);

$bankUnconfRes = callApp('/api/orders', 'POST', [
    'customer_id' => $customerId,
    'items' => [['service_id' => $serviceId, 'quantity' => $qty, 'category' => 'Kiloan']],
    'discount_percent' => 0,
    'method' => 'transfer',
    'paid' => $expectedTotal,
    'confirmed' => false,
    'expected_total' => $expectedTotal,
    'request_key' => 'test-req-bank-unconf-' . bin2hex(random_bytes(6)),
], $kasirCookies);
assertTest("6. Pembayaran Transfer Bank tanpa konfirmasi kasir ditolak (HTTP 422)", $bankUnconfRes['status'] === 422, "Status: {$bankUnconfRes['status']}");

// Kasus C: Bank lengkap dikonfigurasi dan dikonfirmasi kasir -> Sukses (201)
$bankReqKey = 'test-req-bank-valid-' . bin2hex(random_bytes(6));
$bankValidRes = callApp('/api/orders', 'POST', [
    'customer_id' => $customerId,
    'items' => [['service_id' => $serviceId, 'quantity' => $qty, 'category' => 'Kiloan']],
    'discount_percent' => 0,
    'method' => 'transfer',
    'paid' => $expectedTotal,
    'confirmed' => true,
    'expected_total' => $expectedTotal,
    'request_key' => $bankReqKey,
    'notes' => 'Transfer Mandiri Ref 99812',
], $kasirCookies);
$bankOrder = $bankValidRes['json']['order'] ?? [];
assertTest("7. Pembayaran Transfer Bank valid berhasil disimpan (HTTP 201)",
    $bankValidRes['status'] === 201 && ($bankOrder['method'] ?? '') === 'transfer' && (int)($bankOrder['paid'] ?? 0) === $expectedTotal && (int)($bankOrder['change_amount'] ?? 1) === 0,
    "Status: {$bankValidRes['status']}, msg: " . json_encode($bankValidRes['json'])
);

// --- UJI SIKLUS HIDUP LENGKAP END-TO-END (LIFECYCLE) ---
$e2eOrderId = $bankOrder['id'] ?? 0;
assertTest("8. Order E2E memiliki ID valid", $e2eOrderId > 0);

// Tahap 1: Diterima -> Diproses
$step1 = callApp("/api/orders/{$e2eOrderId}/status", 'POST', ['status' => 'Diproses'], $kasirCookies);
assertTest("9. Transisi E2E: Diterima -> Diproses berhasil", $step1['status'] === 200 && ($step1['json']['order']['status'] ?? '') === 'Diproses');

// Tahap 2: Diproses -> Selesai (Memicu WhatsApp ready)
$step2 = callApp("/api/orders/{$e2eOrderId}/status", 'POST', ['status' => 'Selesai'], $kasirCookies);
assertTest("10. Transisi E2E: Diproses -> Selesai berhasil", $step2['status'] === 200 && ($step2['json']['order']['status'] ?? '') === 'Selesai');

// Cek log WhatsApp otomatis
$waReadyLog = WhatsappLog::where('order_id', $e2eOrderId)->where('type', 'status_ready')->first();
assertTest("11. Log WhatsApp status_ready otomatis terbentuk di database", $waReadyLog !== null);

// Tahap 3: Selesai -> Diambil
$step3 = callApp("/api/orders/{$e2eOrderId}/status", 'POST', ['status' => 'Diambil'], $kasirCookies);
assertTest("12. Transisi E2E: Selesai -> Diambil berhasil", $step3['status'] === 200 && ($step3['json']['order']['status'] ?? '') === 'Diambil');

// Tahap 4: Struk Cetak Nota Terpadu
$receiptRes = callApp("/api/orders/{$e2eOrderId}/receipt", 'GET', [], $kasirCookies);
assertTest("13. Cetak Struk Nota E2E terstruktur lengkap",
    $receiptRes['status'] === 200 && isset($receiptRes['json']['store']) && isset($receiptRes['json']['order']['items'])
);

// --- UJI INTEGRASI KEUANGAN KE OWNER ---
$loginOwner = callApp('/api/login', 'POST', [
    'username' => 'owner',
    'password' => 'OwnerSyaqil!2026',
]);
$ownerCookies = $loginOwner['cookies'];
assertTest("14. Login Owner berhasil (HTTP 200)", $loginOwner['status'] === 200 && ($loginOwner['json']['user']['role'] ?? '') === 'owner');

// Laporan Owner memuat transaksi E2E
$repRes = callApp('/api/owner/reports?from=' . date('Y-m-01') . '&to=' . date('Y-m-t'), 'GET', [], $ownerCookies);
$hasRepData = isset($repRes['json']['summary']['totalRevenue']) &&
               isset($repRes['json']['summary']['totalExpenses']) &&
               isset($repRes['json']['summary']['netProfit']) &&
               isset($repRes['json']['paymentMethods']);
assertTest("15. Laporan Keuangan Owner otomatis memuat agregasi transaksi nyata",
    $repRes['status'] === 200 && $hasRepData && ($repRes['json']['summary']['totalRevenue'] ?? 0) > 0,
    "Status: {$repRes['status']}"
);

// Kembalikan konfigurasi semula
BusinessSetting::updateOrCreate(['key' => 'qris_image'], ['value' => $origQris ?? '']);
BusinessSetting::updateOrCreate(['key' => 'bank_name'], ['value' => $origBankName ?? '']);
BusinessSetting::updateOrCreate(['key' => 'bank_account'], ['value' => $origBankAccount ?? '']);
BusinessSetting::updateOrCreate(['key' => 'bank_holder'], ['value' => $origBankHolder ?? '']);

echo "\n============================================\n";
echo "HASIL PENGUJIAN MODUL 6:\n";
echo "Lulus (PASSED): {$passed}\n";
echo "Gagal (FAILED): {$failed}\n";
echo "============================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
