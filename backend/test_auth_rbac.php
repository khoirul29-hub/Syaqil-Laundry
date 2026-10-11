<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

echo "=== TEST SUITE MODUL 2: AUTENTIKASI & RBAC ===\n\n";

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

// Initial bootstrap to configure test user
$initApp = require __DIR__ . '/bootstrap/app.php';
$initKernel = $initApp->make(Illuminate\Contracts\Http\Kernel::class);
$initKernel->bootstrap();

RateLimiter::clear('owner|127.0.0.1');
RateLimiter::clear('kasir|127.0.0.1');
RateLimiter::clear('test_inactive|127.0.0.1');
RateLimiter::clear('test_ratelimit|127.0.0.1');

User::updateOrCreate(
    ['username' => 'test_inactive'],
    [
        'name' => 'Akun Nonaktif',
        'password_hash' => Hash::make('Password!2026'),
        'role' => 'cashier',
        'active' => false,
    ]
);

// 1. Login Owner Berhasil
$res1 = callApp('/api/login', 'POST', ['username' => 'owner', 'password' => 'OwnerSyaqil!2026']);
$ownerCookies = $res1['cookies'];
assertTest(
    '1. Login Owner berhasil dengan kredensial benar',
    $res1['status'] === 200 && ($res1['json']['user']['role'] ?? '') === 'owner',
    "Status: {$res1['status']}"
);

// 2. Login Kasir Berhasil
$res2 = callApp('/api/login', 'POST', ['username' => 'kasir', 'password' => 'KasirSyaqil!2026']);
$kasirCookies = $res2['cookies'];
assertTest(
    '2. Login Kasir berhasil dengan kredensial benar',
    $res2['status'] === 200 && ($res2['json']['user']['role'] ?? '') === 'cashier',
    "Status: {$res2['status']}"
);

// 3. Login Password Salah Ditolak
$res3 = callApp('/api/login', 'POST', ['username' => 'kasir', 'password' => 'PasswordSalah123']);
assertTest(
    '3. Login dengan password salah ditolak (HTTP 401)',
    $res3['status'] === 401 && isset($res3['json']['error']),
    "Status: {$res3['status']}"
);

// 4. Login Akun Tidak Aktif Ditolak
$res4 = callApp('/api/login', 'POST', ['username' => 'test_inactive', 'password' => 'Password!2026']);
assertTest(
    '4. Login akun dinonaktifkan ditolak (HTTP 403)',
    $res4['status'] === 403 && isset($res4['json']['error']),
    "Status: {$res4['status']}"
);

// 5. Pengguna Belum Login Mengakses Endpoint Terlindungi
$res5 = callApp('/api/me', 'GET');
assertTest(
    '5. Pengguna yang belum login ditolak mengakses endpoint terlindungi (HTTP 401)',
    $res5['status'] === 401,
    "Status: {$res5['status']}"
);

// 6. Owner Mengakses Endpoint Owner (/api/owner/ping)
$res6 = callApp('/api/owner/ping', 'GET', [], $ownerCookies);
assertTest(
    '6. Owner berhasil mengakses endpoint khusus Owner (HTTP 200)',
    $res6['status'] === 200 && ($res6['json']['role'] ?? '') === 'owner',
    "Status: {$res6['status']} Body: {$res6['content']}"
);

// 7. Kasir TIDAK DAPAT Mengakses Endpoint Owner (/api/owner/ping) -> Harus 403
$res7 = callApp('/api/owner/ping', 'GET', [], $kasirCookies);
assertTest(
    '7. Kasir ditolak mengakses endpoint khusus Owner (RBAC HTTP 403 Forbidden)',
    $res7['status'] === 403 && isset($res7['json']['error']),
    "Status: {$res7['status']}"
);

// 8. Kasir Mengakses Endpoint Kasir (/api/cashier/ping)
$res8 = callApp('/api/cashier/ping', 'GET', [], $kasirCookies);
assertTest(
    '8. Kasir berhasil mengakses endpoint meja Kasir (HTTP 200)',
    $res8['status'] === 200 && ($res8['json']['role'] ?? '') === 'cashier',
    "Status: {$res8['status']}"
);

// 9. Session Endpoint Mempertahankan Sesi Valid (/api/session)
$res9 = callApp('/api/session', 'GET', [], $ownerCookies);
assertTest(
    '9. Sesi valid tetap aktif saat refresh/pemeriksaan session (/api/session)',
    $res9['status'] === 200 && ($res9['json']['user']['username'] ?? '') === 'owner',
    "Status: {$res9['status']} User: " . ($res9['json']['user']['username'] ?? 'null')
);

// 10. Logout Mengakhiri Sesi
$res10 = callApp('/api/logout', 'POST', [], $ownerCookies);
assertTest(
    '10. Logout berhasil mengakhiri sesi di server',
    $res10['status'] === 200 && ($res10['json']['ok'] ?? false) === true,
    "Status: {$res10['status']}"
);

// 11. Sesi Kedaluwarsa / Tidak Ada Sesi
$res11 = callApp('/api/cashier/ping', 'GET', [], ['syaqil_laundry_session' => 'invalid_expired_token']);
assertTest(
    '11. Permintaan dengan sesi kedaluwarsa/tidak valid ditolak (HTTP 401)',
    $res11['status'] === 401,
    "Status: {$res11['status']}"
);

// 12. Rate Limiting Login (Max 5 attempts)
$throttled = false;
RateLimiter::clear('test_ratelimit|127.0.0.1');
for ($i = 0; $i < 7; $i++) {
    $resRate = callApp('/api/login', 'POST', ['username' => 'test_ratelimit', 'password' => 'WrongPass']);
    if ($resRate['status'] === 429) {
        $throttled = true;
        break;
    }
}
assertTest(
    '12. Rate limiting login aktif membatasi percobaan berulang (HTTP 429)',
    $throttled,
    "Tidak mendapat HTTP 429 setelah 6 percobaan gagal"
);

echo "\nRingkasan: {$passed} PASS, {$failed} FAIL\n";
exit($failed > 0 ? 1 : 0);
