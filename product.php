<?php
// File: product.php — Quản lý sách (Admin): xem dạng lưới hoặc bảng + tìm kiếm.

// Kiểm tra đăng nhập + quyền admin
require_once __DIR__ . '/Login/auth.php';
requireAdmin();

// Nối database
require_once __DIR__ . '/config/database.php';

// Nhận từ khóa tìm kiếm và kiểu hiển thị trên link
$tuKhoa = trim($_GET['q'] ?? '');
$view = ($_GET['view'] ?? 'grid') === 'table' ? 'table' : 'grid';

if ($tuKhoa !== '') {
    $stmt = $pdo->prepare(
        "SELECT q.*, (SELECT COUNT(*) FROM muon_tra m WHERE m.id_sach = q.id_sach AND m.ngay_tra IS NULL) AS dang_muon
         FROM quyensach q
         WHERE name LIKE :kw1 OR tac_gia LIKE :kw2 OR the_loai LIKE :kw3
         ORDER BY id_sach DESC"
    );
    $kw = '%' . $tuKhoa . '%';
    $stmt->execute(['kw1' => $kw, 'kw2' => $kw, 'kw3' => $kw]);
} else {
    $stmt = $pdo->prepare('SELECT q.*, (SELECT COUNT(*) FROM muon_tra m WHERE m.id_sach = q.id_sach AND m.ngay_tra IS NULL) AS dang_muon FROM quyensach q ORDER BY id_sach DESC');
    $stmt->execute();
}
$data = $stmt->fetchAll();
$qUrl = $tuKhoa !== '' ? '&q=' . urlencode($tuKhoa) : '';

$pageTitle  = 'Quản lý sách';
$activeMenu = 'sach';
include __DIR__ . '/partials/top.php';
?>

<div class="page-head">
    <div>
        <h2>📚 Sách trong thư viện</h2>
        <p class="sub">Tìm thấy <b><?= count($data) ?></b> cuốn sách</p>
    </div>
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <div class="view-toggle">
            <a href="product.php?view=grid<?= $qUrl ?>" class="<?= $view === 'grid' ? 'active' : '' ?>" title="Dạng lưới">▦</a>
            <a href="product.php?view=table<?= $qUrl ?>" class="<?= $view === 'table' ? 'active' : '' ?>" title="Dạng bảng">☰</a>
        </div>
        <a href="./CRUD/create.php" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm sách</a>
    </div>
</div>

<div class="panel">
    <form class="search-bar" method="GET" action="product.php">
        <input type="hidden" name="view" value="<?= $view ?>">
        <input type="text" name="q" placeholder="🔍 Tìm kiếm sách (tên, tác giả, thể loại)..." value="<?= e($tuKhoa) ?>">
        <button type="submit" class="btn btn-primary" style="width:auto;">Tìm</button>
        <?php if ($tuKhoa !== ''): ?>
            <a href="product.php?view=<?= $view ?>" class="btn btn-light">Xóa lọc</a>
        <?php endif; ?>
    </form>

    <?php if ($view === 'grid'): ?>
    <div class="book-grid">
        <?php if (!$data): ?><p style="color:#9ca3af;">Không tìm thấy sách nào.</p><?php endif; ?>
        <?php foreach ($data as $sach): ?>
        <?php
            $tong = (int)($sach['so_luong'] ?? 1);
            $muon = (int)($sach['dang_muon'] ?? 0);
            $con = max(0, $tong - $muon);
        ?>
        <div class="book-card">
            <div class="stock-wrap">
            <?php if (!empty($sach['img']) && file_exists(__DIR__ . '/img/product/' . $sach['img'])): ?>
                <img class="book-cover" src="./img/product/<?= e($sach['img']) ?>" alt="<?= e($sach['name']) ?>" onclick="showImage(this.src)" style="cursor:zoom-in;">
            <?php else: ?>
                <div class="book-cover placeholder">📖</div>
            <?php endif; ?>
                <?php if ($con <= 0): ?>
                    <span class="badge b-han stock-float">Hết sách</span>
                <?php elseif ($con <= 1): ?>
                    <span class="badge b-muon stock-float">Còn <?= $con ?>/<?= $tong ?></span>
                <?php else: ?>
                    <span class="badge b-tra stock-float">Còn <?= $con ?>/<?= $tong ?></span>
                <?php endif; ?>
            </div>
            <div class="book-info">
                <h4><?= e($sach['name']) ?></h4>
                <div class="meta">✍️ <?= e($sach['tac_gia']) ?></div>
                <div class="meta">🏷️ <?= e($sach['the_loai']) ?></div>
                <div class="meta">📦 Tổng: <?= $tong ?> • Đang mượn: <?= $muon ?></div>
            </div>
            <div class="card-actions">
                <a class="btn btn-light btn-sm" href="./CRUD/edit.php?id=<?= $sach['id_sach'] ?>">Sửa</a>
                <a class="btn btn-danger btn-sm" href="./CRUD/delete.php?id=<?= $sach['id_sach'] ?>"
                   data-confirm="Bạn có chắc chắn muốn xóa sách [<?= e($sach['name']) ?>]?|Xóa sách|🗑️">Xóa</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <table class="tbl">
        <thead>
            <tr><th>STT</th><th>Mã</th><th>Ảnh</th><th>Tên sách</th><th>Tác giả</th><th>Thể loại</th><th>Tồn kho</th><th>Thao tác</th></tr>
        </thead>
        <tbody>
            <?php if (!$data): ?><tr><td colspan="8" style="text-align:center;color:#9ca3af;">Không tìm thấy sách nào.</td></tr><?php endif; ?>
            <?php foreach ($data as $stt => $sach): ?>
            <?php
                $tong = (int)($sach['so_luong'] ?? 1);
                $muon = (int)($sach['dang_muon'] ?? 0);
                $con = max(0, $tong - $muon);
            ?>
            <tr>
                <td><?= $stt + 1 ?></td>
                <td>S<?= str_pad($sach['id_sach'], 3, '0', STR_PAD_LEFT) ?></td>
                <td>
                    <?php if (!empty($sach['img']) && file_exists(__DIR__ . '/img/product/' . $sach['img'])): ?>
                        <img class="book-thumb" src="./img/product/<?= e($sach['img']) ?>" alt="" onclick="showImage(this.src)">
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td><?= e($sach['name']) ?></td>
                <td><?= e($sach['tac_gia']) ?></td>
                <td><?= e($sach['the_loai']) ?></td>
                <td>
                    <?php if ($con <= 0): ?>
                        <span class="badge b-han">Hết (<?= $muon ?>/<?= $tong ?>)</span>
                    <?php elseif ($con <= 1): ?>
                        <span class="badge b-muon">Còn <?= $con ?>/<?= $tong ?></span>
                    <?php else: ?>
                        <span class="badge b-tra">Còn <?= $con ?>/<?= $tong ?></span>
                    <?php endif; ?>
                </td>
                <td class="action-cell">
                    <a class="btn-edit btn-sm" href="./CRUD/edit.php?id=<?= $sach['id_sach'] ?>">Sửa</a>
                    <a class="btn-delete btn-sm" href="./CRUD/delete.php?id=<?= $sach['id_sach'] ?>"
                       data-confirm="Bạn có chắc chắn muốn xóa sách [<?= e($sach['name']) ?>]?|Xóa sách|🗑️">Xóa</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<div id="imageModal" class="image-modal" onclick="this.style.display='none';document.getElementById('modalImage').src='';">
    <span class="close">&times;</span>
    <img id="modalImage" src="" alt="" onclick="event.stopPropagation()">
</div>
<script>
function showImage(src) {
    var m = document.getElementById('imageModal');
    document.getElementById('modalImage').src = src;
    m.style.display = 'flex';
}
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') document.getElementById('imageModal').style.display = 'none';
});
</script>

<?php include __DIR__ . '/partials/bottom.php'; ?>
