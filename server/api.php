<?php
declare(strict_types=1);
require __DIR__.'/config.php';
require __DIR__.'/domain.php';
require __DIR__.'/sessions.php';
// Di balik proxy/load balancer (Vercel) HTTPS terdeteksi via X-Forwarded-Proto.
$forwardedProto=strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
$isHttps=(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwardedProto==='https';
session_set_cookie_params(['httponly'=>true,'samesite'=>'Strict','secure'=>$isHttps]);
ini_set('session.use_strict_mode','1');
useDbSessions();
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
function reply(array $data, int $status=200): never { http_response_code($status); echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR); exit; }
function query(string $sql, array $args=[]): PDOStatement { $q=db()->prepare($sql); $q->execute($args); return $q; }
function getOrder(int $id): array {
    $order=query('SELECT o.*,u.name AS cashier_name FROM orders o JOIN users u ON u.id=o.cashier_id WHERE o.id=?',[$id])->fetch();
    if (!$order) reply(['error'=>'Pesanan tidak ditemukan.'],404);
    $order['items']=query('SELECT service_id,service_name,unit,price,quantity,category,subtotal FROM order_items WHERE order_id=? ORDER BY id',[$id])->fetchAll();
    $order['history']=query('SELECT h.status,h.created_at,u.name AS user_name FROM status_history h JOIN users u ON h.user_id=u.id WHERE order_id=? ORDER BY h.id',[$id])->fetchAll();
    $order['notification']=query('SELECT status,message,created_at FROM notifications WHERE order_id=? ORDER BY id DESC LIMIT 1',[$id])->fetch() ?: null;
    return $order;
}
function allOrders(): array {
    $orders=[];
    foreach(query('SELECT o.*,u.name AS cashier_name FROM orders o JOIN users u ON u.id=o.cashier_id ORDER BY o.id DESC')->fetchAll() as $row) {
        $row['items']=[]; $row['history']=[]; $row['notification']=null;
        $orders[$row['id']]=$row;
    }
    foreach(query('SELECT order_id,service_id,service_name,unit,price,quantity,category,subtotal FROM order_items ORDER BY id')->fetchAll() as $item) {
        $id=$item['order_id'];unset($item['order_id']);
        if(isset($orders[$id])) $orders[$id]['items'][]=$item;
    }
    foreach(query('SELECT h.order_id,h.status,h.created_at,u.name AS user_name FROM status_history h JOIN users u ON u.id=h.user_id ORDER BY h.id')->fetchAll() as $history) {
        $id=$history['order_id'];unset($history['order_id']);
        if(isset($orders[$id])) $orders[$id]['history'][]=$history;
    }
    foreach(query('SELECT n.order_id,n.status,n.message,n.created_at FROM notifications n JOIN (SELECT order_id,MAX(id) id FROM notifications GROUP BY order_id) latest ON latest.id=n.id')->fetchAll() as $notification) {
        $id=$notification['order_id'];unset($notification['order_id']);
        if(isset($orders[$id])) $orders[$id]['notification']=$notification;
    }
    return array_values($orders);
}
function allCustomers(): array { return query('SELECT c.*,COUNT(o.id) AS order_count,MAX(o.created_at) AS last_order FROM customers c LEFT JOIN orders o ON c.id=o.customer_id GROUP BY c.id ORDER BY c.name')->fetchAll(); }
function recordNotification(int $orderId): void {
    query('INSERT INTO notifications(order_id,status,message) VALUES(?,?,?)',[$orderId,'not_configured','WhatsApp belum terhubung. Hubungi pelanggan secara manual.']);
}
try {
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    $route=substr(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH),4) ?: '/';
    $method=$_SERVER['REQUEST_METHOD'];
    if ($method === 'GET' && $route === '/session') reply(['user'=>$_SESSION['user'] ?? null,'csrf'=>$_SESSION['csrf'],'settings'=>settings()]);
    if ($method !== 'GET' && !hash_equals($_SESSION['csrf'],$_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) reply(['error'=>'Sesi kedaluwarsa. Muat ulang halaman.'],419);
    $raw=file_get_contents('php://input');
    if (strlen($raw)>100000) reply(['error'=>'Data terlalu besar.'],413);
    $body=$raw ? json_decode($raw,true,32,JSON_THROW_ON_ERROR) : [];
    if (!is_array($body)) throw new InvalidArgumentException('Format permintaan tidak valid.');
    if ($method === 'POST' && $route === '/login') {
        // Di balik proxy (Vercel) REMOTE_ADDR adalah IP internal proxy,
        // jadi pakai IP asli dari X-Forwarded-For bila ada.
        $forwardedParts=explode(',',(string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
        $forwardedIp=trim($forwardedParts[0]);
        $ip=hash('sha256',$forwardedIp !== '' ? $forwardedIp : ($_SERVER['REMOTE_ADDR'] ?? 'local'));
        $attempt=query('SELECT * FROM login_attempts WHERE ip_hash=?',[$ip])->fetch();
        if ($attempt && (int)$attempt['attempts']>=8 && strtotime($attempt['last_attempt'])>time()-300) reply(['error'=>'Terlalu banyak percobaan. Coba lagi dalam 5 menit.'],429);
        $user=query('SELECT * FROM users WHERE username=? AND active=1',[substr((string)($body['username'] ?? ''),0,60)])->fetch();
        if (!$user || !password_verify((string)($body['password'] ?? ''),$user['password_hash'])) {
            query('INSERT INTO login_attempts(ip_hash,attempts,last_attempt) VALUES(?,1,NOW()) ON DUPLICATE KEY UPDATE attempts=IF(last_attempt < NOW()-INTERVAL 5 MINUTE,1,attempts+1),last_attempt=NOW()',[$ip]);
            reply(['error'=>'Username atau password salah.'],401);
        }
        query('DELETE FROM login_attempts WHERE ip_hash=?',[$ip]);
        session_regenerate_id(true);
        $_SESSION['user']=['id'=>(int)$user['id'],'name'=>$user['name'],'role'=>$user['role']];
        $_SESSION['csrf']=bin2hex(random_bytes(32));
        reply(['user'=>$_SESSION['user'],'csrf'=>$_SESSION['csrf']]);
    }
    if (empty($_SESSION['user'])) reply(['error'=>'Silakan masuk untuk melanjutkan.'],401);
    $active=query('SELECT active,role FROM users WHERE id=?',[$_SESSION['user']['id']])->fetch();
    if (!$active || !$active['active'] || !in_array($active['role'],['cashier','owner'],true)) reply(['error'=>'Akses akun dinonaktifkan.'],403);
    $userId=$_SESSION['user']['id'];
    if ($method === 'POST' && $route === '/logout') { $_SESSION=[]; session_destroy(); reply(['ok'=>true]); }
    if ($method === 'GET' && $route === '/bootstrap') reply(['customers'=>allCustomers(),'orders'=>allOrders(),'services'=>query('SELECT * FROM services WHERE active=1 ORDER BY id')->fetchAll(),'settings'=>settings()]);
    if ($method === 'POST' && $route === '/customers') {
        $name=trim((string)($body['name'] ?? '')); $address=trim((string)($body['address'] ?? ''));
        if (mb_strlen($name)<2 || mb_strlen($name)>100) throw new InvalidArgumentException('Nama harus berisi 2–100 karakter.');
        if (mb_strlen($address)>300) throw new InvalidArgumentException('Alamat maksimal 300 karakter.');
        $phone=normalizePhone((string)($body['phone'] ?? ''));
        if (query('SELECT id FROM customers WHERE phone=?',[$phone])->fetch()) reply(['error'=>'Nomor WhatsApp sudah terdaftar. Cari pelanggan tersebut.'],409);
        query('INSERT INTO customers(name,phone,address) VALUES(?,?,?)',[$name,$phone,$address]);
        reply(['customer'=>query('SELECT *,0 AS order_count FROM customers WHERE id=?',[db()->lastInsertId()])->fetch()],201);
    }
    if ($method === 'POST' && preg_match('#^/customers/(\d+)$#',$route,$match)) {
        $id=(int)$match[1];
        if (!query('SELECT id FROM customers WHERE id=?',[$id])->fetch()) reply(['error'=>'Pelanggan tidak ditemukan.'],404);
        $name=trim((string)($body['name'] ?? ''));
        $address=trim((string)($body['address'] ?? ''));
        $notes=trim((string)($body['notes'] ?? ''));
        if (mb_strlen($name)<2 || mb_strlen($name)>100) throw new InvalidArgumentException('Nama harus berisi 2–100 karakter.');
        if (mb_strlen($address)>300) throw new InvalidArgumentException('Alamat maksimal 300 karakter.');
        if (mb_strlen($notes)>1000) throw new InvalidArgumentException('Catatan maksimal 1.000 karakter.');
        $phone=normalizePhone((string)($body['phone'] ?? ''));
        if (query('SELECT id FROM customers WHERE phone=? AND id<>?',[$phone,$id])->fetch()) reply(['error'=>'Nomor WhatsApp sudah terdaftar untuk pelanggan lain.'],409);
        query('UPDATE customers SET name=?,phone=?,address=?,notes=? WHERE id=?',[$name,$phone,$address,$notes,$id]);
        reply(['customer'=>query('SELECT c.*,(SELECT COUNT(*) FROM orders WHERE customer_id=c.id) AS order_count,(SELECT MAX(created_at) FROM orders WHERE customer_id=c.id) AS last_order FROM customers c WHERE c.id=?',[$id])->fetch()]);
    }
    if ($method === 'POST' && $route === '/orders') {
        if (!array_key_exists('discount_percent',$body)) throw new InvalidArgumentException('Diskon sekarang menggunakan persen. Muat ulang halaman kasir sebelum membayar.');
        $key=(string)($body['request_key'] ?? '');
        if (!preg_match('/^[a-zA-Z0-9-]{16,80}$/D',$key)) throw new InvalidArgumentException('Identitas transaksi tidak valid.');
        $fingerprint=checkoutFingerprint($body);
        $existing=query('SELECT id,cashier_id,number,request_hash FROM orders WHERE request_key=?',[$key])->fetch();
        if ($existing) {
            if ((int)$existing['cashier_id']!==$userId) reply(['error'=>'Konflik identitas transaksi.'],409);
            if (!$existing['request_hash'] || !hash_equals($existing['request_hash'],$fingerprint)) reply(['error'=>'Draft ini memakai nomor yang sudah digunakan pada nota '.$existing['number'].'. Isinya berbeda dan belum disimpan. Periksa nota tersebut sebelum membuat nomor baru.','code'=>'draft_conflict'],409);
            reply(['order'=>getOrder((int)$existing['id'])]);
        }
        $customer=query('SELECT * FROM customers WHERE id=?',[(int)($body['customer_id'] ?? 0)])->fetch();
        if (!$customer) throw new InvalidArgumentException('Pilih pelanggan terlebih dahulu.');
        $services=[]; foreach(query('SELECT * FROM services WHERE active=1')->fetchAll() as $s) $services[(int)$s['id']]=$s;
        $calc=calculateOrder($body['items'] ?? [],$services,$body['discount_percent']);
        if (!isset($body['expected_total']) || wholeMoney($body['expected_total']) !== $calc['total']) reply(['error'=>'Harga layanan berubah. Muat ulang halaman dan susun ulang item sebelum membayar.'],409);
        $paymentMethod=(string)($body['method'] ?? ''); $config=settings();
        $configured=$paymentMethod==='qris' ? (bool)$config['qris_image'] : (bool)($config['bank_name'] && $config['bank_account'] && $config['bank_holder']);
        $payment=validatePayment($paymentMethod,$body['paid'] ?? 0,$calc['total'],$configured,($body['confirmed'] ?? false) === true);
        $notes=trim((string)($body['notes'] ?? '')); if(mb_strlen($notes)>1000) throw new InvalidArgumentException('Catatan maksimal 1.000 karakter.');
        $hours=array_map(fn($i)=>$services[$i['service_id']]['duration_hours'],$calc['items']);
        $due=in_array(null,$hours,true) ? null : date('Y-m-d H:i:s',time()+max($hours)*3600);
        db()->beginTransaction();
        query('INSERT INTO orders(request_key,request_hash,customer_id,cashier_id,customer_name,customer_phone,subtotal,discount_percent,discount,total,method,paid,change_amount,notes,due_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',[$key,$fingerprint,$customer['id'],$userId,$customer['name'],$customer['phone'],$calc['subtotal'],$calc['discount_percent'],$calc['discount'],$calc['total'],$paymentMethod,$payment['paid'],$payment['change'],$notes,$due]);
        $id=(int)db()->lastInsertId();
        query('UPDATE orders SET number=? WHERE id=?',['SYQ-'.date('Ymd').'-'.str_pad((string)$id,4,'0',STR_PAD_LEFT),$id]);
        foreach($calc['items'] as $i) query('INSERT INTO order_items(order_id,service_id,service_name,unit,price,quantity,category,subtotal) VALUES(?,?,?,?,?,?,?,?)',[$id,$i['service_id'],$i['service_name'],$i['unit'],$i['price'],$i['quantity'],$i['category'],$i['subtotal']]);
        query('INSERT INTO status_history(order_id,status,user_id) VALUES(?,?,?)',[$id,'Diterima',$userId]);
        db()->commit(); reply(['order'=>getOrder($id)],201);
    }
    if ($method === 'POST' && preg_match('#^/orders/(\d+)/status$#',$route,$match)) {
        $id=(int)$match[1]; $status=(string)($body['status'] ?? '');
        db()->beginTransaction(); $row=query('SELECT * FROM orders WHERE id=? FOR UPDATE',[$id])->fetch();
        if (!$row) { db()->rollBack(); reply(['error'=>'Pesanan tidak ditemukan.'],404); }
        if (!validTransition($row['status'],$status)) throw new InvalidArgumentException('Status harus diperbarui berurutan. Muat ulang untuk melihat status terbaru.');
        query('UPDATE orders SET status=? WHERE id=?',[$status,$id]);
        query('INSERT INTO status_history(order_id,status,user_id) VALUES(?,?,?)',[$id,$status,$userId]);
        if ($status==='Selesai') recordNotification($id);
        db()->commit(); reply(['order'=>getOrder($id)]);
    }
    reply(['error'=>'Endpoint tidak tersedia untuk akses kasir.'],404);
} catch (InvalidArgumentException | JsonException $e) {
    try { if(db()->inTransaction()) db()->rollBack(); } catch(Throwable $ignored) {}
    reply(['error'=>$e instanceof JsonException ? 'Format data tidak valid.' : $e->getMessage()],422);
} catch (Throwable $e) {
    try { if(db()->inTransaction()) db()->rollBack(); } catch(Throwable $ignored) {}
    error_log('Syaqil API: '.$e->getMessage());
    if ($e instanceof PDOException && ($e->errorInfo[1] ?? 0)===1062) reply(['error'=>'Data sudah tersimpan. Muat ulang untuk melihatnya.'],409);
    reply(['error'=>'Data belum dapat disimpan atau dimuat. Periksa koneksi server, lalu coba lagi.'],500);
}
