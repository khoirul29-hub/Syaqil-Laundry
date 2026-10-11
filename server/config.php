<?php
declare(strict_types=1);
$envFile = __DIR__.'/../.env';
if (is_file($envFile)) foreach (parse_ini_file($envFile, false, INI_SCANNER_RAW) ?: [] as $key=>$value) {
    if (getenv($key) === false) putenv($key.'='.$value);
}
date_default_timezone_set('Asia/Jakarta');
function db(): PDO {
    static $db;
    if (!$db) {
        $opts=[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false];
        // TLS untuk database cloud (mis. TiDB Cloud wajib TLS). Isi DB_SSL_CA
        // dengan path file CA (relatif ke folder server/, mis. "ca.pem").
        $ca=(string)(getenv('DB_SSL_CA') ?: '');
        if ($ca !== '') { $path=$ca[0]==='/' ? $ca : __DIR__.'/'.$ca; if (is_file($path)) $opts[PDO::MYSQL_ATTR_SSL_CA]=$path; }
        elseif (getenv('DB_SSL')==='1') $opts[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT]=false;
        $db = new PDO(getenv('DB_DSN') ?: 'mysql:host=127.0.0.1;dbname=syaqil_laundry;charset=utf8mb4',getenv('DB_USER') ?: 'syaqil',getenv('DB_PASSWORD') ?: '',$opts);
    }
    return $db;
}
function demoMode(): bool { return getenv('APP_DEMO') === '1'; }
function settings(): array { return ['name'=>'Syaqil Laundry','address'=>getenv('BUSINESS_ADDRESS') ?: '', 'phone'=>getenv('BUSINESS_PHONE') ?: '', 'bank_name'=>getenv('BANK_NAME') ?: '', 'bank_account'=>getenv('BANK_ACCOUNT') ?: '', 'bank_holder'=>getenv('BANK_HOLDER') ?: '', 'qris_image'=>getenv('QRIS_IMAGE') ?: '', 'demo'=>demoMode()]; }
