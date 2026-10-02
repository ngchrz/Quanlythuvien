<?php
// File: User/sach.php — Độc giả xem + tìm kiếm + lọc sách.
// (Độc giả chỉ xem, không có nút Thêm/Sửa/Xóa.)

// Kiểm tra đăng nhập (độc giả)
require_once __DIR__ . '/auth_user.php';

// Nối database
require_once __DIR__ . '/../config/database.php';

// Nhận từ khóa tìm kiếm và thể loại trên link

$tuKhoa  = trim($_GET['q'] ?? '');
$theLoai = trim($_GET['the_loai'] ?? '');

// Danh sách thể loại để lọc (lấy từ database, không hard-code)
$dsTheLoai = $pdo->query('SELECT DISTINCT the_loai FROM quyensach ORDER BY the_loai')->fetchAll(PDO::FETCH_COLUMN);

// Tìm kiếm bằng PDO Prepared Statement
$sql = 'SELECT * FROM quyensach WHERE 1=1';
$params = [];
if ($tuKhoa !== '') {
    $sql .= ' AND (name LIKE :kw1 OR tac_gia LIKE :kw2 OR the_loai LIKE :kw3)';
    $kw = '%' . $tuKhoa . '%';
    $params['kw1'] = $kw;
    $params['kw2'] = $kw;
    $params['kw3'] = $kw;
}
if ($theLoai !== '') {
    $sql .= ' AND the_loai = :the_loai';
    $params['the_loai'] = $theLoai;
}
$sql .= ' ORDER BY id_sach DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$danhSach = $stmt->fetchAll();

$pageTitle  = 'Xem sách';
$activeMenu = 'sach';
include __DIR__ . '/../partials/reader-top.php';
?>

<h2 class="page-h">📚 Thư viện sách</h2>

<div class="panel">
    <form class="search-bar" method="GET" action="sach.php">
        <input type="text" name="q" placeholder="🔍 Tìm kiếm sách (tên, tác giả, thể loại)..."
               value="<?= htmlspecialchars($tuKhoa) ?>">
        <select name="the_loai">
            <option value="">— Tất cả thể loại —</option>
            <?php foreach ($dsTheLoai as $tl): ?>
                <option value="<?= htmlspecialchars($tl) ?>" <?= $theLoai === $tl ? 'selected' : '' ?>>
                    <?= htmlspecialchars($tl) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-primary" style="width:auto;">Tìm</button>
        <?php if ($tuKhoa !== '' || $theLoai !== ''): ?>
            <a href="sach.php" class="glass-btn btn-home">Xóa lọc</a>
        <?php endif; ?>
    </form>

    <p style="color:#6b7280;font-size:14px;margin-bottom:16px;">Tìm thấy <b><?= count($danhSach) ?></b> cuốn sách.</p>

    <div class="book-grid">
        <?php if (!$danhSach): ?>
            <p style="color:#9ca3af;">Không tìm thấy sách nào phù hợp.</p>
        <?php endif; ?>
        <?php foreach ($danhSach as $sach): ?>
        <a class="book-card" href="chitiet_sach.php?id=<?= $sach['id_sach'] ?>">
            <?php if (!empty($sach['img']) && file_exists(__DIR__ . '/../img/product/' . $sach['img'])): ?>
                <img class="book-cover" src="../img/product/<?= htmlspecialchars($sach['img']) ?>" alt="<?= htmlspecialchars($sach['name']) ?>">
            <?php else: ?>
                <div class="book-cover placeholder">📖</div>
            <?php endif; ?>
            <div class="book-info">
                <h4><?= htmlspecialchars($sach['name']) ?></h4>
                <div class="meta">✍️ <?= htmlspecialchars($sach['tac_gia']) ?></div>
                <div class="meta">🏷️ <?= htmlspecialchars($sach['the_loai']) ?></div>
                <span class="detail-link">Xem chi tiết →</span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/../partials/reader-bottom.php'; ?>
