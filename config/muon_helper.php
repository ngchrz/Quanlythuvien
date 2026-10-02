<?php
// File: config/muon_helper.php
// 1 LOGIC TRẠNG THÁI DÙNG CHUNG cho mọi trang (Dashboard, Mượn trả, Độc giả).
// Quy tắc (giống nhau ở mọi nơi):
//   hạn trả = ngày mượn + 7 ngày
//   - Có ngày trả                  -> Đã trả
//   - Chưa trả + hôm nay > hạn trả -> Quá hạn
//   - Còn lại                      -> Đang mượn
// Chú ý: chưa trả (ngay_tra NULL) KHÁC với quá hạn,
// phải so với hạn trả rồi mới kết luận.

// Số ngày tối đa được mượn
define('SO_NGAY_DUOC_MUON', 7);

// Tính ngày hạn trả từ ngày mượn (trả về 'Y-m-d', rỗng nếu không có ngày mượn)
function hanTra($ngayMuon) {
    if (empty($ngayMuon)) {
        return '';
    }
    return date('Y-m-d', strtotime($ngayMuon . ' +' . SO_NGAY_DUOC_MUON . ' days'));
}

// Tính trạng thái hiển thị của 1 phiếu mượn ($phieu là 1 dòng từ database)
function tinhTrangThai($phieu) {
    // Có ngày trả rồi nghĩa là đã trả
    if (!empty($phieu['ngay_tra'])) {
        return 'Đã trả';
    }
    // Chưa trả: so hôm nay với hạn trả
    $han = hanTra($phieu['ngay_muon'] ?? '');
    if ($han === '') {
        return 'Đang mượn';
    }
    if (date('Y-m-d') > $han) {
        return 'Quá hạn';
    }
    return 'Đang mượn';
}

// Màu của badge theo trạng thái
function lopBadge($trangThai) {
    if ($trangThai === 'Đã trả') {
        return 'b-tra';
    }
    if ($trangThai === 'Quá hạn') {
        return 'b-han';
    }
    return 'b-muon';
}
