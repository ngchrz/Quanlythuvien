<?php
// File: docgia.php — Quản lý độc giả (Admin) + tìm kiếm.
// (Không hiện mật khẩu — mật khẩu nằm ở bảng users.)

// Kiểm tra đăng nhập + quyền admin
require_once __DIR__ . '/Login/auth.php';
requireAdmin();

// Nối database
require_once __DIR__ . '/config/database.php';

// Nhận từ khóa tìm kiếm trên link
$tuKhoa = trim($_GET['q'] ?? '');
if ($tuKhoa !== '') {
    $stmt = $pdo->prepare(
        "SELECT * FROM doc_gia
         WHERE ho_ten LIKE :kw1 OR so_dien_thoai LIKE :kw2 OR dia_chi LIKE :kw3
         ORDER BY id_doc_gia DESC"
    );
    $kw = '%' . $tuKhoa . '%';
    $stmt->execute(['kw1' => $kw, 'kw2' => $kw, 'kw3' => $kw]);
} else {
    $stmt = $pdo->prepare('SELECT * FROM doc_gia ORDER BY id_doc_gia DESC');
    $stmt->execute();
}
$data = $stmt->fetchAll();

$pageTitle  = 'Quản lý độc giả';
$activeMenu = 'docgia';
include __DIR__ . '/partials/top.php';
?>

<div class="page-head">
    <div>
        <h2>👥 Danh sách độc giả</h2>
        <p class="sub">Tìm thấy <b><?= count($data) ?></b> độc giả</p>
    </div>
    <a href="./CRUD/create-docgia.php" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm độc giả</a>
</div>

<div class="panel">
    <form class="search-bar" method="GET" action="docgia.php">
        <input type="text" name="q" placeholder="🔍 Tìm độc giả (tên, SĐT, địa chỉ)..." value="<?= e($tuKhoa) ?>">
        <button type="submit" class="btn btn-primary" style="width:auto;">Tìm</button>
        <?php if ($tuKhoa !== ''): ?>
            <a href="docgia.php" class="btn btn-light">Xóa lọc</a>
        <?php endif; ?>
    </form>

    <table class="tbl">
        <thead>
            <tr><th>STT</th><th>Mã</th><th>Họ tên</th><th>Ngày sinh</th><th>Giới tính</th><th>SĐT</th><th>Địa chỉ</th><th>Thao tác</th></tr>
        </thead>
        <tbody>
            <?php if (!$data): ?>
                <tr><td colspan="8" style="text-align:center;color:#9ca3af;">Không tìm thấy độc giả nào.</td></tr>
            <?php endif; ?>
            <?php foreach ($data as $stt => $dg): ?>
            <tr>
                <td><?= $stt + 1 ?></td>
                <td>DG<?= str_pad($dg['id_doc_gia'], 3, '0', STR_PAD_LEFT) ?></td>
                <td><b><?= e($dg['ho_ten']) ?></b></td>
                <td><?= !empty($dg['ngay_sinh']) ? date('d/m/Y', strtotime($dg['ngay_sinh'])) : '—' ?></td>
                <td><?= e($dg['gioi_tinh'] ?? '—') ?></td>
                <td><?= e($dg['so_dien_thoai'] ?? '—') ?></td>
                <td><?= e($dg['dia_chi'] ?? '—') ?></td>
                <td class="action-cell">
                    <a class="btn-edit btn-sm" href="./CRUD/edit-docgia.php?id=<?= $dg['id_doc_gia'] ?>">Sửa</a>
                    <a class="btn-delete btn-sm" href="./CRUD/delete-docgia.php?id=<?= $dg['id_doc_gia'] ?>"
                       data-confirm="Bạn có chắc chắn muốn xóa độc giả [<?= e($dg['ho_ten']) ?>]?|Xóa|🗑️">Xóa</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/partials/bottom.php'; ?>
