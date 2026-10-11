<?php
declare(strict_types=1);
function normalizePhone(string $phone): string {
    $phone = preg_replace('/[\s()+-]/', '', $phone);
    if (str_starts_with($phone, '0')) $phone = '62'.substr($phone, 1);
    if (!preg_match('/^628[1-9][0-9]{7,10}$/D', $phone)) throw new InvalidArgumentException('Nomor WhatsApp tidak valid. Gunakan nomor Indonesia, misalnya 081234567890.');
    return $phone;
}
function wholeMoney(mixed $value): int {
    if (!is_numeric($value) || !is_finite((float)$value) || (float)$value < 0 || (float)$value > 100000000 || (float)$value != floor((float)$value)) throw new InvalidArgumentException('Nominal harus berupa rupiah utuh dan tidak negatif.');
    return (int)$value;
}
function calculateOrder(array $items, array $services, mixed $discountPercent): array {
    if (count($items) < 1 || count($items) > 50) throw new InvalidArgumentException('Tambahkan minimal satu item, maksimal 50 item.');
    $subtotal = 0; $lines = [];
    foreach ($items as $item) {
        $service = $services[(int)($item['service_id'] ?? 0)] ?? null;
        if (!$service) throw new InvalidArgumentException('Layanan tidak tersedia. Muat ulang halaman.');
        $qty = (string)($item['quantity'] ?? '');
        if (!preg_match('/^\d{1,4}(\.\d{1,2})?$/D', $qty) || (float)$qty <= 0 || (float)$qty > 1000) throw new InvalidArgumentException('Berat/jumlah harus lebih dari 0, maksimal 1.000, dengan maksimal 2 desimal.');
        if ($service['unit'] !== 'kg' && (float)$qty !== floor((float)$qty)) throw new InvalidArgumentException('Jumlah item harus berupa bilangan bulat.');
        $category = $item['category'] ?? 'Kiloan';
        if (!in_array($category,['Kiloan','Kemeja','Celana','Jas','Bedcover','Sepatu'],true)) throw new InvalidArgumentException('Jenis cucian tidak valid.');
        $hundredths = (int)round((float)$qty * 100);
        $lineTotal = (int)round($hundredths * (int)$service['price'] / 100);
        $subtotal += $lineTotal;
        $lines[] = ['service_id'=>(int)$service['id'],'service_name'=>$service['name'],'unit'=>$service['unit'],'price'=>(int)$service['price'],'quantity'=>$hundredths/100,'category'=>$category,'subtotal'=>$lineTotal];
    }
    if (!is_scalar($discountPercent) || !preg_match('/^\d+(\.\d{1,2})?$/D',(string)$discountPercent) || (float)$discountPercent > 100) throw new InvalidArgumentException('Diskon harus antara 0–100%, maksimal 2 angka desimal.');
    $basisPoints=(int)round((float)$discountPercent*100);
    $discount=(int)round($subtotal*$basisPoints/10000);
    return ['items'=>$lines,'subtotal'=>$subtotal,'discount_percent'=>(float)$discountPercent,'discount'=>$discount,'total'=>$subtotal-$discount];
}
function validatePayment(string $method, mixed $paid, int $total, bool $configured, bool $confirmed=false): array {
    if (!in_array($method,['cash','qris','transfer'],true)) throw new InvalidArgumentException('Metode pembayaran tidak valid.');
    if ($method !== 'cash' && (!$configured || !$confirmed)) throw new InvalidArgumentException('Pembayaran non-tunai belum dikonfigurasi atau belum dikonfirmasi.');
    $paid = $method === 'cash' ? wholeMoney($paid) : $total;
    if ($paid < $total) throw new InvalidArgumentException('Jumlah dibayar masih kurang.');
    return ['paid'=>$paid,'change'=>$method === 'cash' ? $paid-$total : 0];
}
function validTransition(string $from, string $to): bool {
    return (['Diterima'=>'Diproses','Diproses'=>'Selesai','Selesai'=>'Diambil'][$from] ?? null) === $to;
}
function checkoutFingerprint(array $body): string {
    $items=array_map(fn($i)=>[
        'service_id'=>(int)($i['service_id'] ?? 0),
        'quantity'=>number_format((float)($i['quantity'] ?? 0),2,'.',''),
        'category'=>(string)($i['category'] ?? 'Kiloan')
    ],$body['items'] ?? []);
    return hash('sha256',json_encode([
        'customer_id'=>(int)($body['customer_id'] ?? 0),'items'=>$items,
        'discount_percent'=>number_format((float)($body['discount_percent'] ?? 0),2,'.',''),'method'=>(string)($body['method'] ?? ''),
        'paid'=>(int)($body['paid'] ?? 0),'notes'=>trim((string)($body['notes'] ?? '')),
        'expected_total'=>(int)($body['expected_total'] ?? 0)
    ],JSON_THROW_ON_ERROR));
}
