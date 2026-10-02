<?php
// File: User/auth_user.php
// Nhớ include file này ở ĐẦU mọi trang của độc giả.
// 1. Chưa đăng nhập -> đá về Login.
// 2. Admin mà vào nhầm -> đá về Dashboard quản trị.

// Nếu chưa có session thì mở session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Chưa đăng nhập thì về Login
if (empty($_SESSION['id_taikhoan'])) {
    header('Location: ../Login/login.php');
    exit();
}

// Admin thì về trang quản trị, không ở đây
if (($_SESSION['role'] ?? 'user') === 'admin') {
    header('Location: ../index.php');
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
