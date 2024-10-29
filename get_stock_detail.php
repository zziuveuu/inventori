<?php 
require './conn.php'; // Hubungkan ke file koneksi

if (isset($_POST['barang_id'])) {
    $barang_id = $_POST['barang_id'];

    // Ambil detail stock
    $sql = "SELECT b.nama_barang, sd.stock_id, sd.id as stock_id_detail, sd.deskripsi, 
            sd.kode_stock, sd.status, l.nama_lokasi, sd.lokasi_id, k.nama_kategori
            FROM stock_detail sd
            INNER JOIN stock s ON sd.barang_id = s.barang_id
            INNER JOIN barang b ON sd.barang_id = b.id
            INNER JOIN lokasi l ON sd.lokasi_id = l.id
            INNER JOIN barang_kategori k ON b.kategori_id = k.id
            WHERE sd.barang_id = ? AND sd.status != 'Dipinjam' AND sd.status != 'none active'";

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
            "lokasiDetails" => $stockDetails // Kirim semua detail stock
        ]);
    } else {
        echo json_encode(["error" => "Barang yang Active tidak ada."]);
    }
} else {
    echo json_encode(["error" => "Barang ID tidak ditemukan."]);
}
?>
