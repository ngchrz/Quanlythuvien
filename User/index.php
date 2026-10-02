<?php
// File: User/index.php — Trang chủ của độc giả.

// Kiểm tra đăng nhập (độc giả)
require_once __DIR__ . '/auth_user.php';

// Nối database
require_once __DIR__ . '/../config/database.php';

// Đếm tổng số sách trong thư viện

$tongSach = (int) $pdo->query('SELECT COUNT(*) FROM quyensach')->fetchColumn();

$idDocGia = $currentUser['id_doc_gia'];
$dangMuon = 0;
$tongLuot = 0;
if (!empty($idDocGia)) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM muon_tra WHERE id_doc_gia = :id AND ngay_tra IS NULL");
    $stmt->execute(['id' => $idDocGia]);
    $dangMuon = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM muon_tra WHERE id_doc_gia = :id');
    $stmt->execute(['id' => $idDocGia]);
    $tongLuot = (int) $stmt->fetchColumn();
}

$sachMoi = $pdo->query('SELECT * FROM quyensach ORDER BY id_sach DESC LIMIT 4')->fetchAll();

$pageTitle  = 'Trang chủ';
$activeMenu = 'home';
include __DIR__ . '/../partials/reader-top.php';
?>

<div class="welcome">
    <h2>Xin chào, <?= e($currentUser['ho_ten']) ?> 👋</h2>
    <p>Hôm nay bạn muốn đọc gì?</p>
    <form class="search-bar" method="GET" action="sach.php">
        <input type="text" name="q" placeholder="🔍 Tìm kiếm sách...">
        <button type="submit" class="btn btn-light" style="width:auto;">Tìm</button>
    </form>
</div>

<div class="stats" style="grid-template-columns: repeat(3, 1fr);">
    <div class="stat"><div class="stat-ico si-blue">📚</div><div><div class="num"><?= $tongSach ?></div><div class="lbl">Tổng sách</div></div></div>
    <div class="stat"><div class="stat-ico si-amber">🔄</div><div><div class="num"><?= $dangMuon ?></div><div class="lbl">Đang mượn</div></div></div>
    <div class="stat"><div class="stat-ico si-green">📋</div><div><div class="num"><?= $tongLuot ?></div><div class="lbl">Lượt đã mượn</div></div></div>
</div>

<div class="panel">
    <h3>✨ Sách mới nhất</h3>
    <div class="book-grid">
        <?php foreach ($sachMoi as $sach): ?>
        <a class="book-card" href="chitiet_sach.php?id=<?= $sach['id_sach'] ?>">
            <?php if (!empty($sach['img']) && file_exists(__DIR__ . '/../img/product/' . $sach['img'])): ?>
                <img class="book-cover" src="../img/product/<?= e($sach['img']) ?>" alt="<?= e($sach['name']) ?>">
            <?php else: ?>
                <div class="book-cover placeholder">📖</div>
            <?php endif; ?>
            <div class="book-info">
                <h4><?= e($sach['name']) ?></h4>
                <div class="meta">✍️ <?= e($sach['tac_gia']) ?></div>
                <div class="meta">🏷️ <?= e($sach['the_loai']) ?></div>
                <span class="detail-link">Xem chi tiết →</span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/../partials/reader-bottom.php'; ?>
