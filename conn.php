<?php

$servername = "localhost";
$username = "root";
$password = "";
$database = "inventori";

// Membuat koneksi ke database
$connection = new mysqli($servername, $username, $password, $database);

// Mengecek koneksi
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}
?>