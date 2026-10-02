<?php

$loiToan = "";
$loiLy = "";
$loiHoa = "";
$dtb = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $toan = $_POST['toan'];
    $ly = $_POST['ly'];
    $hoa = $_POST['hoa'];

    function diemTB($diem1, $diem2, $diem3): float
    {
        return ($diem1 + $diem2 + $diem3) / 3;
    }

    // Kiểm tra Toán
    if (!is_numeric($toan)) {
        $loiToan = "Điểm phải là số!";
    } elseif ($toan < 0 || $toan > 10) {
        $loiToan = "Điểm phải trong khoảng 0-10!";
    }

    // Kiểm tra Lý
    if (!is_numeric($ly)) {
        $loiLy = "Điểm phải là số!";
    } elseif ($ly < 0 || $ly > 10) {
        $loiLy = "Điểm phải trong khoảng 0-10!";
    }

    // Kiểm tra Hóa
    if (!is_numeric($hoa)) {
        $loiHoa = "Điểm phải là số!";
    } elseif ($hoa < 0 || $hoa > 10) {
        $loiHoa = "Điểm phải trong khoảng 0-10!";
    }

    // Nếu không có lỗi thì tính
    if ($loiToan == "" && $loiLy == "" && $loiHoa == "") {
        $dtb = diemTB($toan, $ly, $hoa);
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Tính điểm trung bình</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">
</head>

<body>

    <div class="container">

        <h2>Tính điểm trung bình</h2>

        <form method="POST">

            <label>Điểm Toán</label>

            <input
                type="text"
                class="form-control"
                name="toan"
                value="<?php echo $_POST['toan'] ?? ''; ?>">

            <?php if ($loiToan != ""): ?>
                <div class="error">
                    ⚠ <?php echo $loiToan; ?>
                </div>
            <?php endif; ?>

            <br>


            <label>Điểm Lý</label>

            <input
                type="text"
                class="form-control"
                name="ly"
                value="<?php echo $_POST['ly'] ?? ''; ?>">

            <?php if ($loiLy != ""): ?>
                <div class="error">
                    ⚠ <?php echo $loiLy; ?>
                </div>
            <?php endif; ?>

            <br>


            <label>Điểm Hóa</label>

            <input
                type="text"
                class="form-control"
                name="hoa"
                value="<?php echo $_POST['hoa'] ?? ''; ?>">

            <?php if ($loiHoa != ""): ?>
                <div class="error">
                    ⚠ <?php echo $loiHoa; ?>
                </div>
            <?php endif; ?>

            <br>

            <input
                type="submit"
                class="btn btn-primary"
                value="GO">

        </form>


        <?php if ($dtb !== ""): ?>

            <h4 class="result">
                Điểm trung bình:
                <?php echo number_format($dtb, 2); ?>
            </h4>

        <?php endif; ?>

    </div>

</body>

</html>