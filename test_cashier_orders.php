<?php

declare(strict_types=1);

/**
 * Test Suite Modul 3: Operasional Kasir & Perhitungan Transaksi Backend
 * Syaqil Laundry (LaundryFlow – Syaqil Laundry Ekspres)
 */

require __DIR__ . '/vendor/autoload.php';

use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
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
    $app = require __DIR__ . '/bootstrap/app.php';
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
        'json' => json_decode($res->getContent(), true) ?: [],
        'content' => $res->getContent(),
        'cookies' => $responseCookies,
    ];
}

echo "=== TEST SUITE MODUL 3: OPERASIONAL KASIR & PERHITUNGAN TRANSAKSI BACKEND ===\n\n";

// TEST 1: Akses anonim ke /api/bootstrap ditolak (HTTP 401)
$res1 = callApp('/api/bootstrap');
assertTest('1. Akses anonim ke /api/bootstrap ditolak (HTTP 401)', $res1['status'] === 401, (string)$res1['status']);

// TEST 2: Akses anonim membuat order ditolak (HTTP 401)
$res2 = callApp('/api/orders', 'POST', ['items' => []]);
assertTest('2. Akses anonim membuat order ditolak (HTTP 401)', $res2['status'] === 401, (string)$res2['status']);

// TEST 3: Login Kasir
$loginRes = callApp('/api/login', 'POST', [
    'username' => 'kasir',
    'password' => 'KasirSyaqil!2026',
]);
$cashierCookies = $loginRes['cookies'];
assertTest(
    '3. Login Kasir berhasil (HTTP 200)',
    $loginRes['status'] === 200 && ($loginRes['json']['user']['role'] ?? '') === 'cashier',
    "Status: {$loginRes['status']}, msg: " . json_encode($loginRes['json'])
);

// TEST 4: Load Bootstrap kasir
$bootRes = callApp('/api/bootstrap', 'GET', [], $cashierCookies);
$hasStructure = isset($bootRes['json']['customers']) &&
                 isset($bootRes['json']['orders']) &&
                 isset($bootRes['json']['services']) &&
                 isset($bootRes['json']['settings']);
assertTest(
    '4. Bootstrap kasir berhasil memuat customers, orders, services, dan settings',
    $bootRes['status'] === 200 && $hasStructure,
    "Status: {$bootRes['status']}"
);

// TEST 5: Tambah Pelanggan Baru dengan nomor WhatsApp
$testPhone = '0812' . rand(10000000, 99999999);
$custRes = callApp('/api/customers', 'POST', [
    'name' => 'Budi Santoso',
    'phone' => $testPhone,
    'address' => 'Jl. Mawar No. 5',
], $cashierCookies);
$newCustId = $custRes['json']['customer']['id'] ?? null;
assertTest(
    '5. Pendaftaran pelanggan baru berhasil dengan normalisasi nomor HP (HTTP 201)',
    $custRes['status'] === 201 && $newCustId && ($custRes['json']['customer']['name'] ?? '') === 'Budi Santoso',
    "Status: {$custRes['status']}, msg: " . json_encode($custRes['json'])
);

// TEST 6: Tambah Pelanggan dengan nomor yang sama ditolak (duplikat)
$dupRes = callApp('/api/customers', 'POST', [
    'name' => 'Budi Kembar',
    'phone' => $testPhone,
    'address' => 'Jl. Mawar No. 6',
], $cashierCookies);
assertTest(
    '6. Pendaftaran pelanggan dengan nomor HP terdaftar ditolak (HTTP 409)',
    $dupRes['status'] === 409,
    "Status: {$dupRes['status']}"
);

// TEST 7: Update data pelanggan
$updRes = callApp("/api/customers/{$newCustId}", 'POST', [
    'name' => 'Budi Santoso Updated',
    'phone' => $testPhone,
    'address' => 'Jl. Mawar No. 5B',
    'notes' => 'Langganan kemeja kantor',
], $cashierCookies);
assertTest(
    '7. Pembaruan data pelanggan berhasil disimpan (HTTP 200)',
    $updRes['status'] === 200 && ($updRes['json']['customer']['name'] ?? '') === 'Budi Santoso Updated',
    "Status: {$updRes['status']}"
);

// Ambil service aktif untuk testing order
$activeService = Service::where('active', true)->first();
$serviceId = (int)$activeService->id;
$servicePrice = (int)$activeService->price;

// TEST 8: Validasi Backend: Berat Cucian 0 atau negatif ditolak
$badQtyRes = callApp('/api/orders', 'POST', [
    'customer_id' => $newCustId,
    'items' => [
        ['service_id' => $serviceId, 'quantity' => 0, 'category' => 'Kiloan']
    ],
    'discount_percent' => 0,
    'method' => 'cash',
    'paid' => 10000,
    'expected_total' => 0,
    'request_key' => 'test-req-badqty-' . bin2hex(random_bytes(10)),
], $cashierCookies);
assertTest('8. Transaksi dengan berat 0 atau negatif ditolak oleh backend (HTTP 422)', $badQtyRes['status'] === 422, "Status: {$badQtyRes['status']}");

// TEST 9: Validasi Backend: Diskon > 100% ditolak
$badDiscRes = callApp('/api/orders', 'POST', [
    'customer_id' => $newCustId,
    'items' => [
        ['service_id' => $serviceId, 'quantity' => 2, 'category' => 'Kiloan']
    ],
    'discount_percent' => 150,
    'method' => 'cash',
    'paid' => 10000,
    'expected_total' => 0,
    'request_key' => 'test-req-baddisc-' . bin2hex(random_bytes(10)),
], $cashierCookies);
assertTest('9. Transaksi dengan diskon di atas 100% ditolak oleh backend (HTTP 422)', $badDiscRes['status'] === 422, "Status: {$badDiscRes['status']}");

// TEST 10: Deteksi Tampering Harga: expected_total berbeda dari kalkulasi otoritatif backend
$badPriceRes = callApp('/api/orders', 'POST', [
    'customer_id' => $newCustId,
    'items' => [
        ['service_id' => $serviceId, 'quantity' => 3, 'category' => 'Kiloan']
    ],
    'discount_percent' => 0,
    'method' => 'cash',
    'paid' => 50000,
    'expected_total' => 1000, // Sengaja dimanipulasi
    'request_key' => 'test-req-tamper-' . bin2hex(random_bytes(10)),
], $cashierCookies);
assertTest('10. Manipulasi / perbedaan nominal expected_total dicegah (HTTP 409)', $badPriceRes['status'] === 409, "Status: {$badPriceRes['status']}");

// Hitung nilai eksak untuk 3.5 kg dengan diskon 10%
$qty = 3.5;
$hundredths = (int)round($qty * 100);
$expectedSubtotal = (int)round($hundredths * $servicePrice / 100);
$discPercent = 10.0;
$basisPoints = (int)round($discPercent * 100);
$expectedDiscount = (int)round($expectedSubtotal * $basisPoints / 10000);
$expectedTotal = $expectedSubtotal - $expectedDiscount;
$cashPaid = $expectedTotal + 15000;
$expectedChange = 15000;

// TEST 11: Buat Order Tunai (Cash) yang Valid
$reqKey1 = 'test-key-cash-' . bin2hex(random_bytes(10));
$orderPayload1 = [
    'customer_id' => $newCustId,
    'items' => [
        ['service_id' => $serviceId, 'quantity' => $qty, 'category' => 'Kiloan']
    ],
    'discount_percent' => $discPercent,
    'method' => 'cash',
    'paid' => $cashPaid,
    'expected_total' => $expectedTotal,
    'request_key' => $reqKey1,
    'notes' => 'Harap jemur di ruangan teduh',
];

$orderRes = callApp('/api/orders', 'POST', $orderPayload1, $cashierCookies);
$order = $orderRes['json']['order'] ?? null;
$calcCorrect = $order &&
               (int)$order['subtotal'] === $expectedSubtotal &&
               (int)$order['discount'] === $expectedDiscount &&
               (int)$order['total'] === $expectedTotal &&
               (int)$order['paid'] === $cashPaid &&
               (int)$order['change_amount'] === $expectedChange &&
               str_starts_with($order['number'], 'SYQ-') &&
               $order['status'] === 'Diterima';

assertTest(
    '11. Order tunai berhasil disimpan dengan kalkulasi backend otoritatif & nomor SYQ- (HTTP 201)',
    $orderRes['status'] === 201 && $calcCorrect,
    "Status: {$orderRes['status']}, msg: " . json_encode($orderRes['json'])
);

$orderId = $order['id'] ?? 0;

// TEST 12: Idempotency: Request ulang dengan request_key sama mengembalikan order yang sudah ada
$idemRes = callApp('/api/orders', 'POST', $orderPayload1, $cashierCookies);
assertTest(
    '12. Idempotency terjamin: request ulang dengan request_key sama mengembalikan order asli (HTTP 200)',
    $idemRes['status'] === 200 && ($idemRes['json']['order']['id'] ?? 0) === $orderId,
    "Status: {$idemRes['status']}"
);

// TEST 13: Deteksi Konflik Draft: request_key sama tapi payload diubah
$alteredPayload = $orderPayload1;
$alteredPayload['notes'] = 'Catatan diubah secara konflik';
$conflictRes = callApp('/api/orders', 'POST', $alteredPayload, $cashierCookies);
assertTest(
    '13. Konflik draft terdeteksi jika request_key sama dipakai ulang untuk isian berbeda (HTTP 409 draft_conflict)',
    $conflictRes['status'] === 409 && ($conflictRes['json']['code'] ?? '') === 'draft_conflict',
    "Status: {$conflictRes['status']}"
);

// TEST 14: Alur Transisi Status: Diterima -> Diproses
$st1Res = callApp("/api/orders/{$orderId}/status", 'POST', ['status' => 'Diproses'], $cashierCookies);
assertTest(
    '14. Transisi status Diterima -> Diproses berhasil (HTTP 200)',
    $st1Res['status'] === 200 && ($st1Res['json']['order']['status'] ?? '') === 'Diproses',
    "Status: {$st1Res['status']}"
);

// TEST 15: Validasi Transisi Status: Melompat dari Diproses langsung ke Diambil ditolak
$stSkipRes = callApp("/api/orders/{$orderId}/status", 'POST', ['status' => 'Diambil'], $cashierCookies);
assertTest(
    '15. Melompati status (Diproses -> Diambil tanpa Selesai) ditolak (HTTP 422)',
    $stSkipRes['status'] === 422,
    "Status: {$stSkipRes['status']}"
);

// TEST 16: Transisi Status Diproses -> Selesai dan pencatatan notifikasi WhatsApp
$st2Res = callApp("/api/orders/{$orderId}/status", 'POST', ['status' => 'Selesai'], $cashierCookies);
$hasNotif = !empty($st2Res['json']['order']['notification']);
assertTest(
    '16. Transisi Diproses -> Selesai berhasil & mencatat antrean notifikasi WhatsApp pelanggan (HTTP 200)',
    $st2Res['status'] === 200 && ($st2Res['json']['order']['status'] ?? '') === 'Selesai' && $hasNotif,
    "Status: {$st2Res['status']}"
);

// TEST 17: Transisi Status Selesai -> Diambil
$st3Res = callApp("/api/orders/{$orderId}/status", 'POST', ['status' => 'Diambil'], $cashierCookies);
assertTest(
    '17. Transisi Selesai -> Diambil berhasil (HTTP 200)',
    $st3Res['status'] === 200 && ($st3Res['json']['order']['status'] ?? '') === 'Diambil',
    "Status: {$st3Res['status']}"
);

// TEST 18: Ambil detail order
$showRes = callApp("/api/orders/{$orderId}", 'GET', [], $cashierCookies);
$historyCount = count($showRes['json']['order']['history'] ?? []);
assertTest(
    '18. Riwayat lengkap 4 tahapan status tercatat di status_history (HTTP 200)',
    $showRes['status'] === 200 && $historyCount === 4,
    "Status: {$showRes['status']}, history: {$historyCount}"
);

echo "\nRingkasan Modul 3: {$passed} PASS, {$failed} FAIL\n";
exit($failed > 0 ? 1 : 0);
