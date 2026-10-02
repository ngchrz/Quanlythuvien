<?php
// File: partials/reader-top.php — Khung ĐỘC GIẢ (menu ngang riêng, cùng kiểu Admin).
// Trang nào muốn dùng thì chuẩn bị 2 biến rồi include:
//   $pageTitle  : tên trang
//   $activeMenu : home / sach / muon / lichsu / taikhoan

if (!isset($pageTitle)) {
    $pageTitle = 'Thư viện';
}
if (!isset($activeMenu)) {
    $activeMenu = '';
}
// Nếu trang chưa có $currentUser thì tự lấy từ session
// (thường auth_user.php đã tạo sẵn, đoạn này để hết báo đỏ)
if (!isset($currentUser)) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $currentUser = [
        'id'         => $_SESSION['id_taikhoan'] ?? null,
        'username'   => $_SESSION['username'] ?? '',
        'ho_ten'     => $_SESSION['ho_ten'] ?? ($_SESSION['username'] ?? ''),
        'role'       => $_SESSION['role'] ?? 'user',
        'id_doc_gia' => $_SESSION['id_docgia'] ?? null,
    ];
}
$tenHienThi = $currentUser['ho_ten'];
$chuCaiDau = mb_strtoupper(mb_substr($tenHienThi, 0, 1, 'UTF-8'), 'UTF-8');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — Thư viện độc giả</title>
    <link rel="stylesheet" href="../css/style.css?v=9">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
<!-- Thanh menu ngang của độc giả -->
<header class="topnav">
    <div class="topnav-in">
        <a class="brand" href="index.php">
            <span class="brand-icon">📖</span><span>Thư viện</span>
        </a>
        <button class="icon-btn" id="btnMenu" title="Mở menu">☰</button>
        <nav class="menu" id="mainMenu">
            <a class="<?= $activeMenu === 'home' ? 'active' : '' ?>" href="index.php">🏠 Trang chủ</a>
            <a class="<?= $activeMenu === 'sach' ? 'active' : '' ?>" href="sach.php">📚 Khám phá sách</a>
            <a class="<?= $activeMenu === 'muon' ? 'active' : '' ?>" href="muon.php">🔄 Đang mượn</a>
            <a class="<?= $activeMenu === 'lichsu' ? 'active' : '' ?>" href="lichsu.php">📋 Lịch sử</a>
            <a class="<?= $activeMenu === 'taikhoan' ? 'active' : '' ?>" href="taikhoan.php">👤 Tài khoản</a>
        </nav>
        <!-- Bấm vào tên thì mở menu Tài khoản / Đăng xuất -->
        <div class="user-chip has-menu" id="userChip">
            <span class="avatar"><?= e($chuCaiDau) ?></span>
            <span class="uname"><?= e($tenHienThi) ?></span>
            <span class="caret">▾</span>
            <div class="dropdown-menu" id="userMenu">
                <div class="dropdown-head">
                    <b><?= e($tenHienThi) ?></b>
                    <small><?= e($currentUser['username']) ?></small>
                </div>
                <a href="taikhoan.php"><span>👤</span>Tài khoản</a>
                <a class="danger" href="../Login/logout.php" data-confirm="Bạn có chắc muốn đăng xuất?|Đăng xuất|🚪"><span>🚪</span>Đăng xuất</a>
            </div>
        </div>
    </div>
</header>

<!-- Nội dung từng trang -->
<main class="page">
    <?php showFlash(); ?>
