<?php
// File: users.php — QUẢN LÝ TÀI KHOẢN (chỉ admin).
// Chỉ được XEM và SỬA. Hệ thống KHÔNG cho xóa tài khoản.
// Sửa tài khoản không đụng tới bảng doc_gia và muon_tra.

// Kiểm tra đăng nhập + quyền admin
require_once __DIR__ . '/Login/auth.php';
requireAdmin();

// Nối database
require_once __DIR__ . '/config/database.php';

// Nhận từ khóa tìm kiếm (giống trang Sách/Độc giả/Mượn trả)
$tuKhoa = trim($_GET['q'] ?? '');
if ($tuKhoa !== '') {
    $stmt = $pdo->prepare(
        'SELECT u.*, d.ho_ten AS ten_doc_gia
         FROM users u LEFT JOIN doc_gia d ON u.id_doc_gia = d.id_doc_gia
         WHERE u.username LIKE :kw1 OR u.ho_ten LIKE :kw2 OR u.email LIKE :kw3
         ORDER BY u.id DESC'
    );
    $kw = '%' . $tuKhoa . '%';
    $stmt->execute(['kw1' => $kw, 'kw2' => $kw, 'kw3' => $kw]);
    $users = $stmt->fetchAll();
} else {
    // Lấy danh sách tài khoản kèm tên độc giả liên kết
    $users = $pdo->query(
        'SELECT u.*, d.ho_ten AS ten_doc_gia
         FROM users u LEFT JOIN doc_gia d ON u.id_doc_gia = d.id_doc_gia
         ORDER BY u.id DESC'
    )->fetchAll();
}

// Danh sách độc giả còn trống (để nối cho tài khoản cũ chưa nối)
$dsDocGia = $pdo->query('SELECT id_doc_gia, ho_ten FROM doc_gia ORDER BY ho_ten')->fetchAll();
$daDung = $pdo->query('SELECT id_doc_gia FROM users WHERE id_doc_gia IS NOT NULL')->fetchAll(PDO::FETCH_COLUMN);

$pageTitle  = 'Quản lý tài khoản';
$activeMenu = 'users';
include __DIR__ . '/partials/top.php';
?>

<div class="page-head">
    <div>
        <h2>👤 Quản lý tài khoản</h2>
        <p class="sub">Tìm thấy <b><?= count($users) ?></b> tài khoản (xem và sửa, không được xóa)</p>
    </div>
</div>

<div class="panel">
    <form class="search-bar" method="GET" action="users.php">
        <input type="text" name="q" placeholder="Tìm tài khoản (tên đăng nhập, họ tên, email)..." value="<?= e($tuKhoa) ?>">
        <button type="submit" class="btn btn-primary" style="width:auto;">Tìm</button>
        <?php if ($tuKhoa !== ''): ?>
            <a href="users.php" class="btn btn-light">Xóa lọc</a>
        <?php endif; ?>
    </form>

    <table class="tbl">
        <thead>
            <tr><th>STT</th><th>Mã TK</th><th>Tài khoản</th><th>Vai trò</th><th>Độc giả liên kết</th><th>Trạng thái</th><th>Thao tác</th></tr>
        </thead>
        <tbody>
            <?php if (!$users): ?>
                <tr><td colspan="7" style="text-align:center;color:#9ca3af;">Không tìm thấy tài khoản nào.</td></tr>
            <?php endif; ?>
            <?php foreach ($users as $stt => $u): ?>
            <tr>
                <td><?= $stt + 1 ?></td>
                <td>U<?= str_pad($u['id'], 3, '0', STR_PAD_LEFT) ?></td>
                <td>
                    <b><code><?= e($u['username']) ?></code></b>
                    <br><small style="color:#9ca3af;"><?= e($u['ho_ten']) ?></small>
                </td>
                <td>
                    <?php if ($u['role'] === 'admin'): ?>
                        <span class="badge b-admin">👑 Admin</span>
                    <?php else: ?>
                        <span class="badge b-user">👤 User</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($u['role'] === 'admin'): ?>
                        <span style="color:#9ca3af;">—</span>
                    <?php elseif (!empty($u['id_doc_gia'])): ?>
                        <!-- Đã liên kết thì KHÓA, không cho đổi -->
                        <b>DG<?= str_pad($u['id_doc_gia'], 3, '0', STR_PAD_LEFT) ?> - <?= e($u['ten_doc_gia'] ?? '') ?></b>
                        <br><small style="color:#9ca3af;">🔒 Đã liên kết</small>
                    <?php else: ?>
                        <!-- Chưa liên kết (tài khoản cũ) thì admin được nối 1 lần -->
                    <form method="POST" action="./CRUD/link-user.php" style="display:flex;gap:6px;align-items:center;">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <select name="id_doc_gia" style="min-width:150px;">
                            <option value="">— Chưa liên kết —</option>
                            <?php foreach ($dsDocGia as $dg): ?>
                            <?php
                            if (in_array($dg['id_doc_gia'], $daDung)) {
                                continue;
                            }
                            ?>
                            <option value="<?= $dg['id_doc_gia'] ?>">
                                DG<?= str_pad($dg['id_doc_gia'], 3, '0', STR_PAD_LEFT) ?> - <?= e($dg['ho_ten']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-light btn-sm" style="width:auto;">Lưu</button>
                    </form>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($u['role'] === 'admin'): ?>
                        <span class="badge b-admin">👑 Quản trị</span>
                    <?php elseif (!empty($u['id_doc_gia'])): ?>
                        <span class="badge b-tra">✅ Đã liên kết</span>
                    <?php else: ?>
                        <span class="badge b-muon">⚠️ Chưa liên kết</span>
                    <?php endif; ?>
                </td>
                <td class="action-cell">
                    <a class="btn-edit btn-sm" href="./CRUD/edit-user.php?id=<?= $u['id'] ?>">Sửa</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p style="color:#6b7280;font-size:13px;margin-top:14px;">
        Tài khoản đăng ký mới <b>tự động tạo + liên kết hồ sơ độc giả</b> và bị khóa liên kết.
        Sửa tài khoản chỉ đổi tên, mật khẩu, vai trò — không đụng tới hồ sơ độc giả và lịch sử mượn.
    </p>
</div>

<?php include __DIR__ . '/partials/bottom.php'; ?>
