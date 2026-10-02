<?php
// File: CRUD/delete-docgia.php — XÓA ĐỘC GIẢ (chỉ admin).
// Quy tắc: độc giả đã có LỊCH SỬ mượn trả (bất kể đã trả hay chưa)
// thì KHÔNG được xóa để giữ lịch sử. Chỉ xóa khi chưa từng mượn.

// Kiểm tra đăng nhập + quyền admin
require_once __DIR__ . '/../Login/auth.php';
requireAdmin();

// Nối database
require_once __DIR__ . '/../config/database.php';

// Lấy id độc giả trên link
$id = (int) ($_GET['id'] ?? 0);

// Không có id thì về danh sách
if ($id <= 0) {
    flash('error', 'Mã độc giả không hợp lệ.');
    header("Location: ../docgia.php");
    exit();
}

// Đếm xem độc giả này có dòng nào trong bảng mượn trả không
$sql = "SELECT COUNT(*) FROM muon_tra WHERE id_doc_gia = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);

$count = $stmt->fetchColumn();

// Có lịch sử mượn trả thì không cho xóa
if ($count > 0) {
    flash('warning', 'Không thể xóa độc giả vì còn lịch sử mượn trả. Hãy xóa phiếu mượn liên quan trước.');
    header("Location: ../docgia.php");
    exit();
}

// Chưa từng mượn thì xóa bình thường
$sql = "DELETE FROM doc_gia WHERE id_doc_gia = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);

// Xong thì báo và về danh sách
flash('success', 'Xóa độc giả thành công!');
header("Location: ../docgia.php");
exit();
