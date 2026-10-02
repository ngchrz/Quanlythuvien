<?php // partials/inner-bottom.php — Cuối trang CRUD: chân trang + JS don gian. ?>
    <footer class="page-foot">© 2026 Hệ thống quản lý thư viện — Cao đẳng Bách Khoa</footer>
</main><!-- /.page -->
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
// Thông báo tự ẩn sau 4 giây
setTimeout(function () {
    document.querySelectorAll('.toast').forEach(function (t) {
        t.style.transition = 'opacity .4s ease';
        t.style.opacity = '0';
        setTimeout(function () { t.remove(); }, 400);
    });
}, 4000);
</script>
