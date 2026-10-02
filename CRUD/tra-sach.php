<?php
// File: CRUD/tra-sach.php — XÁC NHẬN TRẢ SÁCH (chỉ admin).
// Ghi ngày trả = hôm nay, trạng thái = Đã trả.

// Kiểm tra đăng nhập + quyền admin
require_once __DIR__ . '/../Login/auth.php';
requireAdmin();

// Nối database
require_once __DIR__ . '/../config/database.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ../muontra.php');
    exit();
}
if (true) {
    $kiemTra = $pdo->prepare("SELECT ngay_tra FROM muon_tra WHERE id_muon_tra = :id");
    $kiemTra->execute(['id' => $id]);
    $phieu = $kiemTra->fetch();
    if (!$phieu) {
        flash('error', 'Không tìm thấy phiếu mượn.');
        header('Location: ../muontra.php');
        exit();
    }
    if (!empty($phieu['ngay_tra'])) {
        flash('warning', 'Phiếu này đã được trả trước đó.');
        header('Location: ../muontra.php');
        exit();
    }
    $pdo->prepare(
        "UPDATE muon_tra SET ngay_tra = CURDATE(), trang_thai = 'Đã trả' WHERE id_muon_tra = :id"
    )->execute(['id' => $id]);
    flash('success', 'Đã xác nhận trả sách!');
}

header('Location: ../muontra.php');
exit();
