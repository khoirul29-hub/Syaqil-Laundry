<?php
require __DIR__ . '/../server/domain.php';
function check($condition, $message) { if (!$condition) throw new Exception($message); echo "PASS: $message\n"; }
function rejects($fn, $message) { try { $fn(); } catch (InvalidArgumentException $e) { check(true, $message); return; } throw new Exception('Expected rejection: '.$message); }
$services = [1 => ['id'=>1,'name'=>'Reguler','price'=>6000,'unit'=>'kg','duration_hours'=>null]];
$line = ['service_id'=>1,'quantity'=>'4.25','category'=>'Kiloan','price'=>1];
check(calculateOrder([$line], $services, 0)['total'] === 25500, 'Decimal weight uses authoritative service price');
$discounted=calculateOrder([array_merge($line,['quantity'=>13])],$services,10);
check($discounted['discount']===7800 && $discounted['total']===70200, 'Ten percent reduces Rp78000 by Rp7800');
check($discounted['discount_percent']===10.0, 'Percentage is retained for receipts');
check(calculateOrder([$line],$services,12.5)['discount']===3188, 'Fractional percentage rounds discount to nearest rupiah');
check(calculateOrder([$line],$services,100)['total']===0, 'Full discount produces zero total');
rejects(fn()=>calculateOrder([$line],$services,-1),'Negative percentage rejected');
rejects(fn()=>calculateOrder([$line],$services,100.01),'Percentage above 100 rejected');
rejects(fn()=>calculateOrder([$line],$services,'1.234'),'Percentage with more than two decimals rejected');
rejects(fn()=>calculateOrder([array_merge($line,['quantity'=>0])],$services,0),'Zero quantity rejected');
rejects(fn()=>calculateOrder([array_merge($line,['quantity'=>-1])],$services,0),'Negative quantity rejected');
rejects(fn()=>calculateOrder([array_merge($line,['quantity'=>'1.234'])],$services,0),'More than two decimals rejected');
rejects(fn()=>calculateOrder([$line],$services,99999),'Excessive discount rejected');
rejects(fn()=>calculateOrder([],$services,0),'Empty order rejected');
rejects(fn()=>validatePayment('cash',24999,25000,false),'Insufficient cash rejected');
check(validatePayment('cash',30000,25000,false)['change'] === 5000,'Cash change calculated');
rejects(fn()=>validatePayment('transfer',25000,25000,false),'Unconfigured transfer rejected');
check(validTransition('Diterima','Diproses'),'Next status allowed');
check(!validTransition('Diterima','Selesai'),'Skipping status rejected');
check(normalizePhone('0812 3456 7890') === '6281234567890','Phone normalization');
rejects(fn()=>normalizePhone('123'),'Invalid WhatsApp rejected');
