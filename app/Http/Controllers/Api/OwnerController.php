<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountActivity;
use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderCorrection;
use App\Models\OrderItem;
use App\Models\PriceHistory;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class OwnerController extends Controller
{
    /**
     * Memuat seluruh data master, operasional, dan analitik untuk workspace Owner.
     */
    public function bootstrap(): JsonResponse
    {
        // 1. Data Pesanan & Nota Lengkap
        $orders = Order::with(['items', 'history.user', 'latestNotification', 'cashier'])
            ->orderByDesc('id')
            ->get();

        $receipts = $orders->map(function (Order $order) {
            $firstItem = $order->items->first();
            $totalWeight = (float) $order->items->sum('quantity');
            return [
                'id' => (int)$order->id,
                'number' => $order->number ?: 'SYQ-' . $order->id,
                'date' => $order->created_at ? $order->created_at->format('Y-m-d') : date('Y-m-d'),
                'time' => $order->created_at ? $order->created_at->format('H:i') : '00:00',
                'customer' => $order->customer_name,
                'phone' => $order->customer_phone,
                'service' => $firstItem ? $firstItem->service_name : 'Cucian',
                'employee' => $order->cashier ? $order->cashier->name : 'Kasir',
                'method' => match ($order->method) {
                    'cash' => 'Tunai',
                    'qris' => 'QRIS',
                    'transfer' => 'Transfer',
                    'later' => 'Bayar Nanti',
                    default => ucfirst($order->method),
                },
                'amount' => (int)$order->total,
                'total' => (int)$order->total,
                'weight' => $totalWeight > 0 ? $totalWeight : 1.0,
                'subtotal' => (int)$order->subtotal,
                'discount' => (int)$order->discount,
                'discount_percent' => (float)$order->discount_percent,
                'paid' => (int)$order->paid,
                'change_amount' => (int)$order->change_amount,
                'status' => $order->status,
                'payment_status' => $order->payment_status ?: 'paid',
                'notes' => $order->notes,
                'items' => $order->items->map(fn(OrderItem $i) => [
                    'id' => $i->id,
                    'service_name' => $i->service_name,
                    'unit' => $i->unit,
                    'price' => (int)$i->price,
                    'quantity' => (float)$i->quantity,
                    'category' => $i->category,
                    'subtotal' => (int)$i->subtotal,
                ])->values()->all(),
            ];
        })->values()->all();

        // 2. Data Pelanggan Lengkap dengan Agregat
        $customers = Customer::leftJoin('orders', 'customers.id', '=', 'orders.customer_id')
            ->selectRaw('customers.*, COUNT(orders.id) AS order_count, COALESCE(SUM(orders.total), 0) AS total_spent, MAX(orders.created_at) AS last_order')
            ->groupBy('customers.id')
            ->orderBy('customers.name')
            ->get()
            ->map(fn($c) => [
                'id' => (int)$c->id,
                'name' => $c->name,
                'phone' => $c->phone ?: '',
                'address' => $c->address ?: '',
                'notes' => $c->notes ?: '',
                'order_count' => (int)$c->order_count,
                'total_spent' => (int)$c->total_spent,
                'last_order' => $c->last_order ?: '',
                'transactions' => (int)$c->order_count,
                'spent' => (int)$c->total_spent,
                'last' => $c->last_order ? (is_string($c->last_order) ? substr($c->last_order, 0, 10) : $c->last_order->format('Y-m-d')) : '',
                'category' => ((int)$c->order_count >= 10 || (int)$c->total_spent >= 300000) ? 'Member' : 'Reguler',
                'joined' => $c->created_at ? $c->created_at->format('Y-m-d') : date('Y-m-d'),
                'email' => $c->email ?: '',
            ])
            ->values()->all();

        // 3. Pengeluaran
        $expenses = Expense::orderByDesc('date')->orderByDesc('id')->get()->map(fn(Expense $e) => [
            'id' => (int)$e->id,
            'date' => $e->date ? $e->date->format('Y-m-d') : date('Y-m-d'),
            'category' => $e->category,
            'description' => $e->description,
            'amount' => (int)$e->amount,
            'method' => $e->method,
            'actor' => $e->actor,
        ])->values()->all();

        // 4. Layanan & Tarif
        $services = Service::orderBy('id')->get()->map(fn(Service $s) => [
            'id' => (int)$s->id,
            'name' => $s->name,
            'price' => (int)$s->price,
            'unit' => $s->unit,
            'duration_hours' => (int)($s->duration_hours ?? 0),
            'duration' => ($s->duration_hours ?? 0) === 24 ? 'day' : (($s->duration_hours ?? 0) > 0 ? 'hours' : 'normal'),
            'hours' => (int)($s->duration_hours ?? 0),
            'active' => (bool)$s->active,
            'description' => $s->description ?: '',
            'tone' => $s->tone ?: 'blue',
            'symbol' => $s->symbol ?: 'shirt',
        ])->values()->all();

        // 5. Riwayat Perubahan Harga
        $priceHistory = PriceHistory::orderByDesc('date')->orderByDesc('id')->get()->map(fn(PriceHistory $h) => [
            'id' => (int)$h->id,
            'date' => $h->date ? $h->date->format('Y-m-d') : date('Y-m-d'),
            'service' => $h->service_name,
            'oldPrice' => (int)$h->old_price,
            'newPrice' => (int)$h->new_price,
            'actor' => $h->actor,
            'reason' => $h->reason ?: '',
        ])->values()->all();

        // 6. Karyawan (Tanpa password hash)
        $employees = User::orderBy('id')->get()->map(fn(User $u) => [
            'id' => (int)$u->id,
            'name' => $u->name,
            'username' => $u->username,
            'role' => $u->role === 'owner' ? 'Owner' : 'Kasir',
            'active' => (bool)$u->active,
            'phone' => $u->phone ?: '',
            'shift' => $u->shift ?: 'Pagi',
            'lastLogin' => $u->last_login_at ? $u->last_login_at->format('Y-m-d\TH:i') : '',
            'resets' => 0,
        ])->values()->all();

        // 7. Koreksi Transaksi
        $corrections = OrderCorrection::with(['order', 'user'])->orderByDesc('id')->get()->map(fn(OrderCorrection $c) => [
            'id' => (int)$c->id,
            'order_id' => (int)$c->order_id,
            'number' => $c->order ? $c->order->number : '',
            'customer' => $c->order ? $c->order->customer_name : '',
            'date' => $c->created_at ? $c->created_at->format('Y-m-d\TH:i') : '',
            'type' => $c->type,
            'reason' => $c->reason,
            'actor' => $c->user ? $c->user->name : 'Owner',
            'old_values' => $c->old_values,
            'new_values' => $c->new_values,
        ])->values()->all();

        // 8. Aktivitas Akun
        $accountActivity = AccountActivity::orderByDesc('id')->limit(50)->get()->map(fn(AccountActivity $a) => [
            'id' => (int)$a->id,
            'date' => $a->created_at ? $a->created_at->format('Y-m-d\TH:i') : '',
            'name' => $a->name,
            'type' => $a->type,
            'detail' => $a->detail,
        ])->values()->all();

        // 9. Pengaturan Usaha
        $settings = BusinessSetting::allAsArray();

        // 10. Ringkasan Keuangan Otoritatif
        $totalRevenue = (int)$orders->sum('total');
        $totalExpenses = (int)Expense::sum('amount');
        $netProfit = $totalRevenue - $totalExpenses;

        return response()->json([
            'receipts' => $receipts,
            'customers' => $customers,
            'expenses' => $expenses,
            'services' => $services,
            'priceHistory' => $priceHistory,
            'employees' => $employees,
            'corrections' => $corrections,
            'accountActivity' => $accountActivity,
            'settings' => $settings,
            'financialSummary' => [
                'totalRevenue' => $totalRevenue,
                'totalExpenses' => $totalExpenses,
                'netProfit' => $netProfit,
                'orderCount' => $orders->count(),
                'customerCount' => count($customers),
            ],
        ]);
    }

    /**
     * Tambah catatan pengeluaran baru.
     */
    public function storeExpense(Request $request): JsonResponse
    {
        $description = trim((string)$request->input('description', ''));
        $category = (string)$request->input('category', 'Operasional');
        $amount = (int)$request->input('amount', 0);
        $date = (string)$request->input('date', date('Y-m-d'));
        $method = (string)$request->input('method', 'Tunai');

        if (mb_strlen($description) < 2 || mb_strlen($description) > 255) {
            return response()->json(['error' => 'Keterangan pengeluaran minimal 2 karakter (maksimal 255).'], 422);
        }
        if ($amount <= 0 || $amount > 100000000) {
            return response()->json(['error' => 'Nominal pengeluaran harus berupa rupiah utuh lebih dari 0.'], 422);
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return response()->json(['error' => 'Format tanggal harus YYYY-MM-DD.'], 422);
        }

        $user = $request->user();
        $expense = Expense::create([
            'user_id' => $user->id,
            'date' => $date,
            'category' => $category,
            'description' => $description,
            'amount' => $amount,
            'method' => $method,
            'actor' => $user->name,
        ]);

        return response()->json(['expense' => [
            'id' => (int)$expense->id,
            'date' => $expense->date->format('Y-m-d'),
            'category' => $expense->category,
            'description' => $expense->description,
            'amount' => (int)$expense->amount,
            'method' => $expense->method,
            'actor' => $expense->actor,
        ]], 201);
    }

    /**
     * Perbarui catatan pengeluaran.
     */
    public function updateExpense(Request $request, int|string $id): JsonResponse
    {
        $expense = Expense::find((int)$id);
        if (!$expense) {
            return response()->json(['error' => 'Catatan pengeluaran tidak ditemukan.'], 404);
        }

        $description = trim((string)$request->input('description', $expense->description));
        $category = (string)$request->input('category', $expense->category);
        $amount = (int)$request->input('amount', $expense->amount);
        $date = (string)$request->input('date', $expense->date->format('Y-m-d'));
        $method = (string)$request->input('method', $expense->method);

        if (mb_strlen($description) < 2) {
            return response()->json(['error' => 'Keterangan pengeluaran minimal 2 karakter.'], 422);
        }
        if ($amount <= 0) {
            return response()->json(['error' => 'Nominal pengeluaran harus lebih dari 0.'], 422);
        }

        $expense->update([
            'date' => $date,
            'category' => $category,
            'description' => $description,
            'amount' => $amount,
            'method' => $method,
        ]);

        return response()->json(['expense' => [
            'id' => (int)$expense->id,
            'date' => $expense->date->format('Y-m-d'),
            'category' => $expense->category,
            'description' => $expense->description,
            'amount' => (int)$expense->amount,
            'method' => $expense->method,
            'actor' => $expense->actor,
        ]]);
    }

    /**
     * Hapus catatan pengeluaran.
     */
    public function deleteExpense(int|string $id): JsonResponse
    {
        $expense = Expense::find((int)$id);
        if (!$expense) {
            return response()->json(['error' => 'Catatan pengeluaran tidak ditemukan.'], 404);
        }

        $expense->delete();
        return response()->json(['ok' => true]);
    }

    /**
     * Laporan & analitik keuangan otoritatif berbasis rentang tanggal.
     */
    public function reports(Request $request): JsonResponse
    {
        $from = (string)$request->input('from', date('Y-m-01'));
        $to = (string)$request->input('to', date('Y-m-t'));

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            return response()->json(['error' => 'Format tanggal filter tidak valid.'], 422);
        }
        if ($from > $to) {
            return response()->json(['error' => 'Tanggal akhir tidak boleh sebelum tanggal awal.'], 422);
        }

        $fromDt = Carbon::parse($from)->startOfDay();
        $toDt = Carbon::parse($to)->endOfDay();

        // 1. Pesanan dalam periode
        $orders = Order::with('items')
            ->whereBetween('created_at', [$fromDt, $toDt])
            ->get();

        $revenue = (int)$orders->sum('total');
        $orderCount = $orders->count();

        // 2. Pengeluaran dalam periode
        $expenses = Expense::whereBetween('date', [$from, $to])->get();
        $totalExpense = (int)$expenses->sum('amount');
        $netProfit = $revenue - $totalExpense;

        // 3. Rincian metode pembayaran
        $paymentMethods = [
            'Tunai' => (int)$orders->where('method', 'cash')->sum('total'),
            'QRIS' => (int)$orders->where('method', 'qris')->sum('total'),
            'Transfer' => (int)$orders->where('method', 'transfer')->sum('total'),
        ];

        // 4. Rincian kategori pengeluaran
        $expenseByCategory = [];
        foreach ($expenses as $e) {
            $cat = $e->category ?: 'Lainnya';
            $expenseByCategory[$cat] = ($expenseByCategory[$cat] ?? 0) + (int)$e->amount;
        }

        // 5. Volume cucian
        $totalKg = (float)OrderItem::whereHas('order', function ($q) use ($fromDt, $toDt) {
            $q->whereBetween('created_at', [$fromDt, $toDt]);
        })->where('unit', 'kg')->sum('quantity');

        return response()->json([
            'period' => ['from' => $from, 'to' => $to],
            'summary' => [
                'totalRevenue' => $revenue,
                'totalExpenses' => $totalExpense,
                'netProfit' => $netProfit,
                'orderCount' => $orderCount,
                'totalKg' => round($totalKg, 2),
            ],
            'paymentMethods' => $paymentMethods,
            'expenseByCategory' => $expenseByCategory,
        ]);
    }

    /**
     * Tambah layanan baru.
     */
    public function storeService(Request $request): JsonResponse
    {
        $name = trim((string)$request->input('name', ''));
        $price = (int)$request->input('price', 0);
        $unit = (string)$request->input('unit', 'kg');
        $hours = (int)$request->input('hours', 0);
        $description = trim((string)$request->input('description', ''));
        $tone = (string)$request->input('tone', 'blue');
        $symbol = (string)$request->input('symbol', 'shirt');

        if (mb_strlen($name) < 3) {
            return response()->json(['error' => 'Nama layanan minimal 3 karakter.'], 422);
        }
        if ($price <= 0 || $price > 10000000) {
            return response()->json(['error' => 'Harga harus berupa rupiah utuh antara 1 dan 10.000.000.'], 422);
        }
        if (!in_array($unit, ['kg', 'pcs', 'pasang'], true)) {
            return response()->json(['error' => 'Pilih satuan layanan yang valid (kg, pcs, atau pasang).'], 422);
        }
        if (Service::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists()) {
            return response()->json(['error' => 'Nama layanan sudah digunakan.'], 409);
        }

        $service = Service::create([
            'name' => $name,
            'price' => $price,
            'unit' => $unit,
            'duration_hours' => $hours > 0 ? $hours : null,
            'active' => true,
            'description' => $description,
            'tone' => $tone,
            'symbol' => $symbol,
        ]);

        return response()->json(['service' => [
            'id' => (int)$service->id,
            'name' => $service->name,
            'price' => (int)$service->price,
            'unit' => $service->unit,
            'duration_hours' => (int)($service->duration_hours ?? 0),
            'active' => (bool)$service->active,
            'description' => $service->description,
            'tone' => $service->tone,
            'symbol' => $service->symbol,
        ]], 201);
    }

    /**
     * Perbarui layanan & tarif. Jika harga berubah, catat riwayat harga.
     */
    public function updateService(Request $request, int|string $id): JsonResponse
    {
        $service = Service::find((int)$id);
        if (!$service) {
            return response()->json(['error' => 'Layanan tidak ditemukan.'], 404);
        }

        $name = trim((string)$request->input('name', $service->name));
        $price = (int)$request->input('price', $service->price);
        $unit = (string)$request->input('unit', $service->unit);
        $hours = (int)$request->input('hours', $service->duration_hours ?? 0);
        $reason = trim((string)$request->input('reason', 'Penyesuaian tarif'));

        if (mb_strlen($name) < 3) {
            return response()->json(['error' => 'Nama layanan minimal 3 karakter.'], 422);
        }
        if ($price <= 0 || $price > 10000000) {
            return response()->json(['error' => 'Harga harus antara 1 dan 10.000.000.'], 422);
        }

        $oldPrice = (int)$service->price;
        $priceChanged = $oldPrice !== $price;

        $service->update([
            'name' => $name,
            'price' => $price,
            'unit' => $unit,
            'duration_hours' => $hours > 0 ? $hours : null,
        ]);

        // Catat ke PriceHistory jika harga berubah
        if ($priceChanged) {
            PriceHistory::create([
                'service_id' => $service->id,
                'service_name' => $service->name,
                'date' => date('Y-m-d'),
                'old_price' => $oldPrice,
                'new_price' => $price,
                'actor' => $request->user()->name,
                'reason' => $reason ?: 'Penyesuaian tarif layanan',
            ]);
        }

        return response()->json(['service' => [
            'id' => (int)$service->id,
            'name' => $service->name,
            'price' => (int)$service->price,
            'unit' => $service->unit,
            'duration_hours' => (int)($service->duration_hours ?? 0),
            'active' => (bool)$service->active,
        ]]);
    }

    /**
     * Aktifkan / Nonaktifkan layanan.
     */
    public function toggleService(int|string $id): JsonResponse
    {
        $service = Service::find((int)$id);
        if (!$service) {
            return response()->json(['error' => 'Layanan tidak ditemukan.'], 404);
        }

        $service->update(['active' => !$service->active]);
        return response()->json(['service' => [
            'id' => (int)$service->id,
            'active' => (bool)$service->active,
        ]]);
    }

    /**
     * Tambah karyawan / akun kasir baru.
     */
    public function storeEmployee(Request $request): JsonResponse
    {
        $name = trim((string)$request->input('name', ''));
        $username = trim(mb_strtolower((string)$request->input('username', '')));
        $password = (string)$request->input('password', '');
        $phone = (string)$request->input('phone', '');
        $shift = (string)$request->input('shift', 'Pagi');
        $role = mb_strtolower((string)$request->input('role', 'cashier')) === 'owner' ? 'owner' : 'cashier';

        if (mb_strlen($name) < 3) {
            return response()->json(['error' => 'Nama lengkap minimal 3 karakter.'], 422);
        }
        if (!preg_match('/^[a-z0-9._]{3,24}$/', $username)) {
            return response()->json(['error' => 'Username harus berupa 3–24 huruf, angka, titik, atau underscore.'], 422);
        }
        if (mb_strlen($password) < 6) {
            return response()->json(['error' => 'Password minimal 6 karakter.'], 422);
        }
        if (User::where('username', $username)->exists()) {
            return response()->json(['error' => 'Username sudah digunakan.'], 409);
        }

        $user = User::create([
            'name' => $name,
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'role' => $role,
            'phone' => $phone,
            'shift' => $shift,
            'active' => true,
        ]);

        AccountActivity::create([
            'user_id' => $user->id,
            'name' => $name,
            'type' => 'Akun Dibuat',
            'detail' => "Akun {$role} {$username} dibuat oleh " . $request->user()->name,
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['employee' => [
            'id' => (int)$user->id,
            'name' => $user->name,
            'username' => $user->username,
            'role' => $user->role === 'owner' ? 'Owner' : 'Kasir',
            'active' => (bool)$user->active,
            'phone' => $user->phone,
            'shift' => $user->shift,
        ]], 201);
    }

    /**
     * Perbarui data karyawan.
     */
    public function updateEmployee(Request $request, int|string $id): JsonResponse
    {
        $user = User::find((int)$id);
        if (!$user) {
            return response()->json(['error' => 'Karyawan tidak ditemukan.'], 404);
        }

        $name = trim((string)$request->input('name', $user->name));
        $phone = (string)$request->input('phone', $user->phone);
        $shift = (string)$request->input('shift', $user->shift);
        $role = mb_strtolower((string)$request->input('role', $user->role)) === 'owner' ? 'owner' : 'cashier';
        $active = $request->boolean('active', $user->active);

        // Safeguard: Tidak boleh menonaktifkan akun owner jika tidak ada owner lain yang aktif
        if ($user->role === 'owner' && (!$active || $role !== 'owner')) {
            $otherOwnerCount = User::where('role', 'owner')->where('active', true)->where('id', '<>', $user->id)->count();
            if ($otherOwnerCount === 0) {
                return response()->json(['error' => 'Minimal satu akun Owner harus tetap aktif.'], 422);
            }
        }

        $user->update([
            'name' => $name,
            'phone' => $phone,
            'shift' => $shift,
            'role' => $role,
            'active' => $active,
        ]);

        AccountActivity::create([
            'user_id' => $user->id,
            'name' => $name,
            'type' => 'Update Data',
            'detail' => "Data karyawan diperbarui oleh " . $request->user()->name,
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['employee' => [
            'id' => (int)$user->id,
            'name' => $user->name,
            'username' => $user->username,
            'role' => $user->role === 'owner' ? 'Owner' : 'Kasir',
            'active' => (bool)$user->active,
            'phone' => $user->phone,
            'shift' => $user->shift,
        ]]);
    }

    /**
     * Reset password karyawan.
     */
    public function resetEmployeePassword(Request $request, int|string $id): JsonResponse
    {
        $user = User::find((int)$id);
        if (!$user) {
            return response()->json(['error' => 'Karyawan tidak ditemukan.'], 404);
        }

        $password = (string)$request->input('password', '');
        if (mb_strlen($password) < 6) {
            return response()->json(['error' => 'Password baru minimal 6 karakter.'], 422);
        }

        $user->update([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        AccountActivity::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'type' => 'Reset Password',
            'detail' => "Password akun {$user->username} direset oleh " . $request->user()->name,
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * Simpan koreksi transaksi / pembatalan nota.
     */
    public function storeCorrection(Request $request): JsonResponse
    {
        $orderId = (int)$request->input('order_id', 0);
        $type = (string)$request->input('type', 'Pembatalan');
        $reason = trim((string)$request->input('reason', ''));

        $order = Order::find($orderId);
        if (!$order) {
            return response()->json(['error' => 'Pesanan tidak ditemukan.'], 404);
        }
        if (mb_strlen($reason) < 3) {
            return response()->json(['error' => 'Alasan koreksi harus diisi minimal 3 karakter.'], 422);
        }

        $correction = OrderCorrection::create([
            'order_id' => $order->id,
            'user_id' => $request->user()->id,
            'type' => $type,
            'reason' => $reason,
            'old_values' => [
                'status' => $order->status,
                'total' => $order->total,
                'items' => $order->items->toArray(),
            ],
            'new_values' => $request->input('new_values', null),
            'created_at' => now(),
        ]);

        // Jika pembatalan, tandai status order atau catatan
        if ($type === 'Pembatalan') {
            $order->update([
                'notes' => trim($order->notes . " [DIBATALKAN: {$reason}]"),
            ]);
        }

        return response()->json(['correction' => [
            'id' => (int)$correction->id,
            'order_id' => (int)$correction->order_id,
            'type' => $correction->type,
            'reason' => $correction->reason,
            'date' => $correction->created_at->format('Y-m-d\TH:i'),
        ]], 201);
    }

    /**
     * Perbarui pengaturan profil bisnis, QRIS, dan rekening bank.
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $settings = $request->input('settings', []);
        if (!is_array($settings)) {
            return response()->json(['error' => 'Format pengaturan tidak valid.'], 422);
        }

        foreach ($settings as $key => $val) {
            BusinessSetting::updateOrCreate(
                ['key' => (string)$key],
                ['value' => is_scalar($val) ? (string)$val : json_encode($val)]
            );
        }

        return response()->json([
            'settings' => BusinessSetting::allAsArray(),
        ]);
    }
}
