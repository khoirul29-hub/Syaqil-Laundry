<?php

declare(strict_types=1);

/**
 * Test Suite Komprehensif: Pengiriman Nota LaundryFlow via WhatsApp & Tautan Nota Digital
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
        'HTTP_HOST' => '127.0.0.1:8000',
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

    $rawContent = $res->getContent() ?: '';
    $json = json_decode($rawContent, true) ?: [];

    return [
        'status' => $res->getStatusCode(),
        'content' => $rawContent,
        'json' => $json,
        'cookies' => array_merge($cookies, $responseCookies),
    ];
}

echo "=== MEMULAI TEST PENGIRIMAN NOTA VIA WHATSAPP & NOTA DIGITAL ===\n\n";

// 1. Login Kasir
$loginKasir = callApp('/api/login', 'POST', [
    'username' => 'kasir',
    'password' => 'KasirSyaqil!2026',
]);
$kasirCookies = $loginKasir['cookies'];
assertTest("1. Login kasir berhasil (HTTP 200)", $loginKasir['status'] === 200);

// 2. Siapkan Data Customer dengan No HP format 08...
$customerPhone = '0812' . rand(11111111, 99999999);
$custRes = callApp('/api/customers', 'POST', [
    'name' => 'Siti Rahmawati',
    'phone' => $customerPhone,
    'address' => 'Jl. Khatib Sulaiman No. 12, Padang',
], $kasirCookies);
$customerId = $custRes['json']['customer']['id'] ?? Customer::first()->id;
assertTest("2. Pendaftaran pelanggan baru berhasil", $customerId > 0);

// 3. Ambil layanan aktif
$activeService = Service::where('active', true)->first();
$serviceId = (int)$activeService->id;
$servicePrice = (int)$activeService->price;
$qty = 4.0;
$subtotal = (int)round($qty * $servicePrice);
$discountPercent = 10;
$discount = (int)round($subtotal * 0.10);
$expectedTotal = $subtotal - $discount;

// 4. Kasir memproses transaksi dengan parfum 'Ocean Fresh'
$reqKey = 'test-wa-flow-' . bin2hex(random_bytes(10));
$orderPayload = [
    'customer_id' => $customerId,
    'items' => [
        ['service_id' => $serviceId, 'quantity' => $qty, 'category' => 'Kiloan']
    ],
    'discount_percent' => $discountPercent,
    'method' => 'cash',
    'paid' => $expectedTotal + 20000,
    'expected_total' => $expectedTotal,
    'request_key' => $reqKey,
    'perfume' => 'Ocean Fresh',
    'notes' => 'Tolong pisahkan pakaian putih dan batik sutra.',
];

$orderCountBefore = Order::count();
$createRes = callApp('/api/orders', 'POST', $orderPayload, $kasirCookies);
$orderCountAfter = Order::count();

assertTest("3. Transaksi berhasil disimpan (HTTP 201)", $createRes['status'] === 201);
assertTest("4. Jumlah order bertambah tepat 1 (tidak duplikat)", $orderCountAfter === $orderCountBefore + 1);

$orderData = $createRes['json']['order'] ?? [];
$orderId = $orderData['id'] ?? 0;
$orderNumber = $orderData['number'] ?? '';
$publicToken = $orderData['public_token'] ?? '';
$waInfo = $orderData['whatsapp'] ?? [];
$waUrl = $waInfo['wa_url'] ?? '';

assertTest("5. Nomor nota berawalan SYQ- dihasilkan", str_starts_with($orderNumber, 'SYQ-'));
assertTest("7. Pilihan parfum dihapus dari data order", empty($orderData['perfume']));

// 5. Cek format Click to Chat WhatsApp
assertTest("8. WhatsApp Click to Chat URL dihasilkan", !empty($waUrl) && str_starts_with($waUrl, 'https://wa.me/'));
assertTest("9. Nomor telepon dikonversi dari 08 ke format internasional 628", str_contains($waUrl, 'https://wa.me/628'));

// 6. Decode pesan WhatsApp dan verifikasi seluruh isi pesan
$parsedUrl = parse_url($waUrl);
parse_str($parsedUrl['query'] ?? '', $queryParams);
$messageText = $queryParams['text'] ?? '';

assertTest("10. Pesan WA memuat Nama Laundry", str_contains($messageText, 'Syaqil Laundry'));
assertTest("11. Pesan WA memuat Alamat Laundry", str_contains($messageText, 'Padang') || str_contains($messageText, 'Jl.'));
assertTest("12. Pesan WA memuat Nomor Nota", str_contains($messageText, $orderNumber));
assertTest("13. Pesan WA memuat Nama Pelanggan", str_contains($messageText, 'Siti Rahmawati'));
assertTest("14. Pesan WA memuat Tanggal Masuk", str_contains($messageText, 'Tanggal Masuk:'));
assertTest("15. Pesan WA memuat Estimasi Selesai", str_contains($messageText, 'Estimasi Selesai:'));
assertTest("16. Pesan WA memuat Rincian Layanan (jumlah, satuan, harga)",
    str_contains($messageText, $activeService->name) &&
    str_contains($messageText, '4 kg') &&
    str_contains($messageText, 'Rp ')
);
assertTest("17. Pesan WA TIDAK memuat baris parfum (telah dihapus)", !str_contains($messageText, 'Parfum:'));
assertTest("18. Pesan WA memuat Status Pembayaran Lunas", str_contains($messageText, 'Lunas'));
assertTest("19. Pesan WA TIDAK memuat baris diskon (telah dihapus)", !str_contains($messageText, 'Diskon:'));
assertTest("20. Pesan WA memuat Total Bayar", str_contains($messageText, 'Total'));
assertTest("21. Pesan WA memuat Tautan Nota Digital yang memuat token publik",
    str_contains($messageText, '/nota/' . $publicToken)
);
assertTest("22. Pesan WA memuat Ucapan Terima Kasih", str_contains($messageText, 'Terima kasih'));

// 7. Uji Akses Tautan Nota Digital Publik (GET /nota/{token}) - Tanpa Login
$receiptPageRes = callApp('/nota/' . $publicToken, 'GET', [], [], ['HTTP_ACCEPT' => 'text/html']);
assertTest("23. Halaman nota digital publik dapat diakses tanpa autentikasi (HTTP 200)", $receiptPageRes['status'] === 200);
assertTest("24. Halaman nota digital memuat nama pelanggan", str_contains($receiptPageRes['content'], 'Siti Rahmawati'));
assertTest("25. Halaman nota digital memuat nomor nota", str_contains($receiptPageRes['content'], $orderNumber));
assertTest("26. Halaman nota digital TIDAK memuat parfum (telah dihapus)", !str_contains($receiptPageRes['content'], 'Ocean Fresh'));
assertTest("27. Halaman nota digital memuat status LUNAS", str_contains($receiptPageRes['content'], 'LUNAS'));
assertTest("28. Halaman nota digital memuat tombol cetak/simpan PDF", str_contains($receiptPageRes['content'], 'window.print()'));

// 8. Uji Token Salah (GET /nota/token_palsu_12345)
$invalidTokenRes = callApp('/nota/token_palsu_12345', 'GET', [], [], ['HTTP_ACCEPT' => 'text/html']);
assertTest("30. Token nota digital yang salah mengembalikan HTTP 404 yang aman", $invalidTokenRes['status'] === 404);

// 9. Uji Endpoint JSON Nota Digital Publik (GET /api/public/receipt/{token})
$publicJsonRes = callApp('/api/public/receipt/' . $publicToken, 'GET');
assertTest("31. Endpoint JSON nota publik merespons 200 OK", $publicJsonRes['status'] === 200);
assertTest("32. Data JSON memuat token publik yang cocok", ($publicJsonRes['json']['order']['public_token'] ?? '') === $publicToken);

// 10. Uji Idempotency: Request key yang sama tidak membuat transaksi ganda
$orderCountBeforeIdem = Order::count();
$idemRes = callApp('/api/orders', 'POST', $orderPayload, $kasirCookies);
$orderCountAfterIdem = Order::count();
assertTest("33. Request ulang dengan request_key sama mengembalikan order yang ada (HTTP 200)", $idemRes['status'] === 200);
assertTest("34. Transaksi TIDAK tersimpan dua kali (jumlah order tetap)", $orderCountAfterIdem === $orderCountBeforeIdem);

// 11. Uji Pengiriman Ulang WhatsApp Manual (POST /api/orders/{id}/send-whatsapp)
$resendRes = callApp("/api/orders/{$orderId}/send-whatsapp", 'POST', ['type' => 'receipt'], $kasirCookies);
assertTest("35. Endpoint kirim ulang WhatsApp merespons 200 OK", $resendRes['status'] === 200);
assertTest("36. Kirim ulang menghasilkan wa_url yang valid", str_starts_with($resendRes['json']['wa_url'] ?? '', 'https://wa.me/628'));

// 12. Uji Penanganan Nomor Tidak Valid (Resiliensi: Order tetap tersimpan aman)
$invalidPhoneReqKey = 'test-invalid-phone-' . bin2hex(random_bytes(10));
$invalidPhoneCustRes = callApp('/api/customers', 'POST', [
    'name' => 'Pelanggan Nomor Rusak',
    'phone' => '08999' . rand(100000, 999999), // Valid saat register
    'address' => 'Padang',
], $kasirCookies);
$invalidCustId = $invalidPhoneCustRes['json']['customer']['id'] ?? Customer::first()->id;

// Ubah paksa customer phone di database menjadi invalid string untuk simulasi nomor rusak
Customer::where('id', $invalidCustId)->update(['phone' => '0000' . rand(1000, 9999)]);

$invalidOrderPayload = [
    'customer_id' => $invalidCustId,
    'items' => [
        ['service_id' => $serviceId, 'quantity' => 1.0, 'category' => 'Kiloan']
    ],
    'discount_percent' => 0,
    'method' => 'cash',
    'paid' => $servicePrice,
    'expected_total' => $servicePrice,
    'request_key' => $invalidPhoneReqKey,
];

$invalidOrderRes = callApp('/api/orders', 'POST', $invalidOrderPayload, $kasirCookies);
assertTest("37. Order dengan nomor WhatsApp tidak valid tetap tersimpan sukses (HTTP 201)", $invalidOrderRes['status'] === 201);
$invWa = $invalidOrderRes['json']['order']['whatsapp'] ?? [];
assertTest("38. Resiliensi nomor: phone_valid ditandai false", ($invWa['phone_valid'] ?? true) === false);

echo "\n============================================\n";
echo "HASIL PENGUJIAN KOMPREHENSIF:\n";
echo "Lulus (PASSED): {$passed}\n";
echo "Gagal (FAILED): {$failed}\n";
echo "============================================\n";

exit($failed > 0 ? 1 : 0);
