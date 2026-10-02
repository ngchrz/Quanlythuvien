<?php
// File: CRUD/edit-user.php — SỬA TÀI KHOẢN (chỉ admin).
// Được sửa: tên tài khoản, email, mật khẩu, vai trò.
// KHÔNG được: đổi/bỏ độc giả liên kết, đụng tới bảng doc_gia và muon_tra.
// KHÔNG có chức năng xóa tài khoản trong hệ thống.

// Kiểm tra đăng nhập + quyền admin
require_once __DIR__ . '/../Login/auth.php';
requireAdmin();

// Nối database
require_once __DIR__ . '/../config/database.php';

// Lấy id tài khoản trên link
$id = $_GET['id'] ?? '';
if (empty($id)) {
    header('Location: ../users.php');
    exit();
}

// Lấy tài khoản cần sửa kèm tên độc giả liên kết
$stmt = $pdo->prepare(
    'SELECT u.*, d.ho_ten AS ten_doc_gia
     FROM users u LEFT JOIN doc_gia d ON u.id_doc_gia = d.id_doc_gia
     WHERE u.id = :id'
);
$stmt->execute(['id' => $id]);
$tk = $stmt->fetch();

if (!$tk) {
    flash('error', 'Không tìm thấy tài khoản.');
    header('Location: ../users.php');
    exit();
}

// Giá trị ban đầu cho form
$username = $tk['username'];
$email = $tk['email'] ?? '';
$role = $tk['role'];
$loi = '';

// Có phải đang sửa chính mình không (tự sửa thì không được đổi vai trò)
$laMinh = ((int) $id === (int) $_SESSION['id_taikhoan']);

// Người dùng bấm nút Lưu
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = normalizeUsername($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $matKhau = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? $tk['role'];

    if ($username == '') {
        $loi = 'Vui lòng nhập tên tài khoản.';
    } elseif (strlen($username) < 3) {
        $loi = 'Tên tài khoản phải từ 3 ký tự trở lên.';
    } elseif (isValidUsername($username) == false) {
        $loi = 'Tên tài khoản chỉ được chứa chữ cái không dấu và số.';
    } elseif ($email != '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $loi = 'Email chưa đúng định dạng.';
    } elseif ($matKhau != '' && strlen($matKhau) < 4) {
        $loi = 'Mật khẩu mới phải từ 4 ký tự trở lên.';
    } elseif ($matKhau != '' && strtolower($matKhau) == strtolower($tk['username'])) {
        $loi = 'Mật khẩu không được trùng với tên tài khoản.';
    } else {
        // Tên đã có người khác dùng chưa (không tính chính mình)
        $check = $pdo->prepare('SELECT COUNT(*) FROM users WHERE LOWER(username) = :u AND id != :id');
        $check->execute(['u' => $username, 'id' => $id]);
        if ($check->fetchColumn() > 0) {
            $loi = 'Tên tài khoản đã tồn tại.';
        } else {
            // Tự sửa mình thì giữ nguyên vai trò (tránh tự mất quyền admin)
            if ($laMinh) {
                $role = $tk['role'];
            }
            if ($role !== 'admin' && $role !== 'user') {
                $role = 'user';
            }

            // Chỉ UPDATE bảng users (không đụng doc_gia, muon_tra)
            if ($matKhau != '') {
                // Có nhập mật khẩu mới thì đổi
                $pdo->prepare(
                    'UPDATE users SET username = :u, email = :e, password = :p, role = :r WHERE id = :id'
                )->execute([
                    'u' => $username,
                    'e' => $email != '' ? $email : null,
                    'p' => password_hash($matKhau, PASSWORD_DEFAULT),
                    'r' => $role,
                    'id' => $id,
                ]);
            } else {
                // Bỏ trống mật khẩu thì giữ nguyên mật khẩu cũ
                $pdo->prepare(
                    'UPDATE users SET username = :u, email = :e, role = :r WHERE id = :id'
                )->execute([
                    'u' => $username,
                    'e' => $email != '' ? $email : null,
                    'r' => $role,
                    'id' => $id,
                ]);
            }

            // Nếu sửa chính mình thì cập nhật luôn session đang dùng
            if ($laMinh) {
                $_SESSION['username'] = $username;
            }
            flash('success', 'Sửa tài khoản thành công!');
            header('Location: ../users.php');
            exit();
        }
    }
}

$pageTitle  = 'Sửa tài khoản';
$activeMenu = 'users';
$basePath = '../';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa tài khoản — Quản lý thư viện</title>
    <link rel="stylesheet" href="../css/style.css?v=7">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
<?php include '../partials/inner-top.php'; ?>

<div class="form-card">
    <h2>Sửa tài khoản (U<?= str_pad($id, 3, '0', STR_PAD_LEFT) ?>)</h2>
    <p class="form-sub">Chỉ đổi tên, email, mật khẩu, vai trò — liên kết độc giả bị khóa</p>

    <?php if ($loi != ''): ?>
        <div class="alert alert-error"><?= e($loi) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="username">Tên tài khoản: *</label>
            <input type="text" id="username" name="username" value="<?= e($username) ?>" required maxlength="50">
            <small>Chỉ chữ cái không dấu và số. Đổi tên xong phải đăng nhập lại bằng tên mới.</small>
        </div>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?= e($email) ?>" placeholder="vidu@gmail.com" maxlength="100">
        </div>

        <div class="form-group">
            <label for="password">Mật khẩu mới:</label>
            <input type="password" id="password" name="password" placeholder="Bỏ trống = giữ nguyên mật khẩu cũ">
            <small>Tối thiểu 4 ký tự, không trùng tên tài khoản.</small>
        </div>

        <div class="form-group">
            <label for="role">Vai trò: *</label>
            <?php if ($laMinh): ?>
                <input type="text" value="<?= $tk['role'] === 'admin' ? 'Admin (chính bạn, không được đổi)' : 'User' ?>" disabled>
                <small>Không được tự đổi vai trò của chính mình.</small>
            <?php else: ?>
                <select id="role" name="role">
                    <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>User (độc giả)</option>
                    <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin (quản trị)</option>
                </select>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Độc giả liên kết:</label>
            <?php if (!empty($tk['id_doc_gia'])): ?>
                <input type="text" value="DG<?= str_pad($tk['id_doc_gia'], 3, '0', STR_PAD_LEFT) ?> - <?= e($tk['ten_doc_gia'] ?? '') ?>" disabled>
                <small>Đã liên kết, không được thay đổi.</small>
            <?php else: ?>
                <input type="text" value="Chưa liên kết" disabled>
            <?php endif; ?>
        </div>

        <div class="form-buttons">
            <button type="submit"><i class="fas fa-save"></i> Lưu thay đổi</button>
            <a href="../users.php" class="return"><i class="fas fa-times"></i> Hủy</a>
        </div>
    </form>
</div>

<?php include '../partials/inner-bottom.php'; ?>
</body>
</html>
