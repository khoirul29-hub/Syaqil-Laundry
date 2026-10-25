<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Services\OrderCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class CustomerController extends Controller
{
    public function __construct(
        protected OrderCalculationService $calculationService
    ) {}

    /**
     * Simpan data pelanggan baru.
     */
    public function store(Request $request): JsonResponse
    {
        $name = trim((string)$request->input('name', ''));

        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            return response()->json(['error' => 'Nama harus berisi 2–100 karakter.'], 422);
        }

        try {
            $phone = $this->calculationService->normalizePhone((string)$request->input('phone', ''));
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        if (Customer::where('phone', $phone)->exists()) {
            return response()->json(['error' => 'Nomor WhatsApp sudah terdaftar. Cari pelanggan tersebut.'], 409);
        }

        $customer = Customer::create([
            'name' => $name,
            'phone' => $phone,
            'notes' => '',
            'created_at' => now(),
        ]);

        $customerData = $customer->toArray();
        $customerData['order_count'] = 0;
        $customerData['last_order'] = null;

        return response()->json(['customer' => $customerData], 201);
    }

    /**
     * Perbarui data pelanggan.
     */
    public function update(Request $request, int|string $id): JsonResponse
    {
        $customer = Customer::find((int)$id);
        if (!$customer) {
            return response()->json(['error' => 'Pelanggan tidak ditemukan.'], 404);
        }

        $name = trim((string)$request->input('name', ''));
        $notes = trim((string)$request->input('notes', ''));

        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            return response()->json(['error' => 'Nama harus berisi 2–100 karakter.'], 422);
        }
        if (mb_strlen($notes) > 1000) {
            return response()->json(['error' => 'Catatan maksimal 1.000 karakter.'], 422);
        }

        try {
            $phone = $this->calculationService->normalizePhone((string)$request->input('phone', ''));
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        if (Customer::where('phone', $phone)->where('id', '<>', $customer->id)->exists()) {
            return response()->json(['error' => 'Nomor WhatsApp sudah terdaftar untuk pelanggan lain.'], 409);
        }

        $customer->update([
            'name' => $name,
            'phone' => $phone,
            'notes' => $notes,
        ]);

        $orderCount = Order::where('customer_id', $customer->id)->count();
        $lastOrder = Order::where('customer_id', $customer->id)->max('created_at');

        $customerData = $customer->toArray();
        $customerData['order_count'] = $orderCount;
        $customerData['last_order'] = $lastOrder;

        return response()->json(['customer' => $customerData]);
    }
}
