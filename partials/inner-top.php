<?php
// File: partials/inner-top.php — Khung ADMIN cho các form trong CRUD/.
// Trang CRUD đã có <head> sẵn nên file này chỉ in menu ngang + mở nội dung.
// Cuối trang nhớ include partials/inner-bottom.php để đóng khung.
if (!isset($basePath)) {
    $basePath = '../';
}
if (!isset($pageTitle)) {
    $pageTitle = 'Quản lý thư viện';
}
if (!isset($activeMenu)) {
    $activeMenu = '';
}
// Nếu trang chưa có $currentUser thì tự lấy từ session
if (!isset($currentUser)) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $currentUser = [
        'id'         => $_SESSION['id_taikhoan'],
        'username'   => $_SESSION['username'],
        'ho_ten'     => $_SESSION['ho_ten'],
        'role'       => $_SESSION['role'],
        'id_doc_gia' => $_SESSION['id_docgia'],
    ];
}
if ($currentUser['role'] === 'admin') {
    $admin = true;
} else {
    $admin = false;
}
$tenHienThi = $currentUser['ho_ten'];
$chuCaiDau = mb_strtoupper(mb_substr($tenHienThi, 0, 1, 'UTF-8'), 'UTF-8');
?>
<!-- Thanh menu ngang -->
<header class="topnav">
    <div class="topnav-in">
        <a class="brand" href="<?= $basePath ?>index.php">
            <span class="brand-icon">📚</span><span>Thư viện</span>
        </a>
        <button class="icon-btn" id="btnMenu" title="Mở menu">☰</button>
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
                <a class="danger" href="<?= $basePath ?>Login/logout.php"><span>🚪</span>Đăng xuất</a>
            </div>
        </div>
    </div>
</header>

<!-- Nội dung form -->
<main class="page">
    <?php showFlash(); ?>
