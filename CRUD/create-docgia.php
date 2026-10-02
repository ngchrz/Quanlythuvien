<?php
// File: CRUD/create-docgia.php — THÊM ĐỘC GIẢ (chỉ admin).

// Kiểm tra đăng nhập + quyền admin
require_once __DIR__ . '/../Login/auth.php';
requireAdmin();

// Nối database
require_once __DIR__ . '/../config/database.php';

$loi = "";

$hoTen = "";
$ngaySinh = "";
$gioiTinh = "";
$soDienThoai = "";
$diaChi = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $hoTen = trim($_POST['ho_ten'] ?? '');
    $ngaySinh = $_POST['ngay_sinh'] ?? '';
    $gioiTinh = $_POST['gioi_tinh'] ?? '';
    $soDienThoai = trim($_POST['so_dien_thoai'] ?? '');
    $diaChi = trim($_POST['dia_chi'] ?? '');

    // ==========================================
    // KIỂM TRA DỮ LIỆU
    // ==========================================

    if (
        empty($hoTen) ||
        empty($ngaySinh) ||
        empty($gioiTinh) ||
        empty($soDienThoai) ||
        empty($diaChi)
    ) {

        $loi = "Vui lòng nhập đầy đủ thông tin!";

    } elseif (mb_strlen($hoTen) > 100) {

        $loi = "Họ tên không được quá 100 ký tự.";

    } elseif (!preg_match('/^[0-9]{9,11}$/', $soDienThoai)) {

        $loi = "Số điện thoại phải gồm 9-11 chữ số.";

    } elseif (!in_array($gioiTinh, ['Nam', 'Nữ'], true)) {

        $loi = "Giới tính không hợp lệ.";

    } elseif (strtotime($ngaySinh) === false || $ngaySinh > date('Y-m-d') || $ngaySinh < '1900-01-01') {

        $loi = "Ngày sinh chưa đúng (không được ở tương lai).";

    } else {

        // ==========================================
        // KIỂM TRA SỐ ĐIỆN THOẠI ĐÃ TỒN TẠI
        // ==========================================

        $checkSql = "
            SELECT COUNT(*)
            FROM doc_gia
            WHERE so_dien_thoai = :so_dien_thoai
        ";

        $check = $pdo->prepare($checkSql);

        $check->execute([
            'so_dien_thoai' => $soDienThoai
        ]);

        if ($check->fetchColumn() > 0) {

            $loi = "Số điện thoại này đã được đăng ký!";

        } else {

            // ==========================================
            // THÊM ĐỘC GIẢ
            // ==========================================

            $sql = "
                INSERT INTO doc_gia
                (
                    ho_ten,
                    ngay_sinh,
                    gioi_tinh,
                    so_dien_thoai,
                    dia_chi
                )
                VALUES
                (
                    :ho_ten,
                    :ngay_sinh,
                    :gioi_tinh,
                    :so_dien_thoai,
                    :dia_chi
                )
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                'ho_ten' => $hoTen,
                'ngay_sinh' => $ngaySinh,
                'gioi_tinh' => $gioiTinh,
                'so_dien_thoai' => $soDienThoai,
                'dia_chi' => $diaChi
            ]);

            flash('success', 'Thêm độc giả thành công!');
            header("Location: ../docgia.php");
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

    <title>Thêm độc giả</title>

    <link rel="stylesheet" href="../css/style.css?v=7">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >
</head>

<body>

<?php $pageTitle = 'Thêm độc giả'; $activeMenu = 'docgia'; $basePath = '../'; include '../partials/inner-top.php'; ?>

<div class="form-card">

    <h2>Thêm độc giả</h2>
    <p class="form-sub">Nhập đầy đủ họ tên, ngày sinh, SĐT và địa chỉ</p>

    <?php if (!empty($loi)): ?>
        <div class="alert alert-error"><?= e($loi) ?></div>
    <?php endif; ?>

    <form method="POST">

        <div class="form-group">
            <label for="ho_ten">Họ tên: *</label>
            <input type="text" id="ho_ten" name="ho_ten" value="<?= e($hoTen) ?>"
                placeholder="Nguyễn Văn A" required maxlength="100">
        </div>


        <div class="form-group">
            <label for="ngay_sinh">Ngày sinh: *</label>
            <input type="date" id="ngay_sinh" name="ngay_sinh" value="<?= e($ngaySinh) ?>"
                required max="<?= date('Y-m-d') ?>">
            <small>Không được ở tương lai.</small>
        </div>


        <div class="form-group">
            <label for="gioi_tinh">Giới tính: *</label>
            <select id="gioi_tinh" name="gioi_tinh" required>
                <option value="">-- Chọn giới tính --</option>
                <option value="Nam" <?= ($gioiTinh === 'Nam') ? 'selected' : '' ?>>Nam</option>
                <option value="Nữ" <?= ($gioiTinh === 'Nữ') ? 'selected' : '' ?>>Nữ</option>
            </select>
        </div>


        <div class="form-group">
            <label for="so_dien_thoai">Số điện thoại: *</label>
            <input type="tel" id="so_dien_thoai" name="so_dien_thoai" value="<?= e($soDienThoai) ?>"
                placeholder="09xxxxxxxx" required pattern="[0-9]{9,11}" title="9-11 chữ số">
            <small>Chỉ 9-11 chữ số, không trùng người khác.</small>
        </div>


        <div class="form-group">
            <label for="dia_chi">Địa chỉ: *</label>
            <input type="text" id="dia_chi" name="dia_chi" value="<?= e($diaChi) ?>"
                placeholder="Nhập địa chỉ" required maxlength="255">
        </div>


        <div class="form-buttons">
            <button type="submit"><i class="fas fa-save"></i> Lưu</button>
            <a href="../docgia.php" class="return"><i class="fas fa-times"></i> Hủy</a>
        </div>

    </form>

</div>

<?php include '../partials/inner-bottom.php'; ?>
</body>
</html>