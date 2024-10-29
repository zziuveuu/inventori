<?php
require "./conn.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['barang_id'])) {
    $barang_id = $_POST['barang_id'];

    $sql = "SELECT p.lokasi_id, l.nama_lokasi, p.quantity 
            FROM pengambilan_barang p
            INNER JOIN lokasi l ON p.lokasi_id = l.id
            WHERE p.barang_id = ?
            LIMIT 1";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("i", $barang_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode([
            'lokasi_id' => $row['lokasi_id'],
            'nama_lokasi' => $row['nama_lokasi'],
            'quantity' => $row['quantity']
        ]);
    } else {
        echo json_encode(['error' => 'Barang tidak ditemukan']);
    }
}
?>
