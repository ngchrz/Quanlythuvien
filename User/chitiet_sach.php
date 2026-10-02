<?php
// File: User/chitiet_sach.php — Xem chi tiết 1 cuốn sách.
// (Độc giả chỉ xem, muốn mượn thì ra quầy gặp thủ thư.)

// Kiểm tra đăng nhập (độc giả)
require_once __DIR__ . '/auth_user.php';

// Nối database
require_once __DIR__ . '/../config/database.php';

// Lấy id sách trên link

$id = $_GET['id'] ?? '';
$stmt = $pdo->prepare('SELECT * FROM quyensach WHERE id_sach = :id');
$stmt->execute(['id' => $id]);
$sach = $stmt->fetch();

if (!$sach) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Không tìm thấy sách!'];
    header('Location: sach.php');
    exit();
}

// Trạng thái tồn kho: tổng - đang mượn = còn lại
$soLuong = (int)($sach['so_luong'] ?? 1);
$check = $pdo->prepare(
    "SELECT COUNT(*) FROM muon_tra WHERE id_sach = :id AND ngay_tra IS NULL"
);
$check->execute(['id' => $sach['id_sach']]);
$dangMuon = (int)$check->fetchColumn();
$conLai = max(0, $soLuong - $dangMuon);

$pageTitle  = 'Chi tiết sách';
$activeMenu = 'sach';
include __DIR__ . '/../partials/reader-top.php';
?>

<h2 class="page-h">📖 <?= htmlspecialchars($sach['name']) ?></h2>

<div class="panel">
    <div class="detail-wrap">
        <div>
            <?php if (!empty($sach['img']) && file_exists(__DIR__ . '/../img/product/' . $sach['img'])): ?>
                <img class="detail-cover" src="../img/product/<?= htmlspecialchars($sach['img']) ?>" alt="<?= htmlspecialchars($sach['name']) ?>">
            <?php else: ?>
                <div class="detail-cover book-cover placeholder" style="height:280px;">📖</div>
            <?php endif; ?>
        </div>
        <div>
            <table class="detail-table">
                <tr><th>Mã sách</th><td>S<?= str_pad($sach['id_sach'], 3, '0', STR_PAD_LEFT) ?></td></tr>
                <tr><th>Tên sách</th><td><b><?= htmlspecialchars($sach['name']) ?></b></td></tr>
                <tr><th>Tác giả</th><td><?= htmlspecialchars($sach['tac_gia']) ?></td></tr>
                <tr><th>Thể loại</th><td><?= htmlspecialchars($sach['the_loai']) ?></td></tr>
                <tr><th>Tồn kho</th><td>Tổng <?= $soLuong ?> bản • Đang mượn <?= $dangMuon ?> • Còn lại <b><?= $conLai ?></b></td></tr>
                <tr>
                    <th>Trạng thái</th>
                    <td>
                        <?php if ($conLai <= 0): ?>
                            <span class="badge b-han">Hết sách — vui lòng quay lại sau</span>
                        <?php elseif ($conLai == 1): ?>
                            <span class="badge b-muon">Chỉ còn 1 bản</span>
                        <?php else: ?>
                            <span class="badge b-tra">Còn <?= $conLai ?> bản — có sẵn</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            <p style="color:#6b7280;font-size:13px;margin:14px 0;">
                ℹ️ Thư viện cho mượn trực tiếp tại quầy. Vui lòng liên hệ thủ thư để tạo phiếu mượn.
            </p>
            <div class="page-actions">
                <a href="sach.php" class="glass-btn btn-home"><i class="fas fa-arrow-left"></i> Về danh sách</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../partials/reader-bottom.php'; ?>
