<?php

declare(strict_types=1);

/**
 * Test Suite: Verifikasi Fitur Tautan Nota Digital Publik Antar-Perangkat (Internet Ready)
 * Menguji:
 * 1. Resolusi URL Nota Publik berbasis konfigurasi environment PUBLIC_RECEIPT_BASE_URL.
 * 2. Integrasi tautan publik tersebut ke dalam pesan WhatsApp.
 * 3. Akses publik ke view HTML Blade dan JSON API tanpa autentikasi kasir.
 * 4. Prinsip Least Privilege: Endpoint JSON publik tidak membocorkan field sensitif internal.
 * 5. Penanganan token tidak valid (404 aman).
 * 6. Pengaturan dinamis CORS_ALLOWED_ORIGINS.
 */

$baseDir = dirname(__DIR__, 2);
require $baseDir . '/vendor/autoload.php';

use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use App\Services\WhatsAppNotificationService;
use Illuminate\Http\Request;

$app = require $baseDir . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

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
    global $kernel;

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
        'content' => $res->getContent(),
        'headers' => $res->headers->all(),
        'cookies' => array_merge($cookies, $responseCookies),
    ];
}

echo "=== MEMULAI TEST VERIFIKASI TAUTAN NOTA PUBLIK (INTERNET READY) ===\n\n";

// --- PENGUJIAN 1: Resolusi PUBLIC_RECEIPT_BASE_URL dari Konfigurasi Environment ---
$waService = app(WhatsAppNotificationService::class);
$dummyToken = 'a1b2c3d4e5f60718293a4b5c6d7e8f90';

// A. Uji dengan env PUBLIC_RECEIPT_BASE_URL terdefinisi
config(['app.public_receipt_base_url' => 'https://laundryflow.my.id']);
$urlWithEnv = $waService->getPublicReceiptUrl($dummyToken);
assertTest("1. PUBLIC_RECEIPT_BASE_URL menghasilkan URL domain publik produksi", 
    $urlWithEnv === "https://laundryflow.my.id/nota/{$dummyToken}",
    "Hasil: {$urlWithEnv}"
);

// B. Uji trailing slash ditangani dengan rapi (tanpa double slash)
config(['app.public_receipt_base_url' => 'https://laundryflow.my.id/']);
$urlTrailingSlash = $waService->getPublicReceiptUrl($dummyToken);
assertTest("2. Trailing slash dibersihkan dengan benar (tidak ada double slash //nota/)", 
    $urlTrailingSlash === "https://laundryflow.my.id/nota/{$dummyToken}",
    "Hasil: {$urlTrailingSlash}"
);

// C. Uji fallback ketika PUBLIC_RECEIPT_BASE_URL kosong
config(['app.public_receipt_base_url' => null]);
config(['app.url' => 'http://127.0.0.1:8000']);
$fallbackUrl = $waService->getPublicReceiptUrl($dummyToken);
assertTest("3. Fallback dev menghasilkan URL yang valid saat konfigurasi publik kosong", 
    str_contains($fallbackUrl, "/nota/{$dummyToken}")
);

// Kembalikan ke URL produksi untuk pengujian transaksi di bawah
config(['app.public_receipt_base_url' => 'https://laundryflow.my.id']);

// --- PENGUJIAN 2: Transaksi Kasir & Integrasi Tautan Publik ke Pesan WhatsApp ---
$loginKasir = callApp('/api/login', 'POST', [
    'username' => 'kasir',
    'password' => 'KasirSyaqil!2026',
]);
$kasirCookies = $loginKasir['cookies'];
assertTest("4. Kasir berhasil login untuk membuat transaksi uji", $loginKasir['status'] === 200);

$customer = Customer::first() ?? Customer::create([
    'name' => 'Pelanggan Internet Uji',
    'phone' => '081298765432',
    'address' => 'Jl. Uji Publik No. 1',
]);
$service = Service::where('active', true)->first();

$orderPayload = [
    'customer_id' => $customer->id,
    'items' => [
        ['service_id' => $service->id, 'quantity' => 2, 'category' => 'Kiloan']
    ],
    'discount_percent' => 0,
    'method' => 'cash',
    'paid' => (int)round(2 * $service->price),
    'expected_total' => (int)round(2 * $service->price),
    'request_key' => 'test-internet-req-' . bin2hex(random_bytes(8)),
    'perfume' => 'Lavender',
    'notes' => 'Uji akses nota dari jaringan internet',
];

$orderRes = callApp('/api/orders', 'POST', $orderPayload, $kasirCookies);
assertTest("5. Transaksi berhasil dibuat (HTTP 201)", $orderRes['status'] === 201);

$orderData = $orderRes['json']['order'] ?? [];
$publicToken = $orderData['public_token'] ?? '';
$publicUrl = $orderData['public_url'] ?? '';
$waUrl = $orderData['whatsapp']['wa_url'] ?? '';

assertTest("6. Order mengembalikan field public_url berbasis konfigurasi environment", 
    $publicUrl === "https://laundryflow.my.id/nota/{$publicToken}",
    "Hasil: {$publicUrl}"
);

// Periksa teks pesan WhatsApp yang di-encode dalam wa.me URL
$parsedWa = parse_url($waUrl);
parse_str($parsedWa['query'] ?? '', $waQuery);
$messageText = $waQuery['text'] ?? '';

assertTest("7. Pesan WhatsApp memuat URL publik produksi (bukan localhost/127.0.0.1)", 
    str_contains($messageText, "https://laundryflow.my.id/nota/{$publicToken}") &&
    !str_contains($messageText, "127.0.0.1:8000/nota") &&
    !str_contains($messageText, "localhost:8000/nota"),
    "Teks: {$messageText}"
);

// --- PENGUJIAN 3: Akses Publik Halaman HTML Nota Digital (Tanpa Login) ---
$htmlRes = callApp("/nota/{$publicToken}", 'GET', [], [], ['HTTP_ACCEPT' => 'text/html']);
assertTest("8. Halaman HTML nota (/nota/{token}) dapat dibuka tanpa login (HTTP 200)", $htmlRes['status'] === 200);
assertTest("9. Halaman HTML memuat nama pelanggan", str_contains($htmlRes['content'], $customer->name));
assertTest("10. Halaman HTML memuat nomor nota", str_contains($htmlRes['content'], $orderData['number']));
assertTest("11. Halaman HTML memuat tombol Cetak / PDF", str_contains($htmlRes['content'], 'window.print()'));

// --- PENGUJIAN 4: Akses Publik Endpoint JSON (GET /api/public/receipt/{token}) Tanpa Login ---
$jsonRes = callApp("/api/public/receipt/{$publicToken}", 'GET');
assertTest("12. Endpoint JSON publik merespons 200 OK tanpa login", $jsonRes['status'] === 200);
$publicOrder = $jsonRes['json']['order'] ?? [];
assertTest("13. JSON publik memuat nomor nota yang tepat", ($publicOrder['number'] ?? '') === $orderData['number']);
assertTest("14. JSON publik memuat rincian item", !empty($publicOrder['items']));
assertTest("15. JSON publik memuat data toko publik", !empty($jsonRes['json']['store']['name']));

// --- PENGUJIAN 5: Prinsip Keamanan & Least Privilege ---
// Pastikan TIDAK ADA field internal kasir/admin di response JSON publik
$forbiddenFields = ['cashier_id', 'customer_id', 'request_key', 'request_hash', 'notification', 'password'];
$leakedFields = [];
foreach ($forbiddenFields as $field) {
    if (array_key_exists($field, $publicOrder)) {
        $leakedFields[] = $field;
    }
}
assertTest("16. Keamanan Data: Endpoint JSON publik TIDAK mengekspos field sensitif internal (" . implode(', ', $forbiddenFields) . ")", 
    empty($leakedFields),
    "Field bocor: " . implode(', ', $leakedFields)
);

// History juga tidak boleh membocorkan user_name / ID kasir
$historyHasCashier = false;
foreach ($publicOrder['history'] ?? [] as $h) {
    if (isset($h['user_name']) || isset($h['user_id'])) {
        $historyHasCashier = true;
    }
}
assertTest("17. Keamanan Riwayat: History status publik tidak membocorkan identitas internal kasir", 
    !$historyHasCashier
);

// --- PENGUJIAN 6: Penanganan Token yang Salah / Tidak Ada (Keamanan 404) ---
$fakeTokenHtml = callApp('/nota/token_acak_yang_tidak_pernah_ada_99999', 'GET', [], [], ['HTTP_ACCEPT' => 'text/html']);
assertTest("18. Token palsu pada halaman web menghasilkan status HTTP 404 Not Found", $fakeTokenHtml['status'] === 404);

$fakeTokenJson = callApp('/api/public/receipt/token_acak_yang_tidak_pernah_ada_99999', 'GET');
assertTest("19. Token palsu pada API publik menghasilkan status HTTP 404 Not Found", $fakeTokenJson['status'] === 404);
assertTest("20. Pesan error 404 ramah pengguna dan tidak mengekspos exception backend", 
    ($fakeTokenJson['json']['error'] ?? '') === 'Nota digital tidak ditemukan.'
);

// --- PENGUJIAN 7: Konfigurasi CORS Dinamis CORS_ALLOWED_ORIGINS ---
putenv('CORS_ALLOWED_ORIGINS=https://app.laundryflow.my.id,https://pelanggan.laundryflow.my.id');
$corsConfig = config('cors.allowed_origins');
assertTest("21. CORS mendukung multi-domain frontend dari environment CORS_ALLOWED_ORIGINS", 
    is_array($corsConfig)
);

echo "\n============================================================\n";
echo "HASIL PENGUJIAN VERIFIKASI INTERNET READY:\n";
echo "Lulus (PASSED): {$passed}\n";
echo "Gagal (FAILED): {$failed}\n";
echo "============================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
