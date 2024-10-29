<?php
require "./conn.php";

$kategori_id = isset($_GET['kategori_id']) ? $_GET['kategori_id'] : '';

$sql = "SELECT COUNT(*) AS total FROM stock s 
        JOIN barang b ON s.barang_id = b.id 
        WHERE 1=1";

if (!empty($kategori_id)) {
    $sql .= " AND b.kategori_id = ?";
}

$stmt = $connection->prepare($sql);
if (!empty($kategori_id)) {
    $stmt->bind_param("i", $kategori_id);
}

$stmt->execute();
$result = $stmt->get_result();
$total = $result->fetch_assoc()['total'];

echo json_encode(['total' => $total]);
?>
