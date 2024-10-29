<?php
require "./conn.php"; // Menghubungkan dengan file koneksi

// Mengecek koneksi
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

// Mendapatkan ID barang dari request POST
if (isset($_POST['barang_id'])) {
    $barang_id = intval($_POST['barang_id']);
    
    // Mengambil data lokasi dan quantity dari tabel stock berdasarkan barang_id
    $sql = "SELECT s.lokasi_id, l.nama_lokasi, s.id
            FROM stock s
            INNER JOIN lokasi l ON s.lokasi_id = l.id
            WHERE s.barang_id = ?";
    $stmt = $connection->prepare($sql);
    
    if ($stmt === false) {
        echo json_encode(["error" => "Prepare statement gagal: " . $connection->error]);
        exit();
    }
    
    $stmt->bind_param("i", $barang_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $response = [
            "nama_lokasi" => $row['nama_lokasi'],
            "lokasi_id" => $row['lokasi_id'],
           
        ];
        echo json_encode($response);
    } else {
        echo json_encode(["error" => "Data tidak ditemukan"]);
    }
    
    $stmt->close();
} else {
    echo json_encode(["error" => "ID barang tidak ditemukan"]);
}

$connection->close();
?>
