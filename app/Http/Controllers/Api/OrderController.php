<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Service;
use App\Models\StatusHistory;
use App\Services\OrderCalculationService;
use App\Services\WhatsAppNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderController extends Controller
{
    public function __construct(
        protected OrderCalculationService $calculationService,
        protected WhatsAppNotificationService $waService
    ) {}

    /**
     * Memuat data awal kasir (layanan, pelanggan, order aktif, dan pengaturan toko).
     */
    public function bootstrap(): JsonResponse
    {
        $customers = Customer::leftJoin('orders', 'customers.id', '=', 'orders.customer_id')
            ->selectRaw('customers.*, COUNT(orders.id) AS order_count, MAX(orders.created_at) AS last_order')
            ->groupBy('customers.id')
            ->orderBy('customers.name')
            ->get();

        $orders = Order::with(['items', 'history.user', 'latestNotification', 'cashier'])
            ->orderByDesc('id')
            ->get()
            ->map(fn(Order $o) => $o->toFrontendArray())
            ->values();

        $services = Service::where('active', true)->orderBy('id')->get();
        $settings = BusinessSetting::allAsArray();

        return response()->json([
            'customers' => $customers,
            'orders' => $orders,
            'services' => $services,
            'settings' => $settings,
        ]);
    }

    /**
     * Menyimpan order transaksi kasir baru dengan kalkulasi otoritatif di backend,
     * dilanjutkan dengan pengiriman notifikasi WhatsApp bukti transaksi secara resilien.
     */
    public function store(Request $request): JsonResponse
    {
        $discountPercent = $request->input('discount_percent', 0);

        $key = (string)$request->input('request_key', '');
        if (!preg_match('/^[a-zA-Z0-9-]{16,80}$/D', $key)) {
            return response()->json(['error' => 'Identitas transaksi tidak valid.'], 422);
        }

        $userId = (int)$request->user()->id;
        $fingerprint = $this->calculationService->checkoutFingerprint($request->all());

        // Idempotency & deteksi konflik draft
        $existing = Order::with(['items', 'history.user', 'latestNotification', 'cashier'])
            ->where('request_key', $key)
            ->first();

        if ($existing) {
            if ((int)$existing->cashier_id !== $userId) {
                return response()->json(['error' => 'Konflik identitas transaksi.'], 409);
            }
            if (!$existing->request_hash || !hash_equals((string)$existing->request_hash, $fingerprint)) {
                return response()->json([
                    'error' => 'Draft ini memakai nomor yang sudah digunakan pada nota ' . $existing->number . '. Isinya berbeda dan belum disimpan. Periksa nota tersebut sebelum membuat nomor baru.',
                    'code' => 'draft_conflict',
                ], 409);
            }
            return response()->json(['order' => $existing->toFrontendArray()]);
        }

        $customerId = (int)$request->input('customer_id', 0);
        $customer = Customer::find($customerId);
        if (!$customer) {
            return response()->json(['error' => 'Pilih pelanggan terlebih dahulu.'], 422);
        }

        $services = Service::where('active', true)->get()->keyBy('id')->toArray();

        try {
            $calc = $this->calculationService->calculate(
                $request->input('items', []),
                $services,
                $discountPercent
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        // Verifikasi keselarasan nominal dengan frontend
        try {
            $expectedTotal = $this->calculationService->wholeMoney($request->input('expected_total'));
            if ($expectedTotal !== $calc['total']) {
                return response()->json([
                    'error' => 'Harga layanan berubah. Muat ulang halaman dan susun ulang item sebelum membayar.'
                ], 409);
            }
        } catch (InvalidArgumentException) {
            return response()->json([
                'error' => 'Harga layanan berubah. Muat ulang halaman dan susun ulang item sebelum membayar.'
            ], 409);
        }

        $paymentMethod = (string)$request->input('method', '');
        $settings = BusinessSetting::allAsArray();
        $configured = $paymentMethod === 'qris'
            ? !empty($settings['qris_image'])
            : (!empty($settings['bank_name']) && !empty($settings['bank_account']) && !empty($settings['bank_holder']));

        try {
            $payment = $this->calculationService->validatePayment(
                $paymentMethod,
                $request->input('paid', 0),
                $calc['total'],
                $configured,
                $request->boolean('confirmed')
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $notes = trim((string)$request->input('notes', ''));
        if (mb_strlen($notes) > 1000) {
            return response()->json(['error' => 'Catatan maksimal 1.000 karakter.'], 422);
        }

        // Pilihan parfum dihapus sesuai instruksi
        $perfume = null;

        // Estimasi selesai berdasarkan durasi layanan
        $hours = array_filter(array_map(fn($i) => $i['duration_hours'] ?? null, $calc['items']));
        $dueAt = count($hours) > 0 ? now()->addHours(max($hours)) : null;

        $paymentStatus = $payment['payment_status'] ?? ($paymentMethod === 'later' ? 'unpaid' : 'paid');

        $order = DB::transaction(function () use ($key, $fingerprint, $customer, $userId, $calc, $paymentMethod, $payment, $paymentStatus, $notes, $perfume, $dueAt) {
            $order = Order::create([
                'request_key' => $key,
                'request_hash' => $fingerprint,
                'customer_id' => $customer->id,
                'cashier_id' => $userId,
                'customer_name' => $customer->name,
                'customer_phone' => $customer->phone,
                'subtotal' => $calc['subtotal'],
                'discount_percent' => $calc['discount_percent'],
                'discount' => $calc['discount'],
                'total' => $calc['total'],
                'method' => $paymentMethod,
                'paid' => $payment['paid'],
                'change_amount' => $payment['change'],
                'payment_status' => $paymentStatus,
                'status' => 'Diterima',
                'notes' => $notes,
                'perfume' => $perfume,
                'public_token' => bin2hex(random_bytes(16)),
                'due_at' => $dueAt,
                'created_at' => now(),
            ]);

            $orderNumber = 'SYQ-' . now()->format('Ymd') . '-' . str_pad((string)$order->id, 4, '0', STR_PAD_LEFT);
            $order->update(['number' => $orderNumber]);

            foreach ($calc['items'] as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'service_id' => $item['service_id'],
                    'service_name' => $item['service_name'],
                    'unit' => $item['unit'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'category' => $item['category'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            StatusHistory::create([
                'order_id' => $order->id,
                'status' => 'Diterima',
                'user_id' => $userId,
                'created_at' => now(),
            ]);

            return $order;
        });

        // Pengiriman notifikasi WhatsApp bukti transaksi secara resilien
        // (Transaksi order tetap aman dan berhasil disimpan apapun kondisi koneksi gateway)
        $waResult = $this->waService->sendReceiptNotification($order);

        $order->load(['items', 'history.user', 'latestNotification', 'cashier']);
        $orderArray = $order->toFrontendArray();
        $orderArray['whatsapp'] = $waResult;

        return response()->json(['order' => $orderArray], 201);
    }

    /**
     * Memperbarui status pesanan (Diterima -> Diproses -> Selesai -> Diambil).
     * Jika berubah menjadi Selesai, memicu notifikasi WhatsApp siap diambil.
     */
    public function updateStatus(Request $request, int|string $id): JsonResponse
    {
        $newStatus = (string)$request->input('status', '');
        $userId = (int)$request->user()->id;

        try {
            $order = DB::transaction(function () use ($id, $newStatus, $userId) {
                $order = Order::lockForUpdate()->find((int)$id);
                if (!$order) {
                    return null;
                }

                if (!$this->calculationService->validTransition($order->status, $newStatus)) {
                    throw new InvalidArgumentException('Status harus diperbarui berurutan. Muat ulang untuk melihat status terbaru.');
                }

                $order->update(['status' => $newStatus]);

                StatusHistory::create([
                    'order_id' => $order->id,
                    'status' => $newStatus,
                    'user_id' => $userId,
                    'created_at' => now(),
                ]);

                return $order;
            });
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        if (!$order) {
            return response()->json(['error' => 'Pesanan tidak ditemukan.'], 404);
        }

        // Jika status berubah menjadi Selesai, kirim notifikasi WhatsApp siap diambil secara resilien
        if ($newStatus === 'Selesai') {
            $this->waService->sendStatusReadyNotification($order);
        }

        $order->load(['items', 'history.user', 'latestNotification', 'cashier']);
        return response()->json(['order' => $order->toFrontendArray()]);
    }

    /**
     * Detail satu pesanan.
     */
    public function show(int|string $id): JsonResponse
    {
        $order = Order::with(['items', 'history.user', 'latestNotification', 'cashier'])->find((int)$id);
        if (!$order) {
            return response()->json(['error' => 'Pesanan tidak ditemukan.'], 404);
        }

        return response()->json(['order' => $order->toFrontendArray()]);
    }

    /**
     * Data struk terstruktur untuk cetak nota printer thermal (58mm/80mm) dan dokumen PDF.
     */
    public function receipt(int|string $id): JsonResponse
    {
        $order = Order::with(['items', 'history.user', 'cashier'])->find((int)$id);
        if (!$order) {
            return response()->json(['error' => 'Pesanan tidak ditemukan.'], 404);
        }

        $settings = BusinessSetting::allAsArray();

        return response()->json([
            'order' => $order->toFrontendArray(),
            'store' => [
                'name' => $settings['name'] ?? 'Syaqil Laundry',
                'address' => $settings['address'] ?? 'Jl. Raya Laundry No. 10',
                'phone' => $settings['phone'] ?? '081234567890',
            ],
            'print_at' => now()->format('d M Y H:i:s'),
        ]);
    }

    /**
     * Pengiriman manual / coba ulang notifikasi WhatsApp untuk nota tertentu.
     */
    public function sendWhatsApp(Request $request, int|string $id): JsonResponse
    {
        $order = Order::find((int)$id);
        if (!$order) {
            return response()->json(['error' => 'Pesanan tidak ditemukan.'], 404);
        }

        $type = (string)$request->input('type', $order->status === 'Selesai' ? 'status_ready' : 'receipt');
        $result = $type === 'status_ready'
            ? $this->waService->sendStatusReadyNotification($order)
            : $this->waService->sendReceiptNotification($order);

        return response()->json($result);
    }

    /**
     * Tampilan publik nota digital mandiri yang dapat dibuka langsung oleh pelanggan via WhatsApp.
     */
    public function publicReceipt(string $token)
    {
        $order = Order::with(['items', 'history.user', 'cashier'])
            ->where('public_token', $token)
            ->first();

        if (!$order) {
            return response()->view('public_receipt_not_found', [], 404);
        }

        $settings = BusinessSetting::allAsArray();

        return response()->view('public_receipt', [
            'order' => $order,
            'settings' => $settings,
            'store' => [
                'name' => $settings['name'] ?? config('app.business_name', 'Syaqil Laundry'),
                'address' => $settings['address'] ?? config('app.business_address', 'Jl. Merdeka No. 45, Padang'),
                'phone' => $settings['phone'] ?? config('app.business_phone', '081234567890'),
            ],
        ]);
    }

    /**
     * Data JSON nota digital publik berdasarkan token unik.
     */
    public function publicReceiptJson(string $token): JsonResponse
    {
        $order = Order::with(['items', 'history'])
            ->where('public_token', $token)
            ->first();

        if (!$order) {
            return response()->json(['error' => 'Nota digital tidak ditemukan.'], 404);
        }

        $settings = BusinessSetting::allAsArray();

        return response()->json([
            'order' => $order->toPublicReceiptArray(),
            'store' => [
                'name' => $settings['name'] ?? config('app.business_name', 'Syaqil Laundry'),
                'address' => $settings['address'] ?? config('app.business_address', 'Jl. Merdeka No. 45, Padang'),
                'phone' => $settings['phone'] ?? config('app.business_phone', '081234567890'),
            ],
            'print_at' => now()->format('d M Y H:i:s'),
        ]);
    }

    /**
     * Pelunasan pembayaran untuk pesanan yang berstatus belum bayar.
     */
    public function settlePayment(Request $request, int|string $id): JsonResponse
    {
        $order = Order::find((int)$id);
        if (!$order) {
            return response()->json(['error' => 'Pesanan tidak ditemukan.'], 404);
        }

        if ($order->payment_status === 'paid' && $order->method !== 'later') {
            return response()->json(['error' => 'Pesanan ini sudah lunas.'], 422);
        }

        $method = (string)$request->input('method', 'cash');
        try {
            $payment = $this->calculationService->validatePayment(
                $method,
                $request->input('paid', $order->total),
                $order->total,
                true,
                true
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $order->update([
            'method' => $method,
            'paid' => $payment['paid'],
            'change_amount' => $payment['change'],
            'payment_status' => 'paid',
        ]);

        $order->load(['items', 'history.user', 'latestNotification', 'cashier']);
        return response()->json(['order' => $order->toFrontendArray()]);
    }
}
