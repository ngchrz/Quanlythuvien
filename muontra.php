<?php
// File: muontra.php — Quản lý mượn trả (Admin).
// Trạng thái tính bằng hàm chung tinhTrangThai() (giống Dashboard):
//   có ngày trả = Đã trả, quá 7 ngày chưa trả = Quá hạn, còn lại = Đang mượn.

// Kiểm tra đăng nhập + quyền admin
require_once __DIR__ . '/Login/auth.php';
requireAdmin();

// Nối database
require_once __DIR__ . '/config/database.php';

// Nhận trạng thái cần lọc trên link: all / dang_muon / da_tra / qua_han
$loc = $_GET['loc'] ?? 'all';

// Nhận từ khóa tìm kiếm (giống Dashboard + trang Sách/Độc giả)
$tuKhoa = trim($_GET['q'] ?? '');

// Lấy phiếu mượn (nối bảng để có tên sách + tên độc giả), mới nhất lên đầu
// Điều kiện lọc tính theo NGÀY (giống Dashboard):
//   Đã trả  = đã có ngày trả
//   Quá hạn = chưa trả + hôm nay vượt quá hạn trả (mượn + 7 ngày)
//   Đang mượn = còn lại
$sql = "SELECT m.*, q.name AS ten_sach, d.ho_ten
        FROM muon_tra m
        LEFT JOIN quyensach q ON m.id_sach = q.id_sach
        LEFT JOIN doc_gia d ON m.id_doc_gia = d.id_doc_gia
        WHERE 1 = 1";
$thamSo = [];
if ($loc == 'da_tra') {
    $sql .= " AND m.ngay_tra IS NOT NULL";
} elseif ($loc == 'qua_han') {
    $sql .= " AND m.ngay_tra IS NULL AND CURDATE() > DATE_ADD(m.ngay_muon, INTERVAL " . SO_NGAY_DUOC_MUON . " DAY)";
} elseif ($loc == 'dang_muon') {
    $sql .= " AND m.ngay_tra IS NULL AND CURDATE() <= DATE_ADD(m.ngay_muon, INTERVAL " . SO_NGAY_DUOC_MUON . " DAY)";
}
// Tìm theo tên sách / tên độc giả (giữ nguyên lọc trạng thái)
if ($tuKhoa !== '') {
    $sql .= " AND (q.name LIKE :kw1 OR d.ho_ten LIKE :kw2";
    $thamSo['kw1'] = '%' . $tuKhoa . '%';
    $thamSo['kw2'] = '%' . $tuKhoa . '%';
    if (ctype_digit($tuKhoa)) {
        $sql .= " OR m.id_muon_tra = :kw3 OR m.id_doc_gia = :kw4 OR m.id_sach = :kw5";
        $thamSo['kw3'] = (int)$tuKhoa;
        $thamSo['kw4'] = (int)$tuKhoa;
        $thamSo['kw5'] = (int)$tuKhoa;
    }
    $sql .= ")";
}
$sql .= " ORDER BY m.id_muon_tra DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($thamSo);
$data = $stmt->fetchAll();

$pageTitle  = 'Quản lý mượn trả';
$activeMenu = 'muontra';
include __DIR__ . '/partials/top.php';
?>

<div class="page-head">
    <div>
        <h2>🔄 Phiếu mượn trả</h2>
        <p class="sub">Tổng <b><?= count($data) ?></b> phiếu</p>
    </div>
    <a href="./CRUD/muontra-create.php" class="btn btn-primary"><i class="fas fa-plus"></i> Tạo phiếu mượn</a>
</div>

<div class="panel">
    <!-- Bộ lọc trạng thái (cùng logic với Dashboard) -->
    <div class="filter-row">
        <div class="filter-pills">
            <a class="<?= $loc === 'all' ? 'active' : '' ?>" href="muontra.php?loc=all&q=<?= urlencode($tuKhoa) ?>">Tất cả</a>
            <a class="<?= $loc === 'dang_muon' ? 'active' : '' ?>" href="muontra.php?loc=dang_muon&q=<?= urlencode($tuKhoa) ?>">Đang mượn</a>
            <a class="<?= $loc === 'da_tra' ? 'active' : '' ?>" href="muontra.php?loc=da_tra&q=<?= urlencode($tuKhoa) ?>">Đã trả</a>
            <a class="<?= $loc === 'qua_han' ? 'active' : '' ?>" href="muontra.php?loc=qua_han&q=<?= urlencode($tuKhoa) ?>">Quá hạn</a>
        </div>
        <form class="filter-search" method="GET" action="muontra.php">
            <input type="hidden" name="loc" value="<?= e($loc) ?>">
            <input type="text" name="q" placeholder="Tìm sách, độc giả, mã phiếu..." value="<?= e($tuKhoa) ?>">
            <button type="submit" class="btn btn-primary" style="width:auto;">Tìm</button>
            <?php if ($tuKhoa !== ''): ?>
                <a href="muontra.php?loc=<?= e($loc) ?>" class="btn btn-light">Xóa lọc</a>
            <?php endif; ?>
        </form>
    </div>

    <table class="tbl">
        <thead>
            <tr><th>STT</th><th>Mã mượn</th><th>Sách</th><th>Độc giả</th><th>Ngày mượn</th><th>Hạn trả</th><th>Ngày trả</th><th>Trạng thái</th><th>Thao tác</th></tr>
        </thead>
        <tbody>
            <?php if (!$data): ?>
                <tr><td colspan="9" style="text-align:center;color:#9ca3af;">Chưa có phiếu mượn nào.</td></tr>
            <?php endif; ?>
            <?php foreach ($data as $stt => $m): ?>
            <?php
            // Tính trạng thái bằng hàm chung (giống Dashboard)
            $hienThi = tinhTrangThai($m);
            // Hiển thị mã + tên giống Dashboard: S001 - Tên sách / DG001 - Tên
            $tenSachHienThi = !empty($m['id_sach']) ? 'S' . str_pad($m['id_sach'], 3, '0', STR_PAD_LEFT) . ' - ' . ($m['ten_sach'] ?? '') : '—';
            $tenDocGiaHienThi = !empty($m['id_doc_gia']) ? 'DG' . str_pad($m['id_doc_gia'], 3, '0', STR_PAD_LEFT) . ' - ' . ($m['ho_ten'] ?? '') : '—';
            ?>
            <tr>
                <td><?= $stt + 1 ?></td>
                <td><?= 'MT' . str_pad($m['id_muon_tra'], 3, '0', STR_PAD_LEFT) ?></td>
                <td><?= e($tenSachHienThi) ?></td>
                <td><?= e($tenDocGiaHienThi) ?></td>
                <td><?= !empty($m['ngay_muon']) ? date('d/m/Y', strtotime($m['ngay_muon'])) : '—' ?></td>
                <?php $han = hanTra($m['ngay_muon'] ?? ''); ?>
                <td><?= $han != '' ? date('d/m/Y', strtotime($han)) : '—' ?></td>
                <td><?= !empty($m['ngay_tra']) ? date('d/m/Y', strtotime($m['ngay_tra'])) : 'Chưa trả' ?></td>
                <td>
                    <span class="badge <?= lopBadge($hienThi) ?>"><?= e($hienThi) ?></span>
                </td>
                <td class="action-cell">
                    <?php if ($hienThi != 'Đã trả'): ?>
                    <a class="btn btn-success btn-sm" href="./CRUD/tra-sach.php?id=<?= $m['id_muon_tra'] ?>"
                       data-confirm="Xác nhận độc giả đã trả sách [<?= e($m['ten_sach'] ?? '') ?>]?|Xác nhận|✔️">✔ Trả sách</a>
                    <?php endif; ?>
                    <a class="btn-edit btn-sm" href="./CRUD/edit-muontra.php?id=<?= $m['id_muon_tra'] ?>">Sửa</a>
                    <a class="btn-delete btn-sm" href="./CRUD/delete-muontra.php?id=<?= $m['id_muon_tra'] ?>"
                       data-confirm="Bạn có chắc chắn muốn xóa phiếu mượn này?|Xóa|🗑️">Xóa</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/partials/bottom.php'; ?>
