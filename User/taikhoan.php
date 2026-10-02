<?php
// File: User/taikhoan.php — Tài khoản của tôi.
// Xem/sửa thông tin cá nhân (bảng doc_gia) + đổi mật khẩu (bảng users).
// Mọi truy vấn đều dùng id trong session.

// Kiểm tra đăng nhập (độc giả)
require_once __DIR__ . '/auth_user.php';

// Nối database
require_once __DIR__ . '/../config/database.php';

$idTaiKhoan = (int) $currentUser['id'];
$idDocGia   = $currentUser['id_doc_gia'] ? (int) $currentUser['id_doc_gia'] : null;

// Tài khoản đăng nhập (username, email)
$stmt = $pdo->prepare('SELECT username, email FROM users WHERE id = :id');
$stmt->execute(['id' => $idTaiKhoan]);
$taiKhoan = $stmt->fetch();

// Hồ sơ độc giả liên kết
$docGia = null;
if ($idDocGia) {
    $stmt = $pdo->prepare('SELECT * FROM doc_gia WHERE id_doc_gia = :id');
    $stmt->execute(['id' => $idDocGia]);
    $docGia = $stmt->fetch();
}

$msgOk = '';
$msgErr = '';

// ---- Sửa thông tin cá nhân (bảng doc_gia + email trong users) ----
if (isset($_POST['doi_thong_tin'])) {
    $hoTen = trim($_POST['ho_ten'] ?? '');
    $ngaySinh = $_POST['ngay_sinh'] ?? '';
    $gioiTinh = $_POST['gioi_tinh'] ?? '';
    $soDienThoai = trim($_POST['so_dien_thoai'] ?? '');
    $diaChi = trim($_POST['dia_chi'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($hoTen === '') {
        $msgErr = 'Họ tên không được để trống.';
    } elseif (mb_strlen($hoTen) > 100) {
        $msgErr = 'Họ tên không được quá 100 ký tự.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msgErr = 'Email chưa đúng định dạng.';
    } elseif ($soDienThoai !== '' && !preg_match('/^[0-9]{9,11}$/', $soDienThoai)) {
        $msgErr = 'Số điện thoại phải gồm 9-11 chữ số.';
    } elseif ($ngaySinh !== '' && (strtotime($ngaySinh) === false || $ngaySinh > date('Y-m-d'))) {
        $msgErr = 'Ngày sinh chưa đúng.';
    } elseif (!$docGia) {
        $msgErr = 'Không tìm thấy hồ sơ độc giả của bạn.';
    } elseif ($soDienThoai !== '' && $soDienThoai !== ($docGia['so_dien_thoai'] ?? '')) {
        $trungSdt = $pdo->prepare('SELECT COUNT(*) FROM doc_gia WHERE so_dien_thoai = :s AND id_doc_gia != :id');
        $trungSdt->execute(['s' => $soDienThoai, 'id' => $idDocGia]);
        if ($trungSdt->fetchColumn() > 0) {
            $msgErr = 'Số điện thoại đã được sử dụng.';
        } else {
            $pdo->prepare(
                'UPDATE doc_gia SET ho_ten = :ht, ngay_sinh = :ns, gioi_tinh = :gt,
                 so_dien_thoai = :sdt, dia_chi = :dc WHERE id_doc_gia = :id'
            )->execute([
                'ht'  => $hoTen,
                'ns'  => $ngaySinh !== '' ? $ngaySinh : null,
                'gt'  => $gioiTinh !== '' ? $gioiTinh : null,
                'sdt' => $soDienThoai !== '' ? $soDienThoai : null,
                'dc'  => $diaChi !== '' ? $diaChi : null,
                'id'  => $idDocGia,
            ]);
            $pdo->prepare('UPDATE users SET ho_ten = :ht, email = :em WHERE id = :id')
                ->execute(['ht' => $hoTen, 'em' => $email !== '' ? $email : null, 'id' => $idTaiKhoan]);
            $_SESSION['ho_ten'] = $hoTen;
            $currentUser['ho_ten'] = $hoTen;
            $taiKhoan['email'] = $email;
            $docGia = array_merge($docGia, [
                'ho_ten' => $hoTen, 'ngay_sinh' => $ngaySinh, 'gioi_tinh' => $gioiTinh,
                'so_dien_thoai' => $soDienThoai, 'dia_chi' => $diaChi,
            ]);
            $msgOk = 'Đã cập nhật thông tin cá nhân.';
        }
    } else {
        $pdo->prepare(
            'UPDATE doc_gia SET ho_ten = :ht, ngay_sinh = :ns, gioi_tinh = :gt,
             so_dien_thoai = :sdt, dia_chi = :dc WHERE id_doc_gia = :id'
        )->execute([
            'ht'  => $hoTen,
            'ns'  => $ngaySinh !== '' ? $ngaySinh : null,
            'gt'  => $gioiTinh !== '' ? $gioiTinh : null,
            'sdt' => $soDienThoai !== '' ? $soDienThoai : null,
            'dc'  => $diaChi !== '' ? $diaChi : null,
            'id'  => $idDocGia,
        ]);
        $pdo->prepare('UPDATE users SET ho_ten = :ht, email = :em WHERE id = :id')
            ->execute(['ht' => $hoTen, 'em' => $email !== '' ? $email : null, 'id' => $idTaiKhoan]);
        $_SESSION['ho_ten'] = $hoTen;
        $currentUser['ho_ten'] = $hoTen;
        $taiKhoan['email'] = $email;
        $docGia = array_merge($docGia, [
            'ho_ten' => $hoTen, 'ngay_sinh' => $ngaySinh, 'gioi_tinh' => $gioiTinh,
            'so_dien_thoai' => $soDienThoai, 'dia_chi' => $diaChi,
        ]);
        $msgOk = 'Đã cập nhật thông tin cá nhân.';
    }
}

// ---- Đổi mật khẩu (bảng users) ----
if (isset($_POST['doi_mat_khau'])) {
    $mkCu  = $_POST['mat_khau_cu'] ?? '';
    $mkMoi = $_POST['mat_khau_moi'] ?? '';
    $mkLai = $_POST['mat_khau_lai'] ?? '';
    $hashCu = $pdo->prepare('SELECT password FROM users WHERE id = :id');
    $hashCu->execute(['id' => $idTaiKhoan]);
    if (!password_verify($mkCu, $hashCu->fetchColumn())) {
        $msgErr = 'Mật khẩu hiện tại chưa đúng.';
    } elseif (strlen($mkMoi) < 4) {
        $msgErr = 'Mật khẩu mới phải từ 4 ký tự trở lên.';
    } elseif (strtolower($mkMoi) == strtolower($taiKhoan['username'])) {
        $msgErr = 'Mật khẩu không được trùng với tên tài khoản.';
    } elseif ($mkMoi !== $mkLai) {
        $msgErr = 'Nhập lại mật khẩu mới chưa khớp.';
    } else {
        $pdo->prepare('UPDATE users SET password = :pw WHERE id = :id')
            ->execute(['pw' => password_hash($mkMoi, PASSWORD_DEFAULT), 'id' => $idTaiKhoan]);
        $msgOk = 'Đổi mật khẩu thành công.';
    }
}

$pageTitle  = 'Tài khoản của tôi';
$activeMenu = 'taikhoan';
include __DIR__ . '/../partials/reader-top.php';
?>

<h2 class="page-h">👤 Tài khoản của tôi</h2>

<?php if ($msgOk): ?><div class="alert alert-success"><?= htmlspecialchars($msgOk) ?></div><?php endif; ?>
<?php if ($msgErr): ?><div class="alert alert-error"><?= htmlspecialchars($msgErr) ?></div><?php endif; ?>

<div class="panel">
    <h3>Thông tin độc giả (từ hồ sơ thư viện)</h3>
    <?php if ($docGia): ?>
    <table class="detail-table">
        <tr><th>Mã độc giả</th><td>DG<?= str_pad($docGia['id_doc_gia'], 3, '0', STR_PAD_LEFT) ?></td></tr>
        <tr><th>Họ tên</th><td><?= htmlspecialchars($docGia['ho_ten']) ?></td></tr>
        <tr><th>Ngày sinh</th><td><?= !empty($docGia['ngay_sinh']) ? date('d/m/Y', strtotime($docGia['ngay_sinh'])) : '—' ?></td></tr>
        <tr><th>Giới tính</th><td><?= htmlspecialchars($docGia['gioi_tinh'] ?? '—') ?></td></tr>
        <tr><th>Số điện thoại</th><td><?= htmlspecialchars($docGia['so_dien_thoai'] ?? '—') ?></td></tr>
        <tr><th>Địa chỉ</th><td><?= htmlspecialchars($docGia['dia_chi'] ?? '—') ?></td></tr>
        <tr><th>Username</th><td><?= htmlspecialchars($taiKhoan['username'] ?? '') ?> <small style="color:#9ca3af;">(tài khoản đăng nhập)</small></td></tr>
        <tr><th>Email</th><td><?= htmlspecialchars($taiKhoan['email'] ?? '—') ?></td></tr>
    </table>
    <?php else: ?>
    <p style="color:#9ca3af;">Không tìm thấy hồ sơ độc giả.</p>
    <?php endif; ?>
</div>

<?php if ($docGia): ?>
<div class="panel">
    <h3>✏️ Sửa thông tin cá nhân</h3>
    <form method="POST">
        <div class="form-group">
            <label>Họ tên:</label>
            <input type="text" name="ho_ten" value="<?= htmlspecialchars($docGia['ho_ten']) ?>" required>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="form-group">
                <label>Ngày sinh:</label>
                <input type="date" name="ngay_sinh" value="<?= htmlspecialchars($docGia['ngay_sinh'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Giới tính:</label>
                <select name="gioi_tinh">
                    <option value="">— Chọn —</option>
                    <option value="Nam" <?= ($docGia['gioi_tinh'] ?? '') === 'Nam' ? 'selected' : '' ?>>Nam</option>
                    <option value="Nữ" <?= ($docGia['gioi_tinh'] ?? '') === 'Nữ' ? 'selected' : '' ?>>Nữ</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Số điện thoại:</label>
            <input type="tel" name="so_dien_thoai" value="<?= htmlspecialchars($docGia['so_dien_thoai'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Địa chỉ:</label>
            <input type="text" name="dia_chi" value="<?= htmlspecialchars($docGia['dia_chi'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" value="<?= htmlspecialchars($taiKhoan['email'] ?? '') ?>">
        </div>
        <button type="submit" name="doi_thong_tin">Lưu thay đổi</button>
    </form>
</div>
<?php endif; ?>

<div class="panel">
    <h3>🔒 Đổi mật khẩu</h3>
    <form method="POST">
        <div class="form-group">
            <label>Mật khẩu hiện tại:</label>
            <input type="password" name="mat_khau_cu" required>
        </div>
        <div class="form-group">
            <label>Mật khẩu mới:</label>
            <input type="password" name="mat_khau_moi" required>
        </div>
        <div class="form-group">
            <label>Nhập lại mật khẩu mới:</label>
            <input type="password" name="mat_khau_lai" required>
        </div>
        <button type="submit" name="doi_mat_khau">Đổi mật khẩu</button>
    </form>
</div>

<?php include __DIR__ . '/../partials/reader-bottom.php'; ?>
