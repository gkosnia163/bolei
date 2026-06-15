<?php
// db.php
$con = mysqli_connect("localhost", "root", "");

if (!$con) {
    die("Πρόβλημα στη σύνδεση με τη βάση: " . mysqli_connect_error());
}

mysqli_select_db($con, "bolei_db");
mysqli_set_charset($con, "utf8");
