<?php
// File: partials/top.php — Khung ADMIN (menu ngang + noi dung).
// Trang nào muốn dùng thì chuẩn bị 3 biến rồi include:
//   $pageTitle  : tên trang
//   $activeMenu : home / sach / docgia / muontra / users
//   $basePath   : './' (trang ngoài) hoặc '../' (trang trong CRUD)
if (!isset($basePath)) {
    $basePath = './';
}
if (!isset($pageTitle)) {
    $pageTitle = 'Quản lý thư viện';
}
if (!isset($activeMenu)) {
    $activeMenu = '';
}
// Có phải admin không (để hiện thêm mục Tài khoản)
if (function_exists('isAdmin') && isAdmin()) {
    $admin = true;
} else {
    $admin = false;
}
// Lấy chữ cái đầu của tên làm avatar
$tenHienThi = $currentUser['ho_ten'];
$chuCaiDau = mb_strtoupper(mb_substr($tenHienThi, 0, 1, 'UTF-8'), 'UTF-8');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — Quản lý thư viện</title>
    <link rel="stylesheet" href="<?= $basePath ?>css/style.css?v=9">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
<!-- Thanh menu ngang -->
<header class="topnav">
    <div class="topnav-in">
        <a class="brand" href="<?= $basePath ?>index.php">
            <span class="brand-icon">📚</span><span>Thư viện</span>
        </a>
        <button class="icon-btn" id="btnMenu" title="Mở menu">☰</button>
        <!-- Các mục menu -->
        <nav class="menu" id="mainMenu">
            <a class="<?= $activeMenu === 'home' ? 'active' : '' ?>" href="<?= $basePath ?>index.php">🏠 Dashboard</a>
            <a class="<?= $activeMenu === 'sach' ? 'active' : '' ?>" href="<?= $basePath ?>product.php">📚 Sách</a>
            <a class="<?= $activeMenu === 'docgia' ? 'active' : '' ?>" href="<?= $basePath ?>docgia.php">👥 Độc giả</a>
            <a class="<?= $activeMenu === 'muontra' ? 'active' : '' ?>" href="<?= $basePath ?>muontra.php">🔄 Mượn trả</a>
            <?php if ($admin): ?>
            <a class="<?= $activeMenu === 'users' ? 'active' : '' ?>" href="<?= $basePath ?>users.php">👤 Tài khoản</a>
            <?php endif; ?>
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
                <a href="<?= $basePath ?>users.php"><span>👤</span>Tài khoản</a>
                <a class="danger" href="<?= $basePath ?>Login/logout.php" data-confirm="Bạn có chắc muốn đăng xuất?|Đăng xuất|🚪"><span>🚪</span>Đăng xuất</a>
            </div>
        </div>
    </div>
</header>

<!-- Nội dung từng trang (mỗi trang tự in tiêu đề của mình) -->
<main class="page">
    <?php showFlash(); ?>
