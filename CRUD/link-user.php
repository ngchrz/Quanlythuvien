<?php
// File: CRUD/link-user.php — Nối tài khoản với hồ sơ độc giả (chỉ admin).
// Quy tắc KHÓA liên kết:
//   - Tài khoản đã có id_doc_gia thì KHÔNG được đổi sang người khác,
//     cũng KHÔNG được bỏ thành NULL (kể cả gửi lén qua POST).
//   - Chỉ tài khoản user đang CHƯA nối mới được nối 1 lần.

// Kiểm tra đăng nhập + quyền admin
require_once __DIR__ . '/../Login/auth.php';
requireAdmin();

// Nối database
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId   = $_POST['user_id'] ?? '';
    $idDocGia = trim($_POST['id_doc_gia'] ?? '');
    if ($idDocGia === '') {
        $idDocGia = null;
    } else {
        $idDocGia = (int) $idDocGia;
    }

    // Lấy tài khoản cần nối
    $taiKhoan = $pdo->prepare('SELECT role, id_doc_gia FROM users WHERE id = :id');
    $taiKhoan->execute(['id' => $userId]);
    $row = $taiKhoan->fetch();

    if (!$row || $row['role'] !== 'user') {
        // Không có tài khoản này, hoặc là admin thì không đụng tới
        header('Location: ../users.php');
        exit();
    }

    // Đã nối rồi thì khóa cứng: không cho đổi, không cho bỏ
    if (!empty($row['id_doc_gia'])) {
        flash('error', 'Tài khoản này đã liên kết độc giả, không được thay đổi.');
        header('Location: ../users.php');
        exit();
    }

    // Chưa nối: kiểm tra độc giả đã nối với tài khoản KHÁC chưa
    if ($idDocGia !== null) {
        $checkTrung = $pdo->prepare(
            'SELECT COUNT(*) FROM users WHERE id_doc_gia = :dg AND id != :id'
        );
        $checkTrung->execute(['dg' => $idDocGia, 'id' => $userId]);
        if ($checkTrung->fetchColumn() > 0) {
            flash('error', 'Độc giả này đã được liên kết với một tài khoản khác.');
            header('Location: ../users.php');
            exit();
        }
    }

    // Mọi kiểm tra xong mới lưu
    $pdo->prepare('UPDATE users SET id_doc_gia = :dg WHERE id = :id')
        ->execute(['dg' => $idDocGia, 'id' => $userId]);
    flash('success', 'Đã cập nhật liên kết độc giả!');
}

header('Location: ../users.php');
exit();
