<?php
// File: CRUD/create.php — THÊM SÁCH (chỉ admin).

// Kiểm tra đăng nhập + quyền admin
require_once __DIR__ . '/../Login/auth.php';
requireAdmin();

// Nối database
require_once __DIR__ . '/../config/database.php';

// Giữ lại giá trị đã gõ khi báo lỗi
$tenSach = trim($_POST["TenSach"] ?? "");
$tacGia = trim($_POST["TacGia"] ?? "");
$theLoai = trim($_POST["TheLoai"] ?? "");
$soLuong = $_POST["SoLuong"] ?? "1";
$loi = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenSach = trim($_POST["TenSach"] ?? "");
    $tacGia = trim($_POST["TacGia"] ?? "");
    $theLoai = trim($_POST["TheLoai"] ?? "");
    $soLuong = trim((string)($_POST["SoLuong"] ?? "1"));
    $hinhAnh = "";

    // 1. Kiểm tra bắt buộc
    if ($tenSach === "" || $tacGia === "" || $theLoai === "") {
        $loi = "Vui lòng nhập đầy đủ tên sách, tác giả, thể loại.";
    } elseif (mb_strlen($tenSach) > 255 || mb_strlen($tacGia) > 255 || mb_strlen($theLoai) > 255) {
        $loi = "Tên sách, tác giả, thể loại không được quá 255 ký tự.";
    } elseif (!ctype_digit($soLuong) || (int)$soLuong < 1 || (int)$soLuong > 1000) {
        $loi = "Số lượng phải là số nguyên từ 1 đến 1000.";
    } else {
        // 2. Kiểm tra trùng (cùng tên + cùng tác giả)
        $trung = $pdo->prepare("SELECT COUNT(*) FROM quyensach WHERE name = :n AND tac_gia = :t");
        $trung->execute(["n" => $tenSach, "t" => $tacGia]);
        if ($trung->fetchColumn() > 0) {
            $loi = "Sách này (cùng tên + tác giả) đã tồn tại.";
        }
    }

    // 3. Xử lý ảnh (không bắt buộc — để trống thì lưu rỗng)
    if ($loi === "" && isset($_FILES["HinhAnh"]) && $_FILES["HinhAnh"]["error"] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES["HinhAnh"]["error"] !== UPLOAD_ERR_OK) {
            $loi = "Có lỗi khi tải ảnh lên.";
        } elseif ($_FILES["HinhAnh"]["size"] > 5 * 1024 * 1024) {
            $loi = "Ảnh không được lớn hơn 5MB.";
        } else {
            $fileTam = $_FILES["HinhAnh"]["tmp_name"];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $fileTam);
            finfo_close($finfo);

            $mimeChoPhep = [
                "image/jpeg" => "jpg",
                "image/png"  => "png",
                "image/gif"  => "gif",
                "image/webp" => "webp"
            ];

            if (!isset($mimeChoPhep[$mime])) {
                $loi = "File không phải là ảnh hợp lệ (chỉ JPG, PNG, GIF, WEBP).";
            } else {
                $duoiFile = $mimeChoPhep[$mime];
                $tenMoi = uniqid("book_", false) . "." . $duoiFile;
                $thuMuc = "../img/product/";
                if (!is_dir($thuMuc)) {
                    mkdir($thuMuc, 0777, true);
                }
                if (move_uploaded_file($fileTam, $thuMuc . $tenMoi)) {
                    $hinhAnh = $tenMoi;
                } else {
                    $loi = "Upload ảnh thất bại.";
                }
            }
        }
    }

    // 4. Thêm mới
    if ($loi === "") {
        $sql = "INSERT INTO quyensach (name, img, tac_gia, the_loai, so_luong)
                VALUES (:tenSach, :hinhAnh, :tacGia, :theLoai, :soLuong)";
        $result = $pdo->prepare($sql);
        $check = $result->execute([
            "tenSach" => $tenSach,
            "hinhAnh" => $hinhAnh,
            "tacGia" => $tacGia,
            "theLoai" => $theLoai,
            "soLuong" => (int)$soLuong,
        ]);

        if ($check) {
            flash('success', 'Thêm sách thành công!');
            header("Location: ../product.php");
            exit();
        } else {
            $loi = "Thêm sách thất bại, vui lòng thử lại.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">

    <link rel="stylesheet" href="../css/style.css?v=8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

</head>

<body>

    <?php $pageTitle = 'Thêm sách'; $activeMenu = 'sach'; $basePath = '../'; include '../partials/inner-top.php'; ?>
    <div class="create-page">
        <h2>Thêm sách</h2>
        <p class="form-sub">Nhập thông tin cuốn sách mới</p>

        <?php if ($loi !== ""): ?>
            <div class="alert alert-error"><?= e($loi) ?></div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">

            <div class="boxAll">

                <div class="box-1">

                    <div>
                        <label for="TenSach">Tên Sách: *</label>
                        <input
                            type="text"
                            id="TenSach"
                            name="TenSach"
                            required
                            maxlength="255"
                            value="<?= e($tenSach) ?>">
                    </div>

                    <div>
                        <label for="TacGia">Tác giả: *</label>
                        <input
                            type="text"
                            id="TacGia"
                            name="TacGia"
                            required
                            maxlength="255"
                            value="<?= e($tacGia) ?>">
                    </div>

                    <div>
                        <label for="TheLoai">Thể loại: *</label>
                        <input
                            type="text"
                            id="TheLoai"
                            name="TheLoai"
                            required
                            maxlength="255"
                            value="<?= e($theLoai) ?>">
                    </div>

                    <div>
                        <label for="SoLuong">Số lượng (bản): *</label>
                        <input
                            type="number"
                            id="SoLuong"
                            name="SoLuong"
                            required
                            min="1"
                            max="1000"
                            value="<?= e($soLuong) ?>">
                    </div>

                    <div class="upload-message" id="uploadMessage"></div>

                    <div class="form-buttons">
                        <button type="submit">
                            <span class="btn-icon"><i class="fas fa-save"></i> </span>
                            Lưu sách
                        </button>

                        <a href="../product.php" class="return">
                            <span class="btn-icon"><i class="fas fa-times"></i></span>
                            Hủy
                        </a>
                    </div>



                </div>




                <div class="box-2">

                    <div>
                        <label for="img">Hình ảnh (không bắt buộc):</label>

                        <input
                            type="file"
                            id="img"
                            name="HinhAnh"
                            accept="image/jpeg,image/png,image/gif,image/webp">
                        <small>Để trống nếu chưa có bìa. Tối đa 5MB.</small>
                    </div>

                    <img id="preview" src="" alt="Xem trước">

                </div>

            </div>




        </form>
    </div>
    <?php include '../partials/inner-bottom.php'; ?>
    <script>
        const input = document.getElementById("img");
        const preview = document.getElementById("preview");
        const message = document.getElementById("uploadMessage");

        input.addEventListener("change", function() {

            const file = this.files[0];

            if (!file) {
                preview.src = "";
                preview.style.display = "none";

                message.textContent = "";
                message.className = "upload-message";

                return;
            }

            const allowedTypes = [
                "image/jpeg",
                "image/png",
                "image/gif",
                "image/webp"
            ];

            if (!allowedTypes.includes(file.type)) {

                this.value = "";

                preview.src = "";
                preview.style.display = "none";

                message.innerHTML =
                    "File không hợp lệ! Chỉ chấp nhận JPG, JPEG, PNG, GIF hoặc WEBP.";

                message.className =
                    "upload-message error show";

                return;
            }

            if (file.size > 5 * 1024 * 1024) {
                this.value = "";
                preview.src = "";
                preview.style.display = "none";
                message.innerHTML = "Ảnh không được lớn hơn 5MB.";
                message.className = "upload-message error show";
                return;
            }

            message.innerHTML =
                "Ảnh hợp lệ, có thể tải lên.";

            message.className =
                "upload-message success show";


            const reader = new FileReader();

            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = "block";
            };

            reader.readAsDataURL(file);
        });
    </script>
</body>


</html>
