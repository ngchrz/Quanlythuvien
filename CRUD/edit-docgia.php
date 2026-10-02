<?php
// File: CRUD/edit-docgia.php — SỬA ĐỘC GIẢ (chỉ admin).

// Kiểm tra đăng nhập + quyền admin
require_once __DIR__ . '/../Login/auth.php';
requireAdmin();

// Nối database
require_once __DIR__ . '/../config/database.php';

$id = $_GET['id'] ?? '';

if (empty($id)) {
    header("Location: ../docgia.php");
    exit();
}


// ==========================================
// LẤY THÔNG TIN ĐỘC GIẢ
// ==========================================

$sql = "
    SELECT *
    FROM doc_gia
    WHERE id_doc_gia = :id
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    'id' => $id
]);

$docGia = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$docGia) {
    die("Không tìm thấy độc giả!");
}


// ==========================================
// GIÁ TRỊ BAN ĐẦU
// ==========================================

$hoTen = $docGia['ho_ten'];
$ngaySinh = $docGia['ngay_sinh'];
$gioiTinh = $docGia['gioi_tinh'];
$soDienThoai = $docGia['so_dien_thoai'];
$diaChi = $docGia['dia_chi'];

$loi = "";


// ==========================================
// XỬ LÝ CẬP NHẬT
// ==========================================

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
        // KIỂM TRA TRÙNG SỐ ĐIỆN THOẠI
        // Không tính chính độc giả đang sửa
        // ==========================================

        $checkSql = "
            SELECT COUNT(*)
            FROM doc_gia
            WHERE so_dien_thoai = :so_dien_thoai
              AND id_doc_gia != :id_doc_gia
        ";

        $check = $pdo->prepare($checkSql);

        $check->execute([
            'so_dien_thoai' => $soDienThoai,
            'id_doc_gia' => $id
        ]);

        $biTrung = $check->fetchColumn();


        if ($biTrung > 0) {

            $loi = "Số điện thoại này đã được sử dụng!";

        } else {

            // ==========================================
            // CẬP NHẬT
            // ==========================================

            $updateSql = "
                UPDATE doc_gia
                SET
                    ho_ten = :ho_ten,
                    ngay_sinh = :ngay_sinh,
                    gioi_tinh = :gioi_tinh,
                    so_dien_thoai = :so_dien_thoai,
                    dia_chi = :dia_chi
                WHERE id_doc_gia = :id_doc_gia
            ";

            $update = $pdo->prepare($updateSql);

            $update->execute([
                'ho_ten' => $hoTen,
                'ngay_sinh' => $ngaySinh,
                'gioi_tinh' => $gioiTinh,
                'so_dien_thoai' => $soDienThoai,
                'dia_chi' => $diaChi,
                'id_doc_gia' => $id
            ]);

            flash('success', 'Cập nhật độc giả thành công!');
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

    <title>Sửa độc giả</title>

    <link rel="stylesheet" href="../css/style.css?v=7">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

</head>

<body>
    <?php $pageTitle = 'Sửa độc giả'; $activeMenu = 'docgia'; $basePath = '../'; include '../partials/inner-top.php'; ?>

<div class="form-card">

    <h2>Sửa độc giả (DG<?= str_pad($id, 3, '0', STR_PAD_LEFT) ?>)</h2>
    <p class="form-sub">Để trống không được — SĐT 9-11 số, không trùng người khác</p>

    <?php if (!empty($loi)): ?>
        <div class="alert alert-error"><?= e($loi) ?></div>
    <?php endif; ?>

    <form method="POST">

        <!-- HỌ TÊN -->
        <div class="form-group">
            <label for="ho_ten">Họ tên: *</label>
            <input type="text" id="ho_ten" name="ho_ten" value="<?= e($hoTen) ?>" required maxlength="100">
        </div>

        <!-- NGÀY SINH -->
        <div class="form-group">
            <label for="ngay_sinh">Ngày sinh: *</label>
            <input type="date" id="ngay_sinh" name="ngay_sinh" value="<?= e($ngaySinh) ?>" required max="<?= date('Y-m-d') ?>">
            <small>Không được ở tương lai.</small>
        </div>

        <!-- GIỚI TÍNH -->
        <div class="form-group">
            <label for="gioi_tinh">Giới tính: *</label>
            <select id="gioi_tinh" name="gioi_tinh" required>
                <option value="">-- Chọn giới tính --</option>
                <option value="Nam" <?= ($gioiTinh === 'Nam') ? 'selected' : '' ?>>Nam</option>
                <option value="Nữ" <?= ($gioiTinh === 'Nữ') ? 'selected' : '' ?>>Nữ</option>
            </select>
        </div>

        <!-- SỐ ĐIỆN THOẠI -->
        <div class="form-group">
            <label for="so_dien_thoai">Số điện thoại: *</label>
            <input type="tel" id="so_dien_thoai" name="so_dien_thoai" value="<?= e($soDienThoai) ?>" required pattern="[0-9]{9,11}" title="9-11 chữ số">
            <small>Chỉ 9-11 chữ số, không trùng người khác.</small>
        </div>

        <!-- ĐỊA CHỈ -->
        <div class="form-group">
            <label for="dia_chi">Địa chỉ: *</label>
            <input type="text" id="dia_chi" name="dia_chi" value="<?= e($diaChi) ?>" required maxlength="255">
        </div>

        <!-- NÚT -->
        <div class="form-buttons">
            <button type="submit"><i class="fas fa-save"></i> Lưu thay đổi</button>
            <a href="../docgia.php" class="return"><i class="fas fa-times"></i> Hủy</a>
        </div>

    </form>

</div>

<?php include '../partials/inner-bottom.php'; ?>
</body>

</html>