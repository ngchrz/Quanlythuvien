<?php
// File: Login/check-username.php
// AJAX: kiểm tra tên tài khoản đã có người dùng chưa.
// Gọi: check-username.php?username=minhphuc
// Trả về JSON: {"ton_tai": true/false}
header('Content-Type: application/json; charset=utf-8');

// Nối database (có sẵn $pdo)
require_once __DIR__ . '/../config/database.php';

// Lấy tên cần kiểm tra, đưa về chữ thường
$username = strtolower(trim($_GET['username'] ?? ''));

// Tên rỗng hoặc sai định dạng thì không cần hỏi database
if ($username == '' || preg_match('/^[a-zA-Z0-9]+$/', $username) == false) {
    echo json_encode(['ton_tai' => false, 'hop_le' => false]);
    exit();
}

// Đếm trong database (LOWER để Admin1 = admin1)
$check = $pdo->prepare('SELECT COUNT(*) FROM users WHERE LOWER(username) = :username');
$check->execute(['username' => $username]);

if ($check->fetchColumn() > 0) {
    echo json_encode(['ton_tai' => true, 'hop_le' => true]);
} else {
    echo json_encode(['ton_tai' => false, 'hop_le' => true]);
}
