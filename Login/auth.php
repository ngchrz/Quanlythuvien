<?php
// File: Login/auth.php
// Nhớ include file này ở ĐẦU mọi trang quản trị (Admin).
// Công việc của file này:
// 1. Chưa đăng nhập -> đá về trang Login.
// 2. Tạo biến $currentUser để sidebar/header dùng chung.

// Nếu chưa có session thì mở session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nạp các hàm dùng chung (flash, e...)
require_once __DIR__ . '/../config/helpers.php';

// Chưa đăng nhập thì không cho xem, đá về Login
if (empty($_SESSION['id_taikhoan'])) {
    // File nằm trong thư mục CRUD thì đường dẫn khác một chút
    if (strpos($_SERVER['PHP_SELF'], '/CRUD/') !== false) {
        header('Location: ../Login/login.php');
    } else {
        header('Location: ./Login/login.php');
    }
    exit();
}

// Gom thông tin người đang đăng nhập vào 1 biến cho dễ dùng
$currentUser = [
    'id'         => $_SESSION['id_taikhoan'],
    'username'   => $_SESSION['username'],
    'ho_ten'     => $_SESSION['ho_ten'],
    'role'       => $_SESSION['role'],
    'id_doc_gia' => $_SESSION['id_docgia'],
];

// Kiểm tra có phải admin không (role trong session)
function isAdmin() {
    if (($_SESSION['role'] ?? 'user') === 'admin') {
        return true;
    }
    return false;
}

// Chặn trang chỉ admin được vào.
// Độc giả cố vào thì báo lỗi rồi đưa về cổng độc giả.
function requireAdmin() {
    if (isAdmin() == false) {
        flash('error', 'Bạn không có quyền truy cập chức năng này!');
        if (strpos($_SERVER['PHP_SELF'], '/CRUD/') !== false) {
            header('Location: ../User/index.php');
        } else {
            header('Location: ./User/index.php');
        }
        exit();
    }
}
