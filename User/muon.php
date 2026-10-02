<?php
// File: User/muon.php — Sách độc giả ĐANG mượn.
// Chỉ lấy của chính mình qua id trong session, không lấy của người khác.

// Kiểm tra đăng nhập (độc giả)
require_once __DIR__ . '/auth_user.php';

// Nối database
require_once __DIR__ . '/../config/database.php';

// id độc giả của người đang đăng nhập

$idDocGia = $currentUser['id_doc_gia'];
$danhSach = [];
if (!empty($idDocGia)) {
    $stmt = $pdo->prepare(
        "SELECT m.*, q.name AS ten_sach, q.img, q.tac_gia
         FROM muon_tra m
         LEFT JOIN quyensach q ON m.id_sach = q.id_sach
         WHERE m.id_doc_gia = :id AND m.ngay_tra IS NULL
         ORDER BY m.ngay_muon DESC"
    );
    $stmt->execute(['id' => $idDocGia]);
    $danhSach = $stmt->fetchAll();
}

$pageTitle  = 'Sách đang mượn';
$activeMenu = 'muon';
include __DIR__ . '/../partials/reader-top.php';
?>

<h2 class="page-h">🔄 Sách bạn đang mượn (<?= count($danhSach) ?>)</h2>

<div class="panel">
    <div class="book-grid">
        <?php if (!$danhSach): ?>
            <p style="color:#9ca3af;">Bạn hiện không mượn cuốn sách nào. 📭</p>
        <?php endif; ?>
        <?php foreach ($danhSach as $m): ?>
        <div class="book-card">
            <?php if (!empty($m['img']) && file_exists(__DIR__ . '/../img/product/' . $m['img'])): ?>
                <img class="book-cover" src="../img/product/<?= htmlspecialchars($m['img']) ?>" alt="">
            <?php else: ?>
                <div class="book-cover placeholder">📖</div>
            <?php endif; ?>
            <div class="book-info">
                <h4><?= htmlspecialchars($m['ten_sach'] ?? '—') ?></h4>
                <div class="meta">📅 Mượn: <?= $m['ngay_muon'] ? date('d/m/Y', strtotime($m['ngay_muon'])) : '' ?></div>
                <div class="meta">Trạng thái:
                    <?php $ttMuon = tinhTrangThai($m); ?>
                    <span class="badge <?= lopBadge($ttMuon) ?>"><?= e($ttMuon) ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/../partials/reader-bottom.php'; ?>
