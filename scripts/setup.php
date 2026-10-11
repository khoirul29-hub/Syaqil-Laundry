<?php
require __DIR__.'/../server/config.php';
require __DIR__.'/../server/domain.php';
if(PHP_SAPI !== 'cli') exit('CLI only');
db()->exec(file_get_contents(__DIR__.'/../server/schema.sql'));
if (!db()->query("SHOW COLUMNS FROM orders LIKE 'request_hash'")->fetch()) db()->exec('ALTER TABLE orders ADD COLUMN request_hash CHAR(64) NULL AFTER request_key');
if (!db()->query("SHOW COLUMNS FROM orders LIKE 'discount_percent'")->fetch()) db()->exec('ALTER TABLE orders ADD COLUMN discount_percent DECIMAL(5,2) NULL AFTER subtotal');
if (!db()->query("SHOW COLUMNS FROM customers LIKE 'notes'")->fetch()) db()->exec("ALTER TABLE customers ADD COLUMN notes VARCHAR(1000) NOT NULL DEFAULT '' AFTER address");
if((int)db()->query('SELECT COUNT(*) FROM services')->fetchColumn()===0) {
 db()->exec("INSERT INTO services(name,price,unit,duration_hours) VALUES ('Reguler',6000,'kg',NULL),('Ekspres 1 Hari',10000,'kg',24),('Ekspres Jam',14000,'kg',NULL)");
}
$username=getenv('INITIAL_USERNAME') ?: (demoMode() ? 'kasir' : '');
$password=getenv('INITIAL_PASSWORD') ?: (demoMode() ? 'KasirDemo!2026' : '');
if(!$username || strlen($password)<12) exit("Set INITIAL_USERNAME dan INITIAL_PASSWORD (minimal 12 karakter).\n");
db()->beginTransaction();
$q=db()->prepare('INSERT IGNORE INTO users(name,username,password_hash,role) VALUES(?,?,?,?)');
$q->execute([demoMode() ? 'Kasir Demo' : 'Kasir Syaqil',$username,password_hash($password,PASSWORD_DEFAULT),'cashier']);
if(demoMode() && (int)db()->query('SELECT COUNT(*) FROM customers')->fetchColumn()===0) {
 $q=db()->prepare('INSERT INTO customers(name,phone,address) VALUES(?,?,?)');
 foreach([['Andi Pratama','6281200000001','Alamat contoh · Jl. Melati No. 12'],['Nadia Putri','6281200000002','Alamat contoh · Jl. Anggrek No. 8'],['Rian Saputra','6281200000003','Alamat contoh · Perumahan Asri Blok B'],['Fajar Ramadhan','6281200000004','Alamat contoh · Jl. Kenanga No. 5']] as $c) $q->execute($c);
 $statuses=['Diterima','Diproses','Selesai','Diambil'];
 $user=(int)db()->query('SELECT id FROM users LIMIT 1')->fetchColumn();
 $services=[];foreach(db()->query('SELECT * FROM services')->fetchAll() as $s) $services[$s['id']]=$s;
 foreach(db()->query('SELECT * FROM customers ORDER BY id')->fetchAll() as $index=>$c) {
  $calc=calculateOrder([['service_id'=>($index%3)+1,'quantity'=>3+$index/2,'category'=>'Kiloan']],$services,0);
  $created=date('Y-m-d H:i:s',strtotime('today 08:00')+$index*1200);
  $q=db()->prepare('INSERT INTO orders(number,request_key,customer_id,cashier_id,customer_name,customer_phone,subtotal,total,method,paid,change_amount,status,notes,created_at,due_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
  $q->execute(['SYQ-'.date('Ymd').'-D00'.($index+1),'demo-fixture-000000'.$index,$c['id'],$user,$c['name'],$c['phone'],$calc['total'],$calc['total'],'cash',100000,100000-$calc['total'],$statuses[$index],'Data contoh untuk mencoba aplikasi.',$created,$index===1 ? date('Y-m-d H:i:s',strtotime($created)+86400) : null]);
  $id=(int)db()->lastInsertId();$i=$calc['items'][0];
  $q=db()->prepare('INSERT INTO order_items(order_id,service_id,service_name,unit,price,quantity,category,subtotal) VALUES(?,?,?,?,?,?,?,?)');$q->execute([$id,$i['service_id'],$i['service_name'],$i['unit'],$i['price'],$i['quantity'],$i['category'],$i['subtotal']]);
  for($j=0;$j<=$index;$j++) { $q=db()->prepare('INSERT INTO status_history(order_id,status,user_id,created_at) VALUES(?,?,?,?)');$q->execute([$id,$statuses[$j],$user,$created]); }
  if($index>=2) { $q=db()->prepare('INSERT INTO notifications(order_id,status,message) VALUES(?,?,?)');$q->execute([$id,'not_configured','WhatsApp belum terhubung. Hubungi pelanggan secara manual.']); }
 }
}
db()->commit();
echo "Database dan akun kasir siap. Mode: ".(demoMode() ? 'demo' : 'operasional')."\n";
