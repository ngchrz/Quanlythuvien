<?php
// File: CRUD/delete.php — XÓA SÁCH (chỉ admin).
// Sách đang được mượn thì không cho xóa.

// Kiểm tra đăng nhập + quyền admin
require_once __DIR__ . '/../Login/auth.php';
requireAdmin();

// Nối database
require_once __DIR__ . '/../config/database.php';

// Lấy id sách (ép số nguyên, chống id rác)
$idSach = (int) ($_GET["id"] ?? 0);
if ($idSach <= 0) {
    flash('error', 'Mã sách không hợp lệ.');
    header('Location: ../product.php');
    exit();
}

// 0. Chặn xóa sách đang có người mượn (chưa trả sách)
$checkMuon = $pdo->prepare(
    "SELECT COUNT(*) FROM muon_tra WHERE id_sach = :id AND ngay_tra IS NULL"
);
$checkMuon->execute(["id" => $idSach]);
if ($checkMuon->fetchColumn() > 0) {
    flash('warning', 'Không thể xóa: sách này đang được mượn!');
    header('Location: ../product.php');
    exit();
}

// 0b. Chặn xóa sách đã có lịch sử mượn (kể cả đã trả)
// Lý do: bảng muon_tra đang khóa ngoại tới quyensach (RESTRICT),
// xóa sẽ gây lỗi PDO. Giữ lịch sử giống như cách xóa độc giả.
$checkSu = $pdo->prepare("SELECT COUNT(*) FROM muon_tra WHERE id_sach = :id");
$checkSu->execute(["id" => $idSach]);
if ($checkSu->fetchColumn() > 0) {
    flash('warning', 'Không thể xóa sách vì còn lịch sử mượn trả. Hãy xóa phiếu mượn liên quan trước.');
    header('Location: ../product.php');
    exit();
}

// 1. Lấy tên ảnh của sách trước khi xóa
$sql = "SELECT img FROM quyensach WHERE id_sach = :IDSach";

$stm = $pdo->prepare($sql);
$stm->execute(["IDSach" => $idSach]);

$sach = $stm->fetch();

if ($sach) {

    // 2. Xóa sách trong database TRƯỚC (bọc try-catch phòng FK)
    try {
        $sql = "DELETE FROM quyensach WHERE id_sach = :IDSach";

        $stm = $pdo->prepare($sql);

        $check = $stm->execute(["IDSach" => $idSach]);
    } catch (PDOException $e) {
        flash('error', 'Không thể xóa: sách này còn ràng buộc mượn trả.');
        header("Location: ../product.php");
        exit();
    }

    // 3. Xóa DB xong mới xóa ảnh (tránh mất ảnh khi xóa DB thất bại)
    if ($check) {
        if (!empty($sach["img"])) {

            $duongDanAnh = "../img/product/" . basename($sach["img"]);

            if (file_exists($duongDanAnh)) {
                unlink($duongDanAnh);
            }
        }
        flash('success', 'Xóa sách thành công!');
        header("Location: ../product.php");
        exit();
    } else {
        flash('error', 'Xóa dữ liệu không thành công.');
        header("Location: ../product.php");
        exit();
    }

} else {
    flash('error', 'Không tìm thấy sách.');
    header("Location: ../product.php");
    exit();
}
?>
