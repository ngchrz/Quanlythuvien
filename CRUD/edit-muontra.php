<?php
// File: CRUD/edit-muontra.php — SỬA PHIẾU MƯỢN (chỉ admin).

// Kiểm tra đăng nhập + quyền admin
require_once __DIR__ . '/../Login/auth.php';
requireAdmin();

// Nối database
require_once __DIR__ . '/../config/database.php';

$id = $_GET['id'] ?? '';

if (empty($id)) {
    header("Location: ../muontra.php");
    exit();
}

$loi = "";


// ==========================================
// LẤY PHIẾU MƯỢN HIỆN TẠI
// ==========================================

$sql = "
    SELECT *
    FROM muon_tra
    WHERE id_muon_tra = :id
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    'id' => $id
]);

$muon = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$muon) {
    die("Không tìm thấy phiếu mượn!");
}


// ==========================================
// GIÁ TRỊ BAN ĐẦU
// ==========================================

$idSach = $muon['id_sach'];
$idDocGia = $muon['id_doc_gia'];
$ngayMuon = $muon['ngay_muon'];
$ngayTra = $muon['ngay_tra'];


// ==========================================
// XỬ LÝ FORM
// (Trạng thái TỰ SUY từ ngày trả: có ngày trả = Đã trả)
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $idSach = $_POST['id_sach'] ?? '';
    $idDocGia = $_POST['id_doc_gia'] ?? '';
    $ngayMuon = $_POST['ngay_muon'] ?? '';
    $ngayTra = $_POST['ngay_tra'] ?? '';
    $homNay = date('Y-m-d');


    // ==========================================
    // KIỂM TRA DỮ LIỆU
    // ==========================================

    if (empty($idSach) || empty($idDocGia) || empty($ngayMuon)) {

        $loi = "Vui lòng nhập đầy đủ thông tin!";
    } elseif (!ctype_digit((string)$idSach) || !ctype_digit((string)$idDocGia)) {
        $loi = "Mã sách / độc giả không hợp lệ.";
    } elseif (strtotime($ngayMuon) === false) {
        $loi = "Ngày mượn chưa đúng định dạng.";
    } elseif ($ngayMuon > $homNay) {
        $loi = "Ngày mượn không được ở tương lai.";
    } elseif (!empty($ngayTra) && (strtotime($ngayTra) === false || $ngayTra < $ngayMuon)) {

        $loi = "Ngày trả không được nhỏ hơn ngày mượn!";
    } elseif (!empty($ngayTra) && $ngayTra > $homNay) {
        $loi = "Ngày trả không được ở tương lai.";
    } else {

        $sachTonTai = $pdo->prepare("SELECT so_luong FROM quyensach WHERE id_sach = :id");
        $sachTonTai->execute(['id' => $idSach]);
        $soLuongSach = $sachTonTai->fetchColumn();
        $dgTonTai = $pdo->prepare("SELECT COUNT(*) FROM doc_gia WHERE id_doc_gia = :id");
        $dgTonTai->execute(['id' => $idDocGia]);
        if ($soLuongSach === false) {
            $loi = "Sách không tồn tại.";
        } elseif ($dgTonTai->fetchColumn() == 0) {
            $loi = "Độc giả không tồn tại.";
        } else {

        // ==========================================
        // KIỂM TRA ĐỘC GIẢ ĐANG MƯỢN SÁCH
        // Cùng sách + cùng độc giả + chưa trả, không tính phiếu này
        // ==========================================

        $checkSql = "
            SELECT COUNT(*)
            FROM muon_tra
            WHERE id_sach = :id_sach
              AND id_doc_gia = :id_doc_gia
              AND ngay_tra IS NULL
              AND id_muon_tra != :id_muon_tra
        ";

        $checkResult = $pdo->prepare($checkSql);

        $checkResult->execute([
            'id_sach' => $idSach,
            'id_doc_gia' => $idDocGia,
            'id_muon_tra' => $id
        ]);

        $dangMuon = $checkResult->fetchColumn();


        if ($dangMuon > 0) {

            $loi = "Độc giả này đang mượn sách này, chưa thể cập nhật!";
        } else {

            // Hết sách? (không tính chính phiếu đang sửa, chỉ chặn khi lưu thành chưa trả)
            if (empty($ngayTra)) {
                $demMuon = $pdo->prepare("SELECT COUNT(*) FROM muon_tra WHERE id_sach = :id AND ngay_tra IS NULL AND id_muon_tra != :mid");
                $demMuon->execute(['id' => $idSach, 'mid' => $id]);
                $conLai = (int)$soLuongSach - (int)$demMuon->fetchColumn();
                if ($conLai <= 0) {
                    $loi = "Sách này đã hết (còn 0 bản), không thể chuyển phiếu sang mượn chưa trả.";
                }
            }

            if ($loi === "") {
            // ==========================================
            // CẬP NHẬT PHIẾU
            // ==========================================

            if (!empty($ngayTra)) {
                $trangThai = 'Đã trả';
            } else {
                $trangThai = 'Đang mượn';
            }

            $updateSql = "
                UPDATE muon_tra
                SET
                    id_sach = :id_sach,
                    id_doc_gia = :id_doc_gia,
                    ngay_muon = :ngay_muon,
                    ngay_tra = :ngay_tra,
                    trang_thai = :trang_thai
                WHERE id_muon_tra = :id_muon_tra
            ";

            $update = $pdo->prepare($updateSql);

            $update->execute([
                'id_sach' => $idSach,
                'id_doc_gia' => $idDocGia,
                'ngay_muon' => $ngayMuon,
                'ngay_tra' => !empty($ngayTra) ? $ngayTra : null,
                'trang_thai' => $trangThai,
                'id_muon_tra' => $id
            ]);

            flash('success', 'Cập nhật phiếu mượn thành công!');
            header("Location: ../muontra.php");
            exit();
            }
        }
        }
    }
}


// ==========================================
// LẤY DANH SÁCH SÁCH
// ==========================================

$sqlSach = "
    SELECT *
    FROM quyensach
    ORDER BY id_sach DESC
";

$stmtSach = $pdo->prepare($sqlSach);
$stmtSach->execute();

$danhSachSach = $stmtSach->fetchAll(PDO::FETCH_ASSOC);


// ==========================================
// LẤY DANH SÁCH ĐỘC GIẢ
// ==========================================

$sqlDocGia = "
    SELECT *
    FROM doc_gia
    ORDER BY id_doc_gia DESC
";

$stmtDocGia = $pdo->prepare($sqlDocGia);
$stmtDocGia->execute();

$danhSachDocGia = $stmtDocGia->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sửa phiếu mượn</title>

    <link rel="stylesheet" href="../css/style.css?v=7">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

    <?php $pageTitle = 'Sửa phiếu mượn'; $activeMenu = 'muontra'; $basePath = '../'; include '../partials/inner-top.php'; ?>

    <div class="form-card">

        <h2>Sửa phiếu mượn (MT<?= str_pad($id, 3, '0', STR_PAD_LEFT) ?>)</h2>
        <p class="form-sub">Đổi sách, độc giả hoặc ngày mượn/trả — trạng thái tự tính</p>

        <?php if (!empty($loi)): ?>
            <div class="alert alert-error"><?= e($loi) ?></div>
        <?php endif; ?>


        <form method="POST">


            <!-- ==============================
             SÁCH
             ============================== -->

            <div class="form-group">
                <label for="id_sach">Sách: *</label>

                <select name="id_sach" id="id_sach" required>
                    <option value="">-- Chọn sách --</option>

                    <?php foreach ($danhSachSach as $sach): ?>
                        <option
                            value="<?= $sach['id_sach'] ?>"
                            <?= ($idSach == $sach['id_sach']) ? 'selected' : '' ?>>
                            S<?= str_pad($sach['id_sach'], 3, '0', STR_PAD_LEFT) ?>
                            - <?= e($sach['name']) ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </div>


            <!-- ==============================
             ĐỘC GIẢ
             ============================== -->

            <div class="form-group">
                <label for="id_doc_gia">Độc giả: *</label>

                <select name="id_doc_gia" id="id_doc_gia" required>
                    <option value="">-- Chọn độc giả --</option>

                    <?php foreach ($danhSachDocGia as $docGia): ?>
                        <option
                            value="<?= $docGia['id_doc_gia'] ?>"
                            <?= ($idDocGia == $docGia['id_doc_gia']) ? 'selected' : '' ?>>
                            DG<?= str_pad($docGia['id_doc_gia'], 3, '0', STR_PAD_LEFT) ?>
                            - <?= e($docGia['ho_ten']) ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </div>

            <!-- ==============================
             NGÀY MƯỢN
             ============================== -->

            <div class="form-group">

                <label for="ngay_muon">
                    Ngày mượn: *
                </label>

                <input
                    type="date"
                    id="ngay_muon"
                    name="ngay_muon"
                    value="<?= e($ngayMuon) ?>"
                    max="<?= date('Y-m-d') ?>"
                    required>

            </div>


            <!-- ==============================
             NGÀY TRẢ
             ============================== -->

            <div class="form-group">

                <label for="ngay_tra">
                    Ngày trả:
                </label>

                <input
                    type="date"
                    id="ngay_tra"
                    name="ngay_tra"
                    value="<?= e($ngayTra ?? '') ?>"
                    max="<?= date('Y-m-d') ?>">

                <small>
                    Có thể để trống nếu sách chưa được trả.
                </small>

            </div>


            <!-- ==============================
             TRẠNG THÁI (tự tính theo ngày trả, không cần chọn)
             ============================== -->

            <div class="form-group">

                <label>
                    Trạng thái:
                </label>

                <small>
                    Có ngày trả = <b>Đã trả</b>.
                    Chưa trả = <b>Đang mượn</b> (quá 7 ngày tự hiện <b>Quá hạn</b>).
                </small>

            </div>


            <!-- ==============================
             NÚT
             ============================== -->

            <div class="form-buttons">

                <button type="submit"><i class="fas fa-save"></i> Lưu thay đổi</button>
                <a href="../muontra.php" class="return"><i class="fas fa-times"></i> Hủy</a>

            </div>

        </form>

    </div>


    <?php include '../partials/inner-bottom.php'; ?>
    <script>
        // Ngày trả nhỏ nhất = ngày mượn (giống trang Tạo phiếu — PHP kiểm tra lại khi gửi)
        var oMuon = document.getElementById("ngay_muon");
        var oTra = document.getElementById("ngay_tra");
        if (oMuon && oTra) {
            oMuon.addEventListener("change", function () { oTra.min = oMuon.value; });
            if (oMuon.value) oTra.min = oMuon.value;
        }
    </script>

</body>

</html>