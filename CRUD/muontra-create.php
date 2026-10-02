<?php
// File: CRUD/muontra-create.php — TẠO PHIẾU MƯỢN (chỉ admin).

// Kiểm tra đăng nhập + quyền admin
require_once __DIR__ . '/../Login/auth.php';
requireAdmin();

// Nối database
require_once __DIR__ . '/../config/database.php';

$idSach = '';
$idDocGia = '';
$ngayMuonForm = date('Y-m-d');
$ngayTraForm = '';
$loi = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $idSach = $_POST['id_sach'] ?? '';
    $idDocGia = $_POST['id_doc_gia'] ?? '';
    $ngayMuon = $_POST['ngay_muon'] ?? '';
    $ngayTra = $_POST['ngay_tra'] ?? '';
    $ngayMuonForm = $ngayMuon;
    $ngayTraForm = $ngayTra;
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

        // Ngày trả không được trước ngày mượn
        $loi = "Ngày trả không được nhỏ hơn ngày mượn!";
    } elseif (!empty($ngayTra) && $ngayTra > $homNay) {
        $loi = "Ngày trả không được ở tương lai.";
    } else {

        // Sách / độc giả có tồn tại không (tránh lỗi FK trắng trang)
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
        // Cùng sách + cùng độc giả + chưa trả (ngay_tra NULL)
        // ==========================================

        $checkSql = "
            SELECT COUNT(*)
            FROM muon_tra
            WHERE id_sach = :id_sach
              AND id_doc_gia = :id_doc_gia
              AND ngay_tra IS NULL
        ";

        $checkResult = $pdo->prepare($checkSql);

        $checkResult->execute([
            'id_sach' => $idSach,
            'id_doc_gia' => $idDocGia
        ]);

        $dangMuon = $checkResult->fetchColumn();

        // Nếu độc giả đang mượn sách này
        if ($dangMuon > 0) {

            $loi = "Độc giả này đang mượn sách này, chưa thể tạo phiếu mới!";
        } else {

            // HẾT SÁCH? (chỉ chặn khi phiếu mới là mượn chưa trả)
            if (empty($ngayTra)) {
                $demMuon = $pdo->prepare("SELECT COUNT(*) FROM muon_tra WHERE id_sach = :id AND ngay_tra IS NULL");
                $demMuon->execute(['id' => $idSach]);
                $conLai = (int)$soLuongSach - (int)$demMuon->fetchColumn();
                if ($conLai <= 0) {
                    $loi = "Sách này đã hết (còn 0 bản), không thể cho mượn thêm.";
                }
            }

            if ($loi === "") {
            // ==========================================
            // THÊM PHIẾU MƯỢN
            // Trạng thái TỰ SUY từ ngày trả:
            //   có ngày trả = Đã trả, chưa có = Đang mượn
            // ==========================================

            if (!empty($ngayTra)) {
                $trangThai = 'Đã trả';
            } else {
                $trangThai = 'Đang mượn';
            }

            $sql = "
                INSERT INTO muon_tra
                (
                    id_sach,
                    id_doc_gia,
                    ngay_muon,
                    ngay_tra,
                    trang_thai
                )
                VALUES
                (
                    :id_sach,
                    :id_doc_gia,
                    :ngay_muon,
                    :ngay_tra,
                    :trang_thai
                )
            ";

            $result = $pdo->prepare($sql);

            $check = $result->execute([
                'id_sach' => $idSach,
                'id_doc_gia' => $idDocGia,
                'ngay_muon' => $ngayMuon,
                'ngay_tra' => !empty($ngayTra) ? $ngayTra : null,
                'trang_thai' => $trangThai
            ]);

            if ($check) {
                flash('success', 'Tạo phiếu mượn thành công!');
                header("Location: ../muontra.php");
                exit();
            } else {
                $loi = "Tạo phiếu thất bại, vui lòng thử lại.";
            }
            }
        }
        }
    }
}


// ==========================================
// Lấy danh sách sách
// ==========================================

$sqlSach = "
    SELECT q.*,
           (SELECT COUNT(*) FROM muon_tra m WHERE m.id_sach = q.id_sach AND m.ngay_tra IS NULL) AS dang_muon
    FROM quyensach q
    ORDER BY id_sach DESC
";

$stmtSach = $pdo->prepare($sqlSach);
$stmtSach->execute();

$danhSachSach = $stmtSach->fetchAll(PDO::FETCH_ASSOC);


// ==========================================
// Lấy danh sách độc giả
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

    <title>Tạo phiếu mượn</title>

    <link rel="stylesheet" href="../css/style.css?v=7">


    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />


</head>


<body>


    <?php $pageTitle = 'Tạo phiếu mượn'; $activeMenu = 'muontra'; $basePath = '../'; include '../partials/inner-top.php'; ?>


    <div class="form-card">


        <h2>Tạo phiếu mượn</h2>
        <p class="form-sub">Chọn sách, độc giả và thời gian mượn</p>

        <?php if (!empty($loi)): ?>
            <div class="alert alert-error"><?= e($loi) ?></div>
        <?php endif; ?>


        <form
            action=""
            method="POST">


            <!-- SÁCH -->

            <div class="form-group">

                <label for="id_sach">
                    Sách: *
                </label>

                <select name="id_sach" id="id_sach" required>
                    <option value="">-- Chọn sách --</option>

                    <?php foreach ($danhSachSach as $sach): ?>
                        <?php $conLaiOpt = (int)($sach['so_luong'] ?? 1) - (int)($sach['dang_muon'] ?? 0); ?>
                        <option value="<?= $sach['id_sach'] ?>"
                            <?= ($idSach == $sach['id_sach']) ? 'selected' : '' ?>
                            <?= $conLaiOpt <= 0 ? 'disabled' : '' ?>>
                            S<?= str_pad($sach['id_sach'], 3, '0', STR_PAD_LEFT) ?>
                            - <?= e($sach['name']) ?>
                            (còn <?= max(0, $conLaiOpt) ?>/<?= (int)($sach['so_luong'] ?? 1) ?>)
                            <?= $conLaiOpt <= 0 ? '— HẾT' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>

            </div>


            <!-- ĐỘC GIẢ -->

            <div class="form-group">

                <label for="id_doc_gia">
                    Độc giả: *
                </label>

                <select name="id_doc_gia" id="id_doc_gia" required>
                    <option value="">-- Chọn độc giả --</option>

                    <?php foreach ($danhSachDocGia as $docGia): ?>
                        <option value="<?= $docGia['id_doc_gia'] ?>"
                            <?= ($idDocGia == $docGia['id_doc_gia']) ? 'selected' : '' ?>>
                            DG<?= str_pad($docGia['id_doc_gia'], 3, '0', STR_PAD_LEFT) ?>
                            - <?= e($docGia['ho_ten']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

            </div>


            <!-- NGÀY MƯỢN -->

            <div class="form-group">

                <label for="ngay_muon">
                    Ngày mượn: *
                </label>

                <input
                    type="date"
                    id="ngay_muon"
                    name="ngay_muon"
                    value="<?= e($ngayMuonForm) ?>"
                    max="<?= date('Y-m-d') ?>"
                    required>

            </div>


            <!-- NGÀY TRẢ -->

            <div class="form-group">

                <label for="ngay_tra">
                    Ngày trả:
                </label>

                <input
                    type="date"
                    id="ngay_tra"
                    name="ngay_tra"
                    value="<?= e($ngayTraForm) ?>"
                    max="<?= date('Y-m-d') ?>">

                <small>
                    Có thể để trống nếu sách chưa được trả.
                </small>

            </div>


            <!-- TRẠNG THÁI (tự tính, không cần chọn) -->

            <div class="form-group">

                <label>
                    Trạng thái:
                </label>

                <small>
                    Để trống ngày trả = <b>Đang mượn</b>.
                    Có ngày trả = <b>Đã trả</b>.
                    Quá 7 ngày chưa trả sẽ tự hiện <b>Quá hạn</b>.
                </small>

            </div>


            <!-- BUTTON -->

            <div class="form-buttons">

                <button type="submit"><i class="fas fa-save"></i> Lưu</button>
                <a href="../muontra.php" class="return"><i class="fas fa-times"></i> Hủy</a>

            </div>


        </form>

    </div>


    <script>
        // Ngày trả nhỏ nhất = ngày mượn (PHP vẫn kiểm tra lại khi gửi)
        var oMuon = document.getElementById("ngay_muon");
        var oTra = document.getElementById("ngay_tra");

        oMuon.addEventListener("change", function () {
            oTra.min = oMuon.value;
        });
        if (oMuon.value) {
            oTra.min = oMuon.value;
        }
    </script>


    <?php include '../partials/inner-bottom.php'; ?>
</body>

</html>