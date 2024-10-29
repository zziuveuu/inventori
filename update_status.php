<?php
require './conn.php';
session_start();

// Pastikan pengguna sudah login
if (!isset($_SESSION['session_username'])) {
    header("location:/inventori/index.php");
    exit();
}

// Ambil ID, status, dan keterangan dari form
$id = isset($_POST['id']) ? $_POST['id'] : null;
$status = isset($_POST['status']) ? $_POST['status'] : null;
$keterangan = isset($_POST['keterangan']) ? $_POST['keterangan'] : null;

// Pastikan ID dan status ada
if ($id !== null && $status !== null) {
    // Update status dan keterangan di tabel stock_detail
    $sql = "UPDATE stock_detail SET status = ?, keterangan = ? WHERE id = ?";
    $stmt = $connection->prepare($sql);
    if ($stmt === false) {
        die('Error preparing statement: ' . $connection->error);
    }
    
    $stmt->bind_param("ssi", $status, $keterangan, $id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Status berhasil diperbarui.";
    } else {
        $_SESSION['message'] = "Gagal memperbarui status.";
    }
    $stmt->close();
}

// Redirect kembali ke halaman sebelumnya
header("Location: stock_detail.php?stock_id=" . $_POST['stock_id']); // Pastikan stock_id juga dikirim kembali
exit();
?>
