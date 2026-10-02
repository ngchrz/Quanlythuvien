<?php

declare(strict_types=1);

$host = 'localhost';
$port = 3306;
$database = 'quanlythuvien';
$username = 'root';
$password = '';

$dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $exception) {
    // Trong môi trường học tập có thể tạm thời bật dòng dưới để xem lỗi:
    // exit($exception->getMessage());
    exit('Không thể kết nối cơ sở dữ liệu. Vui lòng kiểm tra MySQL và file cấu hình.');
}
