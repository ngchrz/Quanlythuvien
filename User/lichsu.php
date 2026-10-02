<?php
// File: User/lichsu.php — Lịch sử mượn sách của CHÍNH mình.
// Lấy id trong session, không tin id trên link.

// Kiểm tra đăng nhập (độc giả)
require_once __DIR__ . '/auth_user.php';

// Nối database
require_once __DIR__ . '/../config/database.php';

// id độc giả của người đang đăng nhập

$idDocGia = $currentUser['id_doc_gia'];
$danhSach = [];
if (!empty($idDocGia)) {
    $stmt = $pdo->prepare(
        "SELECT m.*, q.name AS ten_sach
         FROM muon_tra m
         LEFT JOIN quyensach q ON m.id_sach = q.id_sach
         WHERE m.id_doc_gia = :id
         ORDER BY m.ngay_muon DESC"
    );
    $stmt->execute(['id' => $idDocGia]);
    $danhSach = $stmt->fetchAll();
}

$pageTitle  = 'Lịch sử mượn';
$activeMenu = 'lichsu';
include __DIR__ . '/../partials/reader-top.php';
?>

<h2 class="page-h">📋 Lịch sử mượn sách</h2>

<div class="panel">
    <table class="data">
        <thead>
            <tr><th>STT</th><th>Sách</th><th>Ngày mượn</th><th>Ngày trả</th><th>Trạng thái</th></tr>
        </thead>
        <tbody>
            <?php if (!$danhSach): ?>
                <tr><td colspan="5" style="text-align:center;color:#9ca3af;">Chưa có lịch sử mượn nào.</td></tr>
            <?php endif; ?>
            <?php foreach ($danhSach as $stt => $m): ?>
            <tr>
                <td><?= $stt + 1 ?></td>
                <td><?= htmlspecialchars($m['ten_sach'] ?? '—') ?></td>
                <td><?= $m['ngay_muon'] ? date('d/m/Y', strtotime($m['ngay_muon'])) : '' ?></td>
                <td><?= !empty($m['ngay_tra']) ? date('d/m/Y', strtotime($m['ngay_tra'])) : 'Chưa trả' ?></td>
                <td>
                    <?php $ttSu = tinhTrangThai($m); ?>
                    <span class="badge <?= lopBadge($ttSu) ?>"><?= e($ttSu) ?></span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../partials/reader-bottom.php'; ?>
