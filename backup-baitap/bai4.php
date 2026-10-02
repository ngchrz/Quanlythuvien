<?php
require_once "./config/database.php";


echo "<h1>Kết nối cơ sở dữ liệu thành công</h1>";

$sql = "SELECT * FROM quyensach";

$s = $pdo->query($sql);

$data = $s->fetchAll();

// print("<pre>");
// print_r($data);

foreach($data as $book){
    echo $book["id_sach"]."<br />";
    echo $book["name"]."<br />";
    echo $book["img"]."<br />";
    echo $book["tac_gia"]."<br />";
    echo $book["the_loai"]."<br />";

}

?>