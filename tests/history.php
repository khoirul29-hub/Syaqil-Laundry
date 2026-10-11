<?php
// This test creates and removes its own fixtures only in the isolated test database.
require __DIR__.'/../server/config.php';
if(db()->query('SELECT DATABASE()')->fetchColumn() !== 'syaqil_laundry_test') exit("Refusing to modify a non-test database.\n");
$prefix='history-test-'.bin2hex(random_bytes(5)).'-';
$customer=(int)db()->query('SELECT id FROM customers LIMIT 1')->fetchColumn();
$user=(int)db()->query('SELECT id FROM users LIMIT 1')->fetchColumn();
$q=db()->prepare('INSERT INTO orders(number,request_key,customer_id,cashier_id,customer_name,customer_phone,subtotal,total,method,paid,change_amount) VALUES(?,?,?,?,?,?,?,?,?,?,?)');
try {
 db()->beginTransaction();
 for($i=0;$i<1002;$i++) $q->execute([$prefix.$i,$prefix.$i,$customer,$user,'History test','6281200000099',6000,6000,'cash',6000,0]);
 db()->commit();
 $cookie=tempnam(sys_get_temp_dir(),'syaqil-test-');
 function callApi(string $route, ?array $data=null): array {
  global $cookie,$csrf;
  $ch=curl_init('http://127.0.0.1:8081/api'.$route);
  curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEJAR=>$cookie,CURLOPT_COOKIEFILE=>$cookie,CURLOPT_HTTPHEADER=>['Content-Type: application/json','X-CSRF-Token: '.($csrf??'')]]);
  if($data!==null){curl_setopt($ch,CURLOPT_POST,true);curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($data));}
  $result=json_decode(curl_exec($ch),true,512,JSON_THROW_ON_ERROR);curl_close($ch);
  if(isset($result['csrf']))$csrf=$result['csrf'];return $result;
 }
 callApi('/session');callApi('/login',['username'=>'kasir','password'=>'KasirDemo!2026']);
 $result=callApi('/bootstrap');$numbers=array_column($result['orders']??[],'number');
 if(!in_array($prefix.'0',$numbers,true)||!in_array($prefix.'1001',$numbers,true))throw new Exception('Old orders were truncated.');
 echo "PASS: Oldest and newest fixture receipts remain accessible beyond 1,000 orders.\n";
} finally {
 if(db()->inTransaction())db()->rollBack();
 $q=db()->prepare('DELETE FROM orders WHERE request_key LIKE ?');$q->execute([$prefix.'%']);
 if(isset($cookie))unlink($cookie);
}
