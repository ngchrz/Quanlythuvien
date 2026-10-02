# HƯỚNG DẪN: ĐĂNG NHẬP – ĐĂNG XUẤT – ĐĂNG KÝ

File này trích toàn bộ code liên quan đến tài khoản trong project,
kèm cách dùng và giải thích từng phần cho sinh viên mới học PHP.

---

## 1. CÁC FILE LIÊN QUAN (nằm ở đâu)

```text
config/database.php      → Nối database (có biến $pdo)
config/helpers.php       → Hàm kiểm tra username
Login/login.php          → Form + xử lý ĐĂNG NHẬP
Login/logout.php         → Xóa session (ĐĂNG XUẤT)
Login/register.php       → Form + xử lý ĐĂNG KÝ
Login/auth.php           → Bảo vệ trang ADMIN
User/auth_user.php       → Bảo vệ trang ĐỘC GIẢ
Login/check-username.php → AJAX kiểm tra tên đã dùng chưa
```

## 2. LUỒNG HOẠT ĐỘNG (nhớ hình này là hiểu)

```text
ĐĂNG KÝ:  Form → kiểm tra từng ô → kiểm tra trùng tên/SĐT
           → INSERT doc_gia → lấy id → INSERT users → sang Login

ĐĂNG NHẬP: Form → tìm user theo tên (không phân biệt hoa/thường)
           → password_verify() → lưu session → admin/user đi 2 cổng

ĐĂNG XUẤT: bấm Đăng xuất → xóa sạch session → về Login

TRANG ADMIN:  include auth.php → chưa login: về Login
                                 phải user: về cổng độc giả
TRANG ĐỘC GIẢ: include auth_user.php → chưa login: về Login
                                         phải admin: về Dashboard
```

## 3. SESSION LƯU GÌ (sau khi đăng nhập thành công)

| Key trong `$_SESSION` | Ví dụ        | Giải thích                        |
|-----------------------|--------------|-----------------------------------|
| `id_taikhoan`         | 5            | id trong bảng `users`             |
| `username`            | phamthuha    | tên đăng nhập (đã chữ thường)     |
| `ho_ten`              | Phạm Thu Hà  | tên để chào trên giao diện        |
| `role`                | user / admin | quyền                             |
| `id_docgia`           | 4            | id trong bảng `doc_gia` (admin thì null) |

---

## 4. FULL CODE: NỐI DATABASE (`config/database.php`)

```php
<?php
// File: config/database.php — Nối tới MySQL bằng PDO.
// Mọi trang đều include file này để có biến $pdo.

$host = 'localhost';
$port = 3306;
$database = 'quanlythuvien';
$username = 'root';
$password = '';

$dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,       // lỗi thì báo rõ
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  // lấy dòng dạng mảng tên cột
    PDO::ATTR_EMULATE_PREPARES => false,               // dùng prepare thật của MySQL
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    exit('Lỗi kết nối: ' . $e->getMessage());
}

// Hàm dùng chung (e(), flash(), kiểm tra username)
require_once __DIR__ . '/helpers.php';

// Logic trạng thái mượn trả dùng chung (tinhTrangThai(), lopBadge())
require_once __DIR__ . '/muon_helper.php';
```

**Giải thích:** chỉ cần `require_once config/database.php` là có ngay biến
`$pdo` để truy vấn. Muốn đổi tên database/user/mật khẩu thì sửa 4 dòng đầu.

---

## 5. FULL CODE: HÀM KIỂM TRA USERNAME (`config/helpers.php` — trích phần tài khoản)

```php
// Đưa username về chữ thường + bỏ khoảng trắng 2 đầu
// Ví dụ: "  MinhPhuc " -> "minhphuc"
function normalizeUsername($username) {
    $username = trim($username);
    $username = strtolower($username);
    return $username;
}

// Kiểm tra username chỉ gồm chữ không dấu và số hay không
function isValidUsername($username) {
    if (preg_match('/^[a-zA-Z0-9]+$/', $username)) {
        return true;
    }
    return false;
}
```

**Giải thích:** mọi chỗ nhập username (đăng ký, đăng nhập, sửa tài khoản)
đều gọi 2 hàm này nên quy tắc chỉ viết 1 lần, không lệch nhau.

---

## 6. FULL CODE: ĐĂNG NHẬP (`Login/login.php` — phần xử lý PHP)

```php
<?php
// 1. Mở session + nối database
session_start();
require_once __DIR__ . '/../config/database.php';

// 2. Ai đăng nhập rồi thì cho vào thẳng trang của mình
if (!empty($_SESSION['id_taikhoan'])) {
    if (($_SESSION['role'] ?? 'user') === 'admin') {
        header('Location: ../index.php');       // admin vào trang quản trị
    } else {
        header('Location: ../User/index.php');  // độc giả vào cổng riêng
    }
    exit();
}

// 3. Nhận dữ liệu từ form
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Đưa username về chữ thường để MinhPhuc = minhphuc
    $username = normalizeUsername($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username == '' || $password == '') {
        $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';
    } else {
        // Tìm tài khoản (LOWER để gõ hoa hay thường đều được)
        $stmt = $pdo->prepare('SELECT * FROM users WHERE LOWER(username) = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        // Sai tên hoặc sai mật khẩu
        if (!$user || !password_verify($password, $user['password'])) {
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
        } else {
            // Đúng thì lưu vào session
            session_regenerate_id(true);
            $_SESSION['id_taikhoan'] = $user['id'];
            $_SESSION['username']    = $user['username'];
            $_SESSION['ho_ten']      = $user['ho_ten'];
            $_SESSION['role']        = $user['role'];
            $_SESSION['id_docgia']   = $user['id_doc_gia'];

            // Admin và độc giả đi 2 cổng khác nhau
            if ($_SESSION['role'] === 'admin') {
                header('Location: ../index.php');
            } else {
                header('Location: ../User/index.php');
            }
            exit();
        }
    }
}
?>
```

**Giải thích:**
- Form HTML chỉ cần 2 ô `name="username"`, `name="password"` gửi POST sang chính nó.
- `password_verify()` so mật khẩu gõ với chuỗi băm trong cột `password`
  (lúc đăng ký đã băm bằng `password_hash()` nên không bao giờ so trực tiếp).
- Không bao giờ báo riêng “sai tên” hay “sai mật khẩu” để người lạ không
  đoán được tài khoản nào tồn tại.

---

## 7. FULL CODE: ĐĂNG XUẤT (`Login/logout.php` — toàn bộ file)

```php
<?php
// Login/logout.php — Xóa session và quay về trang Login.
session_start();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();
header('Location: login.php');
exit();
```

**Giải thích:** xóa hết biến session + xóa cookie + hủy session rồi về Login.
Muốn thêm nút Đăng xuất ở đâu thì chỉ cần link tới `Login/logout.php`.

---

## 8. FULL CODE: ĐĂNG KÝ (`Login/register.php` — phần xử lý PHP)

8 ô bắt buộc: `username`, `password`, `re_password`, `ho_ten`,
`ngay_sinh`, `gioi_tinh`, `so_dien_thoai`, `dia_chi`
(`email` không bắt buộc).

```php
<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Đã đăng nhập rồi thì không cho đăng ký nữa
if (!empty($_SESSION['id_taikhoan'])) {
    header('Location: ../index.php');
    exit();
}

$error = '';
// ... (lấy từng $_POST vào biến, xem full trong file gốc)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = normalizeUsername($usernameGo); // về chữ thường

    // Kiểm tra từng ô, thiếu ô nào báo ô đó
    if ($username == '') {
        $error = 'Vui lòng nhập tên tài khoản.';
    } elseif (strlen($username) < 3) {
        $error = 'Tên tài khoản phải từ 3 ký tự trở lên.';
    } elseif (isValidUsername($username) == false) {
        $error = 'Tên tài khoản chỉ được chứa chữ cái không dấu và số.';
    } elseif ($password == '') {
        $error = 'Vui lòng nhập mật khẩu.';
    } elseif (strlen($password) < 4) {
        $error = 'Mật khẩu phải từ 4 ký tự trở lên.';
    } elseif (strtolower($password) == $username) {
        $error = 'Mật khẩu không được trùng với tên tài khoản.';
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
    } elseif ($diaChi == '') {
        $error = 'Vui lòng nhập địa chỉ.';
    } else {
        // Trùng tên? (hoa/thường tính là một)
        $check = $pdo->prepare('SELECT COUNT(*) FROM users WHERE LOWER(username) = :username');
        $check->execute(['username' => $username]);
        if ($check->fetchColumn() > 0) {
            $error = 'Tên tài khoản đã tồn tại.';
        } else {
            // Trùng số điện thoại với độc giả khác?
            $checkSdt = $pdo->prepare('SELECT COUNT(*) FROM doc_gia WHERE so_dien_thoai = :sdt');
            $checkSdt->execute(['sdt' => $soDienThoai]);
            if ($checkSdt->fetchColumn() > 0) {
                $error = 'Số điện thoại đã được sử dụng.';
            } else {
                // Bắt đầu transaction: 2 INSERT phải cùng thành công
                $pdo->beginTransaction();
                try {
                    // Bước 1: tạo hồ sơ độc giả
                    $stmtDg = $pdo->prepare(
                        'INSERT INTO doc_gia (ho_ten, ngay_sinh, gioi_tinh, so_dien_thoai, dia_chi)
                         VALUES (:ho_ten, :ngay_sinh, :gioi_tinh, :so_dien_thoai, :dia_chi)'
                    );
                    $stmtDg->execute([...]);

                    // Bước 2: lấy id độc giả vừa tạo
                    $idDocGia = (int) $pdo->lastInsertId();

                    // Bước 3: tạo tài khoản gắn sẵn id (role luôn là user)
                    $stmtUser = $pdo->prepare(
                        "INSERT INTO users (ho_ten, username, email, password, role, id_doc_gia)
                         VALUES (:ho_ten, :username, :email, :password, 'user', :id_doc_gia)"
                    );
                    $stmtUser->execute([
                        ...
                        'password'   => password_hash($password, PASSWORD_DEFAULT),
                        'id_doc_gia' => $idDocGia,
                    ]);

                    $pdo->commit(); // xong hết mới lưu
                    header('Location: login.php?registered=1');
                    exit();
                } catch (Exception $e) {
                    $pdo->rollBack(); // lỗi thì hủy hết
                    $error = 'Đăng ký thất bại, vui lòng thử lại.';
                }
            }
        }
    }
}
?>
```

**Giải thích:** transaction (`beginTransaction/commit/rollBack`) đảm bảo
không bao giờ rơi vào cảnh “có độc giả mà mất tài khoản”.

---

## 9. FULL CODE: KIỂM TRA TÊN LIVE BẰNG AJAX (`Login/check-username.php`)

```php
<?php
// Trả về JSON: {"ton_tai": true/false}
// Gọi: check-username.php?username=minhphuc
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$username = strtolower(trim($_GET['username'] ?? ''));

if ($username == '' || preg_match('/^[a-zA-Z0-9]+$/', $username) == false) {
    echo json_encode(['ton_tai' => false, 'hop_le' => false]);
    exit();
}

$check = $pdo->prepare('SELECT COUNT(*) FROM users WHERE LOWER(username) = :username');
$check->execute(['username' => $username]);
echo json_encode(['ton_tai' => $check->fetchColumn() > 0, 'hop_le' => true]);
```

**Cách dùng trong form đăng ký (JavaScript cơ bản):** khi gõ xong 0.4 giây
thì `fetch()` URL trên, đọc JSON rồi hiện “✓ có thể sử dụng” hoặc
“✕ đã được sử dụng”. Backend PHP vẫn kiểm tra lại nên không lách được.

---

## 10. FULL CODE: BẢO VỆ TRANG (`Login/auth.php` cho Admin, `User/auth_user.php` cho độc giả)

```php
<?php
// Login/auth.php — nhớ include ở ĐẦU mọi trang quản trị
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/helpers.php';

// Chưa đăng nhập → về Login
if (empty($_SESSION['id_taikhoan'])) {
    header('Location: ./Login/login.php'); // trong CRUD/ thì '../Login/login.php'
    exit();
}

// Gom thông tin người dùng cho sidebar/header dùng chung
$currentUser = [
    'id'         => $_SESSION['id_taikhoan'],
    'username'   => $_SESSION['username'],
    'ho_ten'     => $_SESSION['ho_ten'],
    'role'       => $_SESSION['role'],
    'id_doc_gia' => $_SESSION['id_docgia'],
];

function isAdmin() {
    return ($_SESSION['role'] ?? 'user') === 'admin';
}

// Trang chỉ admin được vào: độc giả cố vào thì báo + về cổng độc giả
function requireAdmin() {
    if (!isAdmin()) {
        flash('error', 'Bạn không có quyền truy cập chức năng này!');
        header('Location: ./User/index.php'); // trong CRUD/ thì '../User/index.php'
        exit();
    }
}
```

```php
<?php
// User/auth_user.php — nhớ include ở ĐẦU mọi trang độc giả
session_start();
if (empty($_SESSION['id_taikhoan'])) {
    header('Location: ../Login/login.php');
    exit();
}
if (($_SESSION['role'] ?? 'user') === 'admin') {
    header('Location: ../index.php'); // admin về dashboard
    exit();
}
// ... $currentUser giống bên trên
```

**Cách dùng cho trang mới:** trang admin thì 3 dòng đầu file là
`require auth.php + requireAdmin() + require database.php`;
trang độc giả thì `require auth_user.php + require database.php`.
Muốn lấy id độc giả của người đang xem thì dùng
`$currentUser['id_doc_gia']`, tuyệt đối không lấy id trên URL.

---

## 11. BẢNG DATABASE LIÊN QUAN

```text
users   (id, ho_ten, username UNIQUE, email, password(băm), role, id_doc_gia FK → doc_gia, created_at)
doc_gia (id_doc_gia, ho_ten, ngay_sinh, gioi_tinh, so_dien_thoai, dia_chi)
```

---

## 12. TÀI KHOẢN MẪU ĐỂ TEST

```text
admin / admin123       → Admin
nguyenvanan / 123456   → DG001 Nguyễn Văn An
tranminhanh / 123456   → DG002 Trần Minh Anh
phamthuha / 123456     → DG004 Phạm Thu Hà
admin1 / 123456        → DG010 Nguyễn Minh Phúc
```
