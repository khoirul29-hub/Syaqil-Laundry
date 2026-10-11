<?php
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if(str_starts_with($path,'/api/')) { require __DIR__.'/api.php'; return; }
$root=realpath(__DIR__.'/../dist');
$file=$root ? realpath($root.$path) : false;
if($root && $file && str_starts_with($file,$root.DIRECTORY_SEPARATOR) && is_file($file)) {
 $types=['js'=>'text/javascript','css'=>'text/css','png'=>'image/png','svg'=>'image/svg+xml','woff2'=>'font/woff2','ico'=>'image/x-icon'];
 header('Content-Type: '.($types[pathinfo($file,PATHINFO_EXTENSION)] ?? 'application/octet-stream')); readfile($file); return;
}
header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: DENY');
if($root) readfile($root.'/index.html'); else { http_response_code(503); echo 'Jalankan npm run build terlebih dahulu.'; }
