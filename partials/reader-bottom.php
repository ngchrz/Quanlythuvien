<?php // partials/reader-bottom.php — Cuối trang doc gia: chân trang + hop xac nhan + JS. ?>
    <footer class="page-foot">© 2026 Hệ thống quản lý thư viện — Cao đẳng Bách Khoa</footer>
</main><!-- /.page -->

<!-- Hộp xác nhận -->
<div class="modal-backdrop" id="confirmModal">
    <div class="modal">
        <div class="modal-ico" id="cmIcon">⚠️</div>
        <h4 id="cmTitle">Xác nhận</h4>
        <p id="cmMsg">Bạn có chắc chắn?</p>
        <div class="modal-actions">
            <button class="btn btn-light" id="cmCancel">Hủy</button>
            <a class="btn btn-danger" id="cmOk" href="#">Đồng ý</a>
        </div>
    </div>
</div>

<script>
// Nút 3 gạch: ẩn / hiện menu trên điện thoại
(function () {
    var nut = document.getElementById('btnMenu');
    var menu = document.getElementById('mainMenu');
    if (nut == null || menu == null) {
        return;
    }
    nut.addEventListener('click', function () {
        if (menu.classList.contains('open')) {
            menu.classList.remove('open');
        } else {
            menu.classList.add('open');
        }
    });
})();
// Bấm vào tên thì mở menu Tài khoản, bấm ra ngoài thì đóng
(function () {
    var nut = document.getElementById('userChip');
    var menu = document.getElementById('userMenu');
    if (nut == null || menu == null) {
        return;
    }
    nut.addEventListener('click', function (e) {
        e.stopPropagation();
        if (menu.classList.contains('open')) {
            menu.classList.remove('open');
        } else {
            menu.classList.add('open');
        }
    });
    document.addEventListener('click', function () {
        menu.classList.remove('open');
    });
})();
// Hộp xác nhận cho link có [data-confirm]
(function () {
    var hop = document.getElementById('confirmModal');
    var chu = document.getElementById('cmMsg');
    var nutOk = document.getElementById('cmOk');
    var icon = document.getElementById('cmIcon');
    var nutHuy = document.getElementById('cmCancel');
    document.querySelectorAll('[data-confirm]').forEach(function (a) {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            var phan = a.getAttribute('data-confirm').split('|');
            chu.textContent = phan[0] || 'Bạn có chắc chắn?';
            nutOk.textContent = phan[1] || 'Đồng ý';
            icon.textContent = phan[2] || '⚠️';
            nutOk.href = a.href;
            hop.classList.add('open');
        });
    });
    nutHuy.addEventListener('click', function () { hop.classList.remove('open'); });
    hop.addEventListener('click', function (e) {
        if (e.target === hop) hop.classList.remove('open');
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') hop.classList.remove('open');
    });
})();
// Thông báo tự ẩn sau 4 giây
setTimeout(function () {
    document.querySelectorAll('.toast').forEach(function (t) {
        t.style.transition = 'opacity .4s ease';
        t.style.opacity = '0';
        setTimeout(function () { t.remove(); }, 400);
    });
}, 4000);
</script>
</body>
</html>
