<?php
require "./conn.php";

$kategori_id = isset($_GET['kategori_id']) ? $_GET['kategori_id'] : '';
$items_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

$sql = "SELECT s.id, b.nama_barang, s.quantity, s.qty_active, s.created_date, s.updated_date 
        FROM stock s 
        JOIN barang b ON s.barang_id = b.id 
        WHERE 1=1";

if (!empty($kategori_id)) {
    $sql .= " AND b.kategori_id = ?";
}

$sql .= " ORDER BY s.id DESC LIMIT ? OFFSET ?";

$stmt = $connection->prepare($sql);
if (!empty($kategori_id)) {
    $stmt->bind_param("iii", $kategori_id, $items_per_page, $offset);
} else {
    $stmt->bind_param("ii", $items_per_page, $offset);
}

$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

// Hitung total barang untuk kategori terpilih
$count_sql = "SELECT COUNT(*) AS total FROM stock s 
              JOIN barang b ON s.barang_id = b.id 
              WHERE 1=1";

if (!empty($kategori_id)) {
    $count_sql .= " AND b.kategori_id = ?";
    $count_stmt = $connection->prepare($count_sql);
    $count_stmt->bind_param("i", $kategori_id);
} else {
    $count_stmt = $connection->prepare($count_sql);
}

$count_stmt->execute();
$total_items = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_items / $items_per_page);

// Mengembalikan data sebagai JSON
echo json_encode([
    'items' => $items,
    'total_pages' => $total_pages
]);
?>
