<?php
// File: config/helpers.php
// Các hàm nhỏ dùng chung cho cả website.

// In chữ ra màn hình cho an toàn (chống gõ mã độc vào ô nhập)
function e($s) {
    if ($s == null) {
        $s = '';
    }
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// Lưu một thông báo để hiện ở trang kế tiếp (hiện 1 lần rồi tự mất)
// $type có 3 loại: success (xanh), error (đỏ), warning (vàng)
function flash($type, $msg) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

// In thông báo đã lưu ở trên (gọi trong layout)
function showFlash() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // Không có thông báo thì thôi
    if (empty($_SESSION['flash'])) {
        return;
    }
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']); // in xong thì xóa

    // Chọn màu và icon theo loại
    $type = $f['type'];
    if ($type != 'success' && $type != 'warning') {
        $type = 'error';
    }
    if ($type == 'success') {
        $icon = '✓';
    } elseif ($type == 'warning') {
        $icon = '⚠';
    } else {
        $icon = '✕';
    }

    echo '<div class="toast toast-' . $type . '">';
    echo '<span class="toast-icon">' . $icon . '</span>';
    echo '<span>' . e($f['msg']) . '</span>';
    echo '<button class="toast-close" onclick="this.parentElement.remove()">✕</button>';
    echo '</div>';
}

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
