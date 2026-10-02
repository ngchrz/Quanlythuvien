<?php
// File: index.php — Dashboard của Admin.

// Kiểm tra đăng nhập + quyền admin
require_once __DIR__ . '/Login/auth.php';
requireAdmin();

// Nối database
require_once __DIR__ . '/config/database.php';

// Số liệu chung (đếm trực tiếp từ database, không ghi số cứng)
// Trạng thái tính theo NGÀY (giống hệt trang Mượn trả):
//   Đã trả  = đã có ngày trả
//   Quá hạn = chưa trả + mượn quá SO_NGAY_DUOC_MUON ngày
//   Đang mượn = còn lại
$tongSach   = (int) $pdo->query('SELECT COALESCE(SUM(so_luong),0) FROM quyensach')->fetchColumn();
$tongDauSach = (int) $pdo->query('SELECT COUNT(*) FROM quyensach')->fetchColumn();
$tongDocGia = (int) $pdo->query('SELECT COUNT(*) FROM doc_gia')->fetchColumn();
$daTra      = (int) $pdo->query('SELECT COUNT(*) FROM muon_tra WHERE ngay_tra IS NOT NULL')->fetchColumn();
$quaHan     = (int) $pdo->query('SELECT COUNT(*) FROM muon_tra WHERE ngay_tra IS NULL AND CURDATE() > DATE_ADD(ngay_muon, INTERVAL ' . SO_NGAY_DUOC_MUON . ' DAY)')->fetchColumn();
$dangMuon   = (int) $pdo->query('SELECT COUNT(*) FROM muon_tra WHERE ngay_tra IS NULL AND CURDATE() <= DATE_ADD(ngay_muon, INTERVAL ' . SO_NGAY_DUOC_MUON . ' DAY)')->fetchColumn();

// Nhận trạng thái cần lọc trên link: all / dang_muon / da_tra / qua_han
$loc = $_GET['loc'] ?? 'all';

// Nhận từ khóa tìm kiếm (mã phiếu, mã độc giả, tên, sách)
$tuKhoa = trim($_GET['q'] ?? '');

// Câu SQL gốc: nối 3 bảng để lấy tên sách + mã/tên độc giả
$sql = "SELECT m.*, q.name AS ten_sach, d.ho_ten
        FROM muon_tra m
        LEFT JOIN quyensach q ON m.id_sach = q.id_sach
        LEFT JOIN doc_gia d ON m.id_doc_gia = d.id_doc_gia
        WHERE 1 = 1";
$thamSo = [];

// Lọc theo trạng thái (cùng logic ngày tháng với cách hiển thị)
if ($loc == 'da_tra') {
    $sql .= " AND m.ngay_tra IS NOT NULL";
} elseif ($loc == 'qua_han') {
    $sql .= " AND m.ngay_tra IS NULL AND CURDATE() > DATE_ADD(m.ngay_muon, INTERVAL " . SO_NGAY_DUOC_MUON . " DAY)";
} elseif ($loc == 'dang_muon') {
    $sql .= " AND m.ngay_tra IS NULL AND CURDATE() <= DATE_ADD(m.ngay_muon, INTERVAL " . SO_NGAY_DUOC_MUON . " DAY)";
}
// loc == 'all' thì không thêm gì = hiện tất cả

// Tìm kiếm theo tên sách, tên độc giả
if ($tuKhoa != '') {
    $sql .= " AND (q.name LIKE :q1 OR d.ho_ten LIKE :q2";
    $thamSo['q1'] = '%' . $tuKhoa . '%';
    $thamSo['q2'] = '%' . $tuKhoa . '%';
    // Nếu gõ số thì tìm luôn theo mã phiếu / mã độc giả
    if (ctype_digit($tuKhoa)) {
        $sql .= " OR m.id_muon_tra = :q3 OR m.id_doc_gia = :q4";
        $thamSo['q3'] = (int) $tuKhoa;
        $thamSo['q4'] = (int) $tuKhoa;
    }
    $sql .= ")";
}

$sql .= " ORDER BY m.id_muon_tra DESC LIMIT 3";

$stmt = $pdo->prepare($sql);
$stmt->execute($thamSo);
$recent = $stmt->fetchAll();

// Sách mới thêm + độc giả gần đây (tối đa 3 cái mới nhất)
$sachMoi = $pdo->query('SELECT * FROM quyensach ORDER BY id_sach DESC LIMIT 3')->fetchAll();
$docGiaMoi = $pdo->query('SELECT * FROM doc_gia ORDER BY id_doc_gia DESC LIMIT 3')->fetchAll();

$pageTitle  = 'Dashboard';
$activeMenu = 'home';
include __DIR__ . '/partials/top.php';
?>

<div class="page-head" id="thongke">
    <div>
        <h2>Dashboard</h2>
        <p class="sub">Tổng quan hoạt động thư viện</p>
    </div>
</div>

<div class="stats">
    <div class="stat"><div class="stat-ico si-blue">📚</div><div><div class="num"><?= $tongSach ?></div><div class="lbl">Tổng bản sách (<?= $tongDauSach ?> đầu sách)</div></div></div>
    <div class="stat"><div class="stat-ico si-green">👥</div><div><div class="num"><?= $tongDocGia ?></div><div class="lbl">Độc giả</div></div></div>
    <div class="stat"><div class="stat-ico si-amber">🔄</div><div><div class="num"><?= $dangMuon ?></div><div class="lbl">Đang mượn</div></div></div>
    <div class="stat"><div class="stat-ico si-green">✅</div><div><div class="num"><?= $daTra ?></div><div class="lbl">Đã trả<?= $quaHan > 0 ? " (Quá hạn: $quaHan)" : '' ?></div></div></div>
</div>

<div class="panel">
    <h3>📊 Phiếu mượn</h3>

    <!-- Bộ lọc trạng thái -->
    <div class="filter-row">
        <div class="filter-pills">
            <a class="<?= $loc === 'all' ? 'active' : '' ?>" href="index.php?loc=all&q=<?= urlencode($tuKhoa) ?>#thongke">Tất cả</a>
            <a class="<?= $loc === 'dang_muon' ? 'active' : '' ?>" href="index.php?loc=dang_muon&q=<?= urlencode($tuKhoa) ?>#thongke">Đang mượn</a>
            <a class="<?= $loc === 'da_tra' ? 'active' : '' ?>" href="index.php?loc=da_tra&q=<?= urlencode($tuKhoa) ?>#thongke">Đã trả</a>
            <a class="<?= $loc === 'qua_han' ? 'active' : '' ?>" href="index.php?loc=qua_han&q=<?= urlencode($tuKhoa) ?>#thongke">Quá hạn</a>
        </div>
        <form class="filter-search" method="GET" action="index.php">
            <input type="hidden" name="loc" value="<?= e($loc) ?>">
            <input type="text" name="q" placeholder="🔍 Mã phiếu, độc giả, sách..." value="<?= e($tuKhoa) ?>">
            <button type="submit" class="btn btn-primary" style="width:auto;">Tìm</button>
        </form>
    </div>

    <table class="tbl">
        <thead><tr><th>Mã</th><th>Sách</th><th>Độc giả</th><th>Ngày mượn</th><th>Hạn trả</th><th>Trạng thái</th></tr></thead>
        <tbody>
        <?php if (!$recent): ?>
            <tr><td colspan="6" style="text-align:center;color:#9ca3af;">Không có phiếu nào phù hợp.</td></tr>
        <?php endif; ?>
        <?php foreach ($recent as $r): ?>
            <?php
            // Mã + tên độc giả: DG004 - Phạm Thu Hà
            if (!empty($r['id_doc_gia'])) {
                $maDocGia = 'DG' . str_pad($r['id_doc_gia'], 3, '0', STR_PAD_LEFT);
                $tenDocGia = $maDocGia . ' - ' . ($r['ho_ten'] ?? '');
            } else {
                $tenDocGia = '—';
            }
            // Trạng thái tính bằng hàm chung (giống trang Mượn trả)
            $hienThi = tinhTrangThai($r);
            // Hạn trả = ngày mượn + 7 ngày (tính, không lưu database)
            $han = hanTra($r['ngay_muon'] ?? '');
            ?>
            <tr>
                <td>MT<?= str_pad($r['id_muon_tra'], 3, '0', STR_PAD_LEFT) ?></td>
                <td><?= e($r['ten_sach'] ?? '—') ?></td>
                <td><?= e($tenDocGia) ?></td>
                <td><?= $r['ngay_muon'] ? date('d/m/Y', strtotime($r['ngay_muon'])) : '' ?></td>
                <td><?= $han != '' ? date('d/m/Y', strtotime($han)) : '—' ?></td>
                <td>
                    <span class="badge <?= lopBadge($hienThi) ?>"><?= e($hienThi) ?></span>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p style="color:#9ca3af;font-size:12.5px;margin-top:10px;">Quy ước: mượn quá <?= SO_NGAY_DUOC_MUON ?> ngày chưa trả sẽ hiện “Quá hạn”.</p>
</div>

<div class="duo-panels">
    <div class="panel">
        <h3>✨ Sách mới</h3>
        <table class="tbl">
            <thead><tr><th>Mã</th><th>Tên sách</th><th>Tác giả</th></tr></thead>
            <tbody>
            <?php foreach ($sachMoi as $s): ?>
                <tr>
                    <td>S<?= str_pad($s['id_sach'], 3, '0', STR_PAD_LEFT) ?></td>
                    <td><?= e($s['name']) ?></td>
                    <td><?= e($s['tac_gia']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="panel">
        <h3>👥 Độc giả gần đây</h3>
        <table class="tbl">
            <thead><tr><th>Mã</th><th>Họ tên</th><th>SĐT</th></tr></thead>
            <tbody>
            <?php foreach ($docGiaMoi as $d): ?>
                <tr>
                    <td>DG<?= str_pad($d['id_doc_gia'], 3, '0', STR_PAD_LEFT) ?></td>
                    <td><?= e($d['ho_ten']) ?></td>
                    <td><?= e($d['so_dien_thoai'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="cards">
    <a class="card" href="product.php"><h3>📚 Quản lý sách</h3><p>Thêm, sửa, xóa sách, upload ảnh bìa.</p><span class="arrow">Xem danh sách →</span></a>
    <a class="card" href="docgia.php"><h3>👥 Quản lý độc giả</h3><p>Quản lý họ tên, ngày sinh, SĐT, địa chỉ.</p><span class="arrow">Xem danh sách →</span></a>
    <a class="card" href="muontra.php"><h3>🔄 Quản lý mượn trả</h3><p>Tạo phiếu mượn, xác nhận trả sách.</p><span class="arrow">Xem danh sách →</span></a>
</div>

<?php include __DIR__ . '/partials/bottom.php'; ?>
