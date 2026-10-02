<?php
// File: Login/register.php — Trang ĐĂNG KÝ.
// Bắt buộc nhập đủ 8 trường: tài khoản, mật khẩu, nhập lại,
// họ tên, ngày sinh, giới tính, số điện thoại, địa chỉ.
// Đăng ký xong thì TỰ ĐỘNG có hồ sơ độc giả đầy đủ (không cần thủ thư làm tay).
// Dùng transaction: lỗi bước nào thì hủy hết, không để sót dữ liệu.

// 1. Mở session + nối database
session_start();
require_once __DIR__ . '/../config/database.php';

// 2. Ai đăng nhập rồi thì cho vào thẳng trang của mình
if (!empty($_SESSION['id_taikhoan'])) {
    if (($_SESSION['role'] ?? 'user') === 'admin') {
        header('Location: ../index.php');
    } else {
        header('Location: ../User/index.php');
    }
    exit();
}

// 3. Giữ lại chữ đã gõ để hiện lại khi báo lỗi
$error = '';
$usernameGo = trim($_POST['username'] ?? '');
$hoTen = trim($_POST['ho_ten'] ?? '');
$ngaySinh = $_POST['ngay_sinh'] ?? '';
$gioiTinh = $_POST['gioi_tinh'] ?? '';
$soDienThoai = trim($_POST['so_dien_thoai'] ?? '');
$diaChi = trim($_POST['dia_chi'] ?? '');
$email = trim($_POST['email'] ?? '');

// 4. Người dùng bấm nút Đăng ký
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $rePassword = $_POST['re_password'] ?? '';

    // Đưa username về chữ thường (MinhPhuc thành minhphuc)
    $username = normalizeUsername($usernameGo);

    // Kiểm tra từng trường cho rõ ràng
    if ($username == '') {
        $error = 'Vui lòng nhập tên tài khoản.';
    } elseif (strlen($username) < 3) {
        $error = 'Tên tài khoản phải từ 3 ký tự trở lên.';
    } elseif (isValidUsername($username) == false) {
        $error = 'Tên tài khoản chỉ được chứa chữ cái không dấu và số, không chứa khoảng trắng hoặc ký tự đặc biệt.';
    } elseif ($password == '') {
        $error = 'Vui lòng nhập mật khẩu.';
    } elseif (strlen($password) < 4) {
        $error = 'Mật khẩu phải từ 4 ký tự trở lên.';
    } elseif (strtolower($password) == $username) {
        $error = 'Mật khẩu không được trùng với tên tài khoản.';
    } elseif ($rePassword == '') {
        $error = 'Vui lòng nhập lại mật khẩu.';
    } elseif ($password != $rePassword) {
        $error = 'Nhập lại mật khẩu chưa khớp.';
    } elseif ($hoTen == '') {
        $error = 'Vui lòng nhập họ và tên.';
    } elseif ($ngaySinh == '') {
        $error = 'Vui lòng chọn ngày sinh.';
    } elseif ($gioiTinh == '') {
        $error = 'Vui lòng chọn giới tính.';
    } elseif ($soDienThoai == '') {
        $error = 'Vui lòng nhập số điện thoại.';
    } elseif (preg_match('/^[0-9]+$/', $soDienThoai) == false) {
        $error = 'Số điện thoại chỉ được gồm chữ số.';
    } elseif ($diaChi == '') {
        $error = 'Vui lòng nhập địa chỉ.';
    } elseif ($email != '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email chưa đúng định dạng.';
    } else {
        // Kiểm tra tên đã có người dùng chưa (hoa/thường tính là một)
        $check = $pdo->prepare('SELECT COUNT(*) FROM users WHERE LOWER(username) = :username');
        $check->execute(['username' => $username]);
        if ($check->fetchColumn() > 0) {
            $error = 'Tên tài khoản đã tồn tại.';
        } else {
            // Kiểm tra số điện thoại đã có độc giả nào dùng chưa
            // (kiểm tra TRƯỚC khi INSERT để không tạo thừa dữ liệu)
            $checkSdt = $pdo->prepare('SELECT COUNT(*) FROM doc_gia WHERE so_dien_thoai = :sdt');
            $checkSdt->execute(['sdt' => $soDienThoai]);
            if ($checkSdt->fetchColumn() > 0) {
                $error = 'Số điện thoại đã được sử dụng.';
            } else {
            // Bắt đầu transaction
            $pdo->beginTransaction();
            try {
                // Bước 1: tạo hồ sơ độc giả ĐẦY ĐỦ thông tin
                $stmtDg = $pdo->prepare(
                    'INSERT INTO doc_gia (ho_ten, ngay_sinh, gioi_tinh, so_dien_thoai, dia_chi)
                     VALUES (:ho_ten, :ngay_sinh, :gioi_tinh, :so_dien_thoai, :dia_chi)'
                );
                $stmtDg->execute([
                    'ho_ten'        => $hoTen,
                    'ngay_sinh'     => $ngaySinh,
                    'gioi_tinh'     => $gioiTinh,
                    'so_dien_thoai' => $soDienThoai,
                    'dia_chi'       => $diaChi,
                ]);

                // Bước 2: lấy id độc giả vừa tạo
                $idDocGia = (int) $pdo->lastInsertId();

                // Bước 3: tạo tài khoản gắn sẵn id độc giả (role luôn là user)
                $stmtUser = $pdo->prepare(
                    "INSERT INTO users (ho_ten, username, email, password, role, id_doc_gia)
                     VALUES (:ho_ten, :username, :email, :password, 'user', :id_doc_gia)"
                );
                $stmtUser->execute([
                    'ho_ten'     => $hoTen,
                    'username'   => $username,
                    'email'      => $email != '' ? $email : null,
                    'password'   => password_hash($password, PASSWORD_DEFAULT),
                    'id_doc_gia' => $idDocGia,
                ]);

                // Xong hết thì lưu lại
                $pdo->commit();
                header('Location: login.php?registered=1');
                exit();
            } catch (Exception $e) {
                // Lỗi thì hủy hết (không để sót hồ sơ độc giả)
                $pdo->rollBack();
                $error = 'Đăng ký thất bại, vui lòng thử lại.';
            }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký — Quản lý thư viện</title>
    <link rel="stylesheet" href="../css/style.css?v=7">
    <link rel="stylesheet" href="../css/auth.css?v=6">
</head>
<body class="auth-body">
    <div class="auth-wrap wide">
        <!-- Logo và tên thư viện -->
        <div class="auth-brand-top">
            <div class="logo-big">📝</div>
            <b>THƯ VIỆN BÁCH KHOA</b>
            <p>Tạo tài khoản độc giả mới</p>
        </div>
        <!-- Form đăng ký -->
        <div class="auth-card">
            <h2>Tạo tài khoản</h2>
            <p class="auth-sub">Điền đầy đủ thông tin để tạo hồ sơ độc giả</p>

            <div class="auth-note">ℹ️ Tên tài khoản được kiểm tra trực tiếp: đúng định dạng và chưa ai dùng thì mới đăng ký được.</div>

        <!-- Báo lỗi -->
        <?php if ($error != ''): ?>
            <div class="alert alert-error">✕ <?= e($error) ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST" id="registerForm" novalidate>
            <div class="form-group">
                <label for="username">Tên tài khoản *</label>
                <input type="text" id="username" name="username" placeholder="vidu: minhphuc123"
                       value="<?= e($usernameGo) ?>" required autocomplete="off">
                <div class="field-hint" id="usernameHint">✓ Chỉ sử dụng chữ cái không dấu và số</div>
            </div>
            <div class="auth-grid2">
                <div class="form-group">
                    <label for="password">Mật khẩu *</label>
                    <div class="pass-wrap">
                        <input type="password" id="password" name="password" placeholder="Tối thiểu 4 ký tự" required>
                        <button type="button" class="pass-toggle" data-target="password" title="Hiện / ẩn mật khẩu">👁</button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="re_password">Nhập lại mật khẩu *</label>
                    <div class="pass-wrap">
                        <input type="password" id="re_password" name="re_password" placeholder="Nhập lại mật khẩu" required>
                        <button type="button" class="pass-toggle" data-target="re_password" title="Hiện / ẩn mật khẩu">👁</button>
                    </div>
                    <div class="field-hint" id="passHint"></div>
                </div>
            </div>

            <hr class="auth-hr">

            <div class="form-group">
                <label for="ho_ten">Họ và tên *</label>
                <input type="text" id="ho_ten" name="ho_ten" placeholder="Nguyễn Văn A"
                       value="<?= e($hoTen) ?>" required>
            </div>
            <div class="auth-grid2">
                <div class="form-group">
                    <label for="ngay_sinh">Ngày sinh *</label>
                    <input type="date" id="ngay_sinh" name="ngay_sinh"
                           value="<?= e($ngaySinh) ?>" required>
                </div>
                <div class="form-group">
                    <label for="gioi_tinh">Giới tính *</label>
                    <select id="gioi_tinh" name="gioi_tinh" required>
                        <option value="">— Chọn giới tính —</option>
                        <option value="Nam" <?= $gioiTinh === 'Nam' ? 'selected' : '' ?>>Nam</option>
                        <option value="Nữ" <?= $gioiTinh === 'Nữ' ? 'selected' : '' ?>>Nữ</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="so_dien_thoai">Số điện thoại *</label>
                <input type="tel" id="so_dien_thoai" name="so_dien_thoai" placeholder="09xxxxxxxx"
                       value="<?= e($soDienThoai) ?>" required>
            </div>
            <div class="form-group">
                <label for="dia_chi">Địa chỉ *</label>
                <input type="text" id="dia_chi" name="dia_chi" placeholder="Hà Nội"
                       value="<?= e($diaChi) ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email (không bắt buộc)</label>
                <input type="email" id="email" name="email" placeholder="vidu@gmail.com"
                       value="<?= e($email) ?>">
            </div>

            <button type="submit">Đăng ký</button>
        </form>

        <p class="auth-link">Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
        </div>
    </div>

<script>
// Nút con mắt để hiện / ẩn mật khẩu
document.querySelectorAll('.pass-toggle').forEach(function (nut) {
    nut.addEventListener('click', function () {
        var oNhap = document.getElementById(nut.getAttribute('data-target'));
        if (oNhap.type === 'password') {
            oNhap.type = 'text';
            nut.textContent = '🙈';
        } else {
            oNhap.type = 'password';
            nut.textContent = '👁';
        }
    });
});

// Kiểm tra tên tài khoản NGAY KHI GÕ:
// Bước 1: kiểm tra định dạng bằng JavaScript
// Bước 2: hỏi server (AJAX + PHP + database) xem tên đã dùng chưa
var oUser = document.getElementById('username');
var goiYUser = document.getElementById('usernameHint');
var tenBiTrung = false; // true = tên đã có người dùng, không cho gửi form
var henGio = null;      // hẹn giờ để gõ xong mới hỏi server

// Hiện gợi ý dưới ô nhập (loai: '' / ok / bad / check)
function hienGoiY(loai, chu, vien) {
    goiYUser.textContent = chu;
    goiYUser.className = 'field-hint ' + loai;
    oUser.classList.remove('input-ok', 'input-bad');
    if (vien != '') {
        oUser.classList.add(vien);
    }
}

function kiemTen() {
    var ten = oUser.value.trim();
    tenBiTrung = false;
    if (henGio != null) {
        clearTimeout(henGio);
    }
    if (ten === '') {
        hienGoiY('', '✓ Chỉ sử dụng chữ cái không dấu và số', '');
        return true;
    }
    if (ten.length < 3) {
        hienGoiY('bad', '❌ Tên tài khoản phải từ 3 ký tự trở lên.', 'input-bad');
        return false;
    }
    if (/^[a-zA-Z0-9]+$/.test(ten) == false) {
        hienGoiY('bad', '❌ Tên tài khoản chỉ được chứa chữ cái không dấu và số.', 'input-bad');
        return false;
    }
    // Định dạng đúng rồi thì hỏi server xem tên đã dùng chưa
    hienGoiY('check', '⏳ Đang kiểm tra tên tài khoản...', '');
    henGio = setTimeout(function () {
        fetch('check-username.php?username=' + encodeURIComponent(ten))
            .then(function (traLoi) { return traLoi.json(); })
            .then(function (data) {
                // Người dùng gõ tiếp rồi thì bỏ kết quả cũ
                if (oUser.value.trim() !== ten) {
                    return;
                }
                if (data.ton_tai) {
                    tenBiTrung = true;
                    hienGoiY('bad', '✕ Tên tài khoản đã được sử dụng', 'input-bad');
                } else {
                    hienGoiY('ok', '✓ Tên tài khoản có thể sử dụng', 'input-ok');
                }
            })
            .catch(function () {
                hienGoiY('', '✓ Định dạng hợp lệ (chưa kiểm tra trùng được)', '');
            });
    }, 400);
    return true;
}
oUser.addEventListener('input', kiemTen);

// Kiểm tra 2 mật khẩu có giống nhau không
var oMk1 = document.getElementById('password');
var oMk2 = document.getElementById('re_password');
var goiYMk = document.getElementById('passHint');

function kiemMk() {
    goiYMk.className = 'field-hint';
    goiYMk.textContent = '';
    if (oMk2.value === '') {
        return true;
    }
    if (oMk1.value !== oMk2.value) {
        goiYMk.textContent = '❌ Nhập lại mật khẩu chưa khớp.';
        goiYMk.className = 'field-hint bad';
        return false;
    }
    goiYMk.textContent = '✓ Mật khẩu khớp';
    goiYMk.className = 'field-hint ok';
    return true;
}
oMk1.addEventListener('input', kiemMk);
oMk2.addEventListener('input', kiemMk);

// Bấm Đăng ký: sai định dạng, mật khẩu lệch hoặc tên đã dùng thì không cho gửi
document.getElementById('registerForm').addEventListener('submit', function (e) {
    if (kiemTen() == false || kiemMk() == false) {
        e.preventDefault();
        return;
    }
    if (tenBiTrung) {
        e.preventDefault();
        hienGoiY('bad', '✕ Tên tài khoản đã được sử dụng', 'input-bad');
    }
});
</script>
</body>
</html>
