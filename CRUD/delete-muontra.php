<?php
// File: CRUD/delete-muontra.php — XÓA PHIẾU MƯỢN (chỉ admin).

// Kiểm tra đăng nhập + quyền admin
require_once __DIR__ . '/../Login/auth.php';
requireAdmin();

// Nối database
require_once __DIR__ . '/../config/database.php';

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    flash('error', 'Mã phiếu không hợp lệ.');
    header("Location: ../muontra.php");
    exit();
}

$kiemTra = $pdo->prepare("SELECT COUNT(*) FROM muon_tra WHERE id_muon_tra = :id");
$kiemTra->execute(['id' => $id]);
if ($kiemTra->fetchColumn() == 0) {
    flash('error', 'Không tìm thấy phiếu mượn.');
    header("Location: ../muontra.php");
    exit();
}

$sql = "DELETE FROM muon_tra WHERE id_muon_tra = :id";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    'id' => $id
]);

flash('success', 'Xóa phiếu mượn thành công!');
header("Location: ../muontra.php");
exit();

?>