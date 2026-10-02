<?php
// File: Login/login.php — Trang ĐĂNG NHẬP.

// 1. Mở session + nối database
session_start();
require_once __DIR__ . '/../config/database.php';

// 2. Ai đăng nhập rồi thì cho vào thẳng trang của mình
if (!empty($_SESSION['id_taikhoan'])) {
    if (($_SESSION['role'] ?? 'user') === 'admin') {
        header('Location: ../index.php');       // admin vào trang quản trị
    } else {
        header('Location: ../User/index.php');  // độc giả vào cổng riêng
    }
    exit();
}

// 3. Nhận dữ liệu từ form
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Đưa username về chữ thường để MinhPhuc = minhphuc
    $username = normalizeUsername($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username == '' || $password == '') {
        $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';
    } else {
        // Tìm tài khoản (LOWER để gõ hoa hay thường đều được)
        $stmt = $pdo->prepare('SELECT * FROM users WHERE LOWER(username) = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        // Sai tên hoặc sai mật khẩu
        if (!$user || !password_verify($password, $user['password'])) {
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
        } else {
            // Đúng thì lưu vào session
            session_regenerate_id(true);
            $_SESSION['id_taikhoan'] = $user['id'];
            $_SESSION['username']    = $user['username'];
            $_SESSION['ho_ten']      = $user['ho_ten'];
            $_SESSION['role']        = $user['role'];
            $_SESSION['id_docgia']   = $user['id_doc_gia'];

            // Admin và độc giả đi 2 cổng khác nhau
            if ($_SESSION['role'] === 'admin') {
                header('Location: ../index.php');
            } else {
                header('Location: ../User/index.php');
            }
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập — Quản lý thư viện</title>
    <link rel="stylesheet" href="../css/style.css?v=7">
    <link rel="stylesheet" href="../css/auth.css?v=6">
</head>
<body class="auth-body">
    <div class="auth-wrap">
        <!-- Logo và tên thư viện -->
        <div class="auth-brand-top">
            <div class="logo-big">📚</div>
            <b>THƯ VIỆN BÁCH KHOA</b>
            <p>Hệ thống quản lý thư viện</p>
        </div>
        <!-- Form đăng nhập -->
        <div class="auth-card">
            <h2>Đăng nhập tài khoản</h2>
            <p class="auth-sub">Chào mừng bạn quay trở lại</p>

            <!-- Báo đăng ký xong -->
            <?php if (isset($_GET['registered'])): ?>
                <div class="alert alert-success">✓ Đăng ký thành công! Mời bạn đăng nhập.</div>
            <?php endif; ?>

            <!-- Báo lỗi -->
            <?php if ($error != ''): ?>
                <div class="alert alert-error">✕ <?= e($error) ?></div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="username">Tên tài khoản</label>
                    <input type="text" id="username" name="username" placeholder="vidu: minhphuc123"
                           value="<?= e($_POST['username'] ?? '') ?>" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <div class="pass-wrap">
                        <input type="password" id="password" name="password" placeholder="Nhập mật khẩu" required>
                        <button type="button" class="pass-toggle" data-target="password" title="Hiện / ẩn mật khẩu">👁</button>
                    </div>
                </div>
                <button type="submit">Đăng nhập</button>
            </form>

            <p class="auth-link">Chưa có tài khoản? <a href="register.php">Đăng ký tài khoản</a></p>
        </div>
    </div>

<script>
// Nút con mắt để hiện / ẩn mật khẩu
var nutMat = document.querySelectorAll('.pass-toggle');
nutMat.forEach(function (nut) {
    nut.addEventListener('click', function () {
        var oNhap = document.getElementById(nut.getAttribute('data-target'));
        if (oNhap.type === 'password') {
            oNhap.type = 'text';
            nut.textContent = '🙈';
        } else {
            oNhap.type = 'password';
            nut.textContent = '👁';
        }
    });
});
</script>
</body>
</html>
