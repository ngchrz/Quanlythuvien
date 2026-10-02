<?php if ($_SERVER["REQUEST_METHOD"] == "POST"):
//echo "Chào các bạn";

echo "Mã sách: " . $_POST["id-sach"];
echo "<br />";
echo "Tên sách: " . $_POST["ten-sach"];
echo "<br />";
echo "Tác giả: " . $_POST["tac-gia"];
echo "<br />";
echo "Thể loại: " . $_POST["the-loai"];
endif;
?>

<!DOCTYPE html>
<html>
    <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản Lý Sách - Thêm Sách</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    </head>
<body>

<div class="container mt-4">

<h2>Quản Lý Sách</h2>

<form action="" method="POST">

  <label for="id_sach">Mã sách:</label><br>
  <input type="text" class="form-control" id="id_sach" name="id-sach"><br>

  <label for="name">Tên sách:</label><br>
  <input type="text" class="form-control" id="name" name="ten-sach"><br>

  <label for="tac_gia">Tác giả:</label><br>
  <input type="text" class="form-control" id="tac_gia" name="tac-gia"><br>

  <label for="the_loai">Thể loại:</label><br>
  <input type="text" class="form-control" id="the_loai" name="the-loai"><br><br>

  <input type="submit" class="btn btn-primary" value="Thêm Sách">
</form>

</div>

</body>
</html>