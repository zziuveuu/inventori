<?php 
require './conn.php'; // Hubungkan ke file koneksi

if (isset($_POST['barang_id'])) {
    $barang_id = $_POST['barang_id'];

    // Ambil lokasi dan detail stock
    $sql = "SELECT b.nama_barang, sd.deskripsi, sd.kode_stock, sd.status
            FROM stock_detail sd
            INNER JOIN stock s ON sd.barang_id = s.barang_id
            INNER JOIN barang b ON sd.barang_id = b.id
            INNER JOIN lokasi l ON sd.lokasi_id = l.id
            WHERE sd.barang_id = ?";

    $stmt = $connection->prepare($sql);
    $stmt->bind_param("i", $barang_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $stockDetails = [];
    while ($row = $result->fetch_assoc()) {
        $stockDetails[] = $row; // Menambahkan setiap baris ke dalam array
    }

    if (count($stockDetails) > 0) {
        echo json_encode([
            "lokasi_id" => $stockDetails[0]['lokasi_id'],
            "nama_lokasi" => $stockDetails[0]['nama_lokasi'],
            "quantity" => $stockDetails[0]['quantity'],
            "stock_id" => $stockDetails[0]['kode_stock'], // Sesuaikan jika ini kode stock yang dimaksud
            "stockDetails" => $stockDetails // Kirim semua detail stock
        ]);
    } else {
        echo json_encode(["error" => "Data tidak ditemukan untuk barang ini."]);
    }
} else {
    echo json_encode(["error" => "Barang ID tidak ditemukan."]);
}
?>
