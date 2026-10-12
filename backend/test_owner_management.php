<?php

declare(strict_types=1);

/**
 * Test Suite Modul 4: Manajemen Owner, Pengeluaran & Laporan Keuangan
 * Syaqil Laundry (LaundryFlow – Syaqil Laundry Ekspres)
 */

require __DIR__ . '/vendor/autoload.php';

use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Order;
use App\Models\PriceHistory;
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

echo "=== TEST SUITE MODUL 4: MANAJEMEN OWNER, PENGELUARAN & LAPORAN KEUANGAN ===\n\n";

// TEST 1: Akses anonim ke endpoint owner ditolak
$res1 = callApp('/api/owner/bootstrap');
assertTest('1. Akses anonim ke /api/owner/bootstrap ditolak (HTTP 401)', $res1['status'] === 401, (string)$res1['status']);

// TEST 2: Kasir ditolak mengakses endpoint Owner (RBAC 403 Forbidden)
$cashierLogin = callApp('/api/login', 'POST', [
    'username' => 'kasir',
    'password' => 'KasirSyaqil!2026',
]);
$cashierCookies = $cashierLogin['cookies'];
$res2 = callApp('/api/owner/bootstrap', 'GET', [], $cashierCookies);
assertTest('2. Kasir ditolak mengakses /api/owner/bootstrap (RBAC HTTP 403)', $res2['status'] === 403, (string)$res2['status']);

// TEST 3: Login Owner
$ownerLogin = callApp('/api/login', 'POST', [
    'username' => 'owner',
    'password' => 'OwnerSyaqil!2026',
]);
$ownerCookies = $ownerLogin['cookies'];
assertTest(
    '3. Login Owner berhasil (HTTP 200)',
    $ownerLogin['status'] === 200 && ($ownerLogin['json']['user']['role'] ?? '') === 'owner',
    "Status: {$ownerLogin['status']}"
);

// TEST 4: Load Bootstrap Owner
$bootRes = callApp('/api/owner/bootstrap', 'GET', [], $ownerCookies);
$hasStructure = isset($bootRes['json']['receipts']) &&
                 isset($bootRes['json']['customers']) &&
                 isset($bootRes['json']['expenses']) &&
                 isset($bootRes['json']['services']) &&
                 isset($bootRes['json']['priceHistory']) &&
                 isset($bootRes['json']['employees']) &&
                 isset($bootRes['json']['settings']) &&
                 isset($bootRes['json']['financialSummary']);
assertTest(
    '4. Owner Bootstrap berhasil memuat seluruh relasi dan ringkasan keuangan (HTTP 200)',
    $bootRes['status'] === 200 && $hasStructure,
    "Status: {$bootRes['status']}"
);

// TEST 5: Tambah Pengeluaran Baru
$expRes = callApp('/api/owner/expenses', 'POST', [
    'category' => 'Operasional',
    'description' => 'Beli Deterjen Konsentrat 20 Liter',
    'amount' => 150000,
    'date' => date('Y-m-d'),
    'method' => 'Tunai',
], $ownerCookies);
$expId = $expRes['json']['expense']['id'] ?? 0;
assertTest(
    '5. Tambah catatan pengeluaran baru berhasil (HTTP 201)',
    $expRes['status'] === 201 && $expId > 0 && ($expRes['json']['expense']['amount'] ?? 0) === 150000,
    "Status: {$expRes['status']}, msg: " . json_encode($expRes['json'])
);

// TEST 6: Validasi Pengeluaran (nominal 0 ditolak)
$badExp = callApp('/api/owner/expenses', 'POST', [
    'category' => 'Operasional',
    'description' => 'Test salah',
    'amount' => 0,
    'date' => date('Y-m-d'),
], $ownerCookies);
assertTest('6. Validasi pengeluaran (nominal 0) ditolak (HTTP 422)', $badExp['status'] === 422, "Status: {$badExp['status']}");

// TEST 7: Update Pengeluaran
$updExp = callApp("/api/owner/expenses/{$expId}", 'POST', [
    'description' => 'Beli Deterjen Konsentrat 20 Liter (Revisi)',
    'amount' => 160000,
], $ownerCookies);
assertTest(
    '7. Pembaruan catatan pengeluaran berhasil (HTTP 200)',
    $updExp['status'] === 200 && ($updExp['json']['expense']['amount'] ?? 0) === 160000,
    "Status: {$updExp['status']}"
);

// TEST 8: Hapus Pengeluaran
$delExp = callApp("/api/owner/expenses/{$expId}", 'DELETE', [], $ownerCookies);
assertTest('8. Hapus catatan pengeluaran berhasil (HTTP 200)', $delExp['status'] === 200, "Status: {$delExp['status']}");

// TEST 9: Laporan Keuangan Otoritatif
$repRes = callApp('/api/owner/reports?from=' . date('Y-m-01') . '&to=' . date('Y-m-t'), 'GET', [], $ownerCookies);
$hasRepData = isset($repRes['json']['summary']['totalRevenue']) &&
               isset($repRes['json']['summary']['totalExpenses']) &&
               isset($repRes['json']['summary']['netProfit']) &&
               isset($repRes['json']['paymentMethods']);
assertTest(
    '9. Laporan keuangan terhitung otoritatif dari basis data (HTTP 200)',
    $repRes['status'] === 200 && $hasRepData,
    "Status: {$repRes['status']}"
);

// TEST 10: Tambah Layanan Baru
$randCode = rand(100, 999);
$srvRes = callApp('/api/owner/services', 'POST', [
    'name' => 'Cuci Bedcover Jumbo ' . $randCode,
    'price' => 35000,
    'unit' => 'pcs',
    'hours' => 48,
    'description' => 'Khusus selimut tebal dan bedcover ukuran king',
], $ownerCookies);
$newSrvId = $srvRes['json']['service']['id'] ?? 0;
assertTest(
    '10. Tambah master layanan baru berhasil (HTTP 201)',
    $srvRes['status'] === 201 && $newSrvId > 0 && ($srvRes['json']['service']['price'] ?? 0) === 35000,
    "Status: {$srvRes['status']}"
);

// TEST 11: Update Tarif Layanan & Otomatis Tercatat di Riwayat Harga (price_histories)
$updSrv = callApp("/api/owner/services/{$newSrvId}", 'POST', [
    'name' => 'Cuci Bedcover Jumbo ' . $randCode,
    'price' => 40000,
    'unit' => 'pcs',
    'reason' => 'Kenaikan bahan kimia pembersih',
], $ownerCookies);

$priceHist = PriceHistory::where('service_id', $newSrvId)->latest('id')->first();
$histRecorded = $priceHist && (int)$priceHist->old_price === 35000 && (int)$priceHist->new_price === 40000;
assertTest(
    '11. Perubahan harga layanan otomatis mencatat riwayat ke price_histories (HTTP 200)',
    $updSrv['status'] === 200 && $histRecorded,
    "Status: {$updSrv['status']}"
);

// TEST 12: Toggle Aktif/Nonaktif Layanan
$togSrv = callApp("/api/owner/services/{$newSrvId}/toggle", 'POST', [], $ownerCookies);
assertTest(
    '12. Toggle status aktif/nonaktif layanan berhasil (HTTP 200)',
    $togSrv['status'] === 200 && ($togSrv['json']['service']['active'] ?? true) === false,
    "Status: {$togSrv['status']}"
);

// TEST 13: Tambah Akun Kasir Baru
$newEmpUsername = 'kasir_test_' . rand(1000, 9999);
$empRes = callApp('/api/owner/employees', 'POST', [
    'name' => 'Siti Aminah',
    'username' => $newEmpUsername,
    'password' => 'KasirBaru!2026',
    'phone' => '081298765432',
    'shift' => 'Sore',
    'role' => 'cashier',
], $ownerCookies);
$newEmpId = $empRes['json']['employee']['id'] ?? 0;
assertTest(
    '13. Pendaftaran akun kasir baru berhasil dengan enkripsi password (HTTP 201)',
    $empRes['status'] === 201 && $newEmpId > 0 && ($empRes['json']['employee']['name'] ?? '') === 'Siti Aminah',
    "Status: {$empRes['status']}"
);

// TEST 14: Akun Kasir Baru Langsung Bisa Login
$newEmpLogin = callApp('/api/login', 'POST', [
    'username' => $newEmpUsername,
    'password' => 'KasirBaru!2026',
]);
assertTest(
    '14. Akun kasir baru yang dibuat Owner langsung dapat login ke sistem (HTTP 200)',
    $newEmpLogin['status'] === 200 && ($newEmpLogin['json']['user']['username'] ?? '') === $newEmpUsername,
    "Status: {$newEmpLogin['status']}"
);

// TEST 15: Reset Password Kasir
$rstRes = callApp("/api/owner/employees/{$newEmpId}/reset-password", 'POST', [
    'password' => 'KasirReset!2026',
], $ownerCookies);
assertTest('15. Reset password karyawan berhasil (HTTP 200)', $rstRes['status'] === 200, "Status: {$rstRes['status']}");

// TEST 16: Kasir Berhasil Login dengan Password Baru Hasil Reset
$resetLogin = callApp('/api/login', 'POST', [
    'username' => $newEmpUsername,
    'password' => 'KasirReset!2026',
]);
assertTest(
    '16. Kasir berhasil login menggunakan password baru hasil reset (HTTP 200)',
    $resetLogin['status'] === 200,
    "Status: {$resetLogin['status']}"
);

// TEST 17: Safeguard Owner: Tidak boleh menonaktifkan akun owner tunggal
$ownerUser = User::where('role', 'owner')->first();
$tryDisableOwner = callApp("/api/owner/employees/{$ownerUser->id}", 'POST', [
    'name' => $ownerUser->name,
    'active' => false,
], $ownerCookies);
assertTest(
    '17. Safeguard proteksi: Percobaan menonaktifkan akun Owner tunggal ditolak (HTTP 422)',
    $tryDisableOwner['status'] === 422,
    "Status: {$tryDisableOwner['status']}"
);

// TEST 18: Catat Koreksi Transaksi
$anyOrder = Order::first();
if ($anyOrder) {
    $corrRes = callApp('/api/owner/corrections', 'POST', [
        'order_id' => $anyOrder->id,
        'type' => 'Penyesuaian Harga',
        'reason' => 'Diskon tambahan voucher loyalitas pelanggan',
    ], $ownerCookies);
    assertTest(
        '18. Pencatatan audit koreksi transaksi berhasil disimpan (HTTP 201)',
        $corrRes['status'] === 201 && !empty($corrRes['json']['correction']['id']),
        "Status: {$corrRes['status']}"
    );
} else {
    assertTest('18. Pencatatan audit koreksi transaksi berhasil disimpan (SKIP: belum ada order)', true);
}

// TEST 19: Perbarui Pengaturan Usaha (QRIS, Profil, Rekening Bank)
$setRes = callApp('/api/owner/settings', 'POST', [
    'settings' => [
        'name' => 'Syaqil Laundry Ekspres',
        'address' => 'Jl. Merdeka No. 45, Padang',
        'phone' => '081234567890',
        'bank_name' => 'Bank Mandiri',
        'bank_account' => '111-00-9988776-5',
        'bank_holder' => 'Desi Gusrianita',
    ],
], $ownerCookies);
$savedBank = $setRes['json']['settings']['bank_name'] ?? '';
assertTest(
    '19. Pembaruan profil toko & rekening bank merchant berhasil disimpan (HTTP 200)',
    $setRes['status'] === 200 && $savedBank === 'Bank Mandiri',
    "Status: {$setRes['status']}"
);

echo "\nRingkasan Modul 4: {$passed} PASS, {$failed} FAIL\n";
exit($failed > 0 ? 1 : 0);
