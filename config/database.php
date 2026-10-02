<?php
// File: config/database.php — Nối tới MySQL bằng PDO.
// Mọi trang đều include file này để có biến $pdo.

$host = 'localhost';
$port = 3306;
$database = 'quanlythuvien';
$username = 'root';
$password = '';

$dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    exit('Lỗi kết nối: ' . $e->getMessage());
}

// Hàm dùng chung (e(), flash(), kiểm tra username)
require_once __DIR__ . '/helpers.php';

// Logic trạng thái mượn trả dùng chung (tinhTrangThai(), lopBadge())
require_once __DIR__ . '/muon_helper.php';