<?php
// File: CRUD/edit.php — SỬA SÁCH (chỉ admin).
// Không chọn ảnh mới thì giữ ảnh cũ.

// Kiểm tra đăng nhập + quyền admin
require_once __DIR__ . '/../Login/auth.php';
requireAdmin();

// Nối database
require_once __DIR__ . '/../config/database.php';

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    flash('error', 'Mã sách không hợp lệ.');
    header("Location: ../product.php");
    exit();
}

// Lấy sách hiện tại
$sql = "SELECT * FROM quyensach WHERE id_sach = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);

$sach = $stmt->fetch();

if (!$sach) {
    flash('error', 'Sách không tồn tại.');
    header("Location: ../product.php");
    exit();
}

$error = "";

// Giá trị hiển thị (ưu tiên POST khi lỗi để không mất chữ đã gõ)
$name = $sach['name'];
$tac_gia = $sach['tac_gia'];
$the_loai = $sach['the_loai'];
$so_luong = (string)($sach['so_luong'] ?? 1);

// =============================
// XỬ LÝ CẬP NHẬT
// =============================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Lấy dữ liệu từ form
    $name = trim($_POST['name'] ?? "");
    $tac_gia = trim($_POST['tac_gia'] ?? "");
    $the_loai = trim($_POST['the_loai'] ?? "");
    $so_luong = trim((string)($_POST['so_luong'] ?? "1"));

    // Giữ ảnh cũ
    $img = $sach['img'];

    // Kiểm tra bắt buộc
    if ($name === "" || $tac_gia === "" || $the_loai === "") {
        $error = "Vui lòng nhập đầy đủ tên sách, tác giả, thể loại.";
    } elseif (mb_strlen($name) > 255 || mb_strlen($tac_gia) > 255 || mb_strlen($the_loai) > 255) {
        $error = "Tên sách, tác giả, thể loại không được quá 255 ký tự.";
    } elseif (!ctype_digit($so_luong) || (int)$so_luong < 1 || (int)$so_luong > 1000) {
        $error = "Số lượng phải là số nguyên từ 1 đến 1000.";
    } else {
        // Không được ít hơn số đang cho mượn
        $dangMuon = $pdo->prepare("SELECT COUNT(*) FROM muon_tra WHERE id_sach = ? AND ngay_tra IS NULL");
        $dangMuon->execute([$id]);
        if ((int)$so_luong < (int)$dangMuon->fetchColumn()) {
            $error = "Số lượng không được nhỏ hơn số bản đang cho mượn.";
        } else {
            // Trùng tên + tác giả với sách khác?
            $trung = $pdo->prepare("SELECT COUNT(*) FROM quyensach WHERE name = ? AND tac_gia = ? AND id_sach != ?");
            $trung->execute([$name, $tac_gia, $id]);
            if ($trung->fetchColumn() > 0) {
                $error = "Đã có sách khác cùng tên + tác giả.";
            }
        }
    }

    // =============================
    // KIỂM TRA FILE ẢNH (nếu có chọn mới)
    // =============================

    if ($error === "" && isset($_FILES['img']) && $_FILES['img']['error'] !== UPLOAD_ERR_NO_FILE) {

        // Kiểm tra upload có lỗi không
        if ($_FILES['img']['error'] !== UPLOAD_ERR_OK) {
            $error = "Có lỗi khi tải file lên.";
        } else {
            $file = $_FILES['img'];
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            if (!in_array($extension, $allowed)) {
                $error = "Chỉ được chọn ảnh JPG, JPEG, PNG, GIF hoặc WEBP.";
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $error = "Ảnh không được lớn hơn 5MB.";
            } else {
                // Kiểm tra MIME thật (chống đổi đuôi giả ảnh)
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : "";
                if ($finfo) finfo_close($finfo);
                $mimeOk = ["image/jpeg" => "jpg", "image/png" => "png", "image/gif" => "gif", "image/webp" => "webp"];
                if (!isset($mimeOk[$mime])) {
                    $error = "File không phải là ảnh hợp lệ.";
                } else {
                    $newName = uniqid("book_", false) . '.' . $mimeOk[$mime];
                    $uploadPath = "../img/product/" . $newName;
                    if (!is_dir("../img/product/")) mkdir("../img/product/", 0777, true);
                    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                        $img = $newName;
                    } else {
                        $error = "Không thể upload ảnh.";
                    }
                }
            }
        }
    }


    // =============================
    // NẾU KHÔNG CÓ LỖI → UPDATE
    // =============================

    if ($error === "") {

        $sql = "UPDATE quyensach
                SET name = ?,
                    img = ?,
                    tac_gia = ?,
                    the_loai = ?,
                    so_luong = ?
                WHERE id_sach = ?";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $name,
            $img,
            $tac_gia,
            $the_loai,
            (int)$so_luong,
            $id
        ]);


        // Nếu có ảnh mới thì xóa ảnh cũ
        if ($img !== $sach['img'] && !empty($sach['img'])) {

            $oldImage = "../img/product/" . basename($sach['img']);

            if (file_exists($oldImage)) {
                unlink($oldImage);
            }
        }

        // Quay về danh sách
        flash('success', 'Cập nhật sách thành công!');
        header("Location: ../product.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">

    <title>Sửa sách</title>
    <link rel="stylesheet" href="../css/style.css?v=8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

    <?php $pageTitle = 'Sửa sách'; $activeMenu = 'sach'; $basePath = '../'; include '../partials/inner-top.php'; ?>

    <div class="form-card">
    <h2>Sửa sách (S<?= str_pad($sach['id_sach'], 3, '0', STR_PAD_LEFT) ?>)</h2>
    <p class="form-sub">Để trống ảnh mới = giữ ảnh cũ</p>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="boxAll">
            <div class="box-1">
                <div>

                    <label>Tên sách *</label>

                    <input
                        type="text"
                        name="name"
                        value="<?= e($name) ?>"
                        required
                        maxlength="255">

                </div>

                <br>


                <div>

                    <label>Tác giả *</label>

                    <input
                        type="text"
                        name="tac_gia"
                        value="<?= e($tac_gia) ?>"
                        required
                        maxlength="255">

                </div>

                <br>

                <div>


                    <label>Thể loại *</label>

                    <input
                        type="text"
                        name="the_loai"
                        value="<?= e($the_loai) ?>"
                        required
                        maxlength="255">

                </div>

                <br>

                <div>
                    <label>Số lượng (bản) *</label>
                    <input type="number" name="so_luong" value="<?= e($so_luong) ?>" min="1" max="1000" required>
                </div>

                <br>

                <button type="submit">
                    Lưu thay đổi
                </button>

                <a class="return" href="../product.php">
                    Hủy
                </a>

            </div>

            <div class="box-2">

                <label>Ảnh mới (để trống = giữ cũ)</label>

                <input
                    type="file"
                    name="img"
                    id="img"
                    accept="image/jpeg,image/png,image/gif,image/webp">

                <div id="uploadMessage"></div>

                <?php if (!empty($sach['img']) && file_exists("../img/product/" . $sach['img'])): ?>
                <img
                    id="preview"
                    src="../img/product/<?= e($sach['img']) ?>"
                    width="150">
                <?php else: ?>
                <img id="preview" src="" width="150" style="display:none;">
                <div class="book-cover placeholder" style="width:150px;height:180px;">📖</div>
                <?php endif; ?>

            </div>
        </div>
        <br>




    </form>
    </div>


    <?php include '../partials/inner-bottom.php'; ?>
</body>
<script>
    const imgInput = document.getElementById("img");
    const message = document.getElementById("uploadMessage");
    const preview = document.getElementById("preview");
    const anhCu = <?= json_encode(!empty($sach['img']) ? "../img/product/" . $sach['img'] : "") ?>;

    imgInput.addEventListener("change", function() {

        const file = this.files[0];

        // Chưa chọn file
        if (!file) {
            message.innerHTML = "";
            if (anhCu) preview.src = anhCu;
            return;
        }

        // Các loại ảnh cho phép
        const allowed = ["jpg", "jpeg", "png", "webp", "gif"];

        // Lấy đuôi file
        const extension = file.name
            .split(".")
            .pop()
            .toLowerCase();

        // =========================
        // FILE SAI
        // =========================

        if (!allowed.includes(extension)) {

            message.innerHTML =
                "Chỉ được chọn ảnh JPG, JPEG, PNG, GIF hoặc WEBP";

            message.className = "upload-error";

            // Xóa file đã chọn
            this.value = "";

            // Giữ ảnh cũ
            if (anhCu) preview.src = anhCu;

            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            message.innerHTML = "Ảnh không được lớn hơn 5MB.";
            message.className = "upload-error";
            this.value = "";
            if (anhCu) preview.src = anhCu;
            return;
        }


        // =========================
        // FILE ĐÚNG
        // =========================

        message.innerHTML = "Ảnh hợp lệ, có thể tải lên";

        message.className = "upload-success";

        // Hiển thị ảnh mới
        preview.style.display = "block";
        preview.src = URL.createObjectURL(file);
    });
</script>

</html>
