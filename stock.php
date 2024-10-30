<?php 
// Koneksi ke database
require "./conn.php";

// Memulai sesi
session_start();

// Pastikan pengguna sudah login
if (!isset($_SESSION['session_username'])) {
    header("location:/inventori/index.php");
    exit();
}

$items_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Inisialisasi variabel editMode
$editMode = false;
$editStock = null;

// Ambil daftar kategori dari tabel barang_kategori
$sql_kategori = "SELECT id, nama_kategori FROM barang_kategori";
$result_kategori = $connection->query($sql_kategori);

// Proses Penghapusan Stok
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    if (!empty($delete_id) && is_numeric($delete_id)) {
        // Cek apakah barang dengan ID tersebut ada di database
        $check_sql = "SELECT * FROM stock WHERE id = ?";
        $check_stmt = $connection->prepare($check_sql);
        $check_stmt->bind_param("i", $delete_id);
        $check_stmt->execute();
        $result_check = $check_stmt->get_result();
        if ($result_check->num_rows > 0) {
            // Proses penghapusan
            $sql_delete = "DELETE FROM stock WHERE id = ?";
            $stmt_delete = $connection->prepare($sql_delete);
            if ($stmt_delete) {
                $stmt_delete->bind_param("i", $delete_id);
                if ($stmt_delete->execute()) {
                    echo "<script>alert('Stok berhasil dihapus!'); window.location.href='stock_list.php';</script>";
                } else {
                    echo "<script>alert('Terjadi kesalahan saat menghapus stok.');</script>";
                }
                $stmt_delete->close();
            }
        } else {
            echo "<script>alert('ID stok tidak ditemukan.');</script>";
        }
        $check_stmt->close();
    }
}

// Query untuk menghitung qty_active dan memperbarui tabel stock
$updateQtyActiveSql = "
    UPDATE stock s
    SET s.qty_active = (
        SELECT COUNT(*) 
        FROM stock_detail sd 
        WHERE sd.stock_id = s.id AND sd.status = 'active'
    )
";

// Fungsi untuk mencari data
$searchTerm = "";
if (isset($_GET['search'])) {
    $searchTerm = trim($_GET['search']); // Membersihkan input
}

// Mengambil kategori yang dipilih
$kategori_id = isset($_GET['kategori']) ? $_GET['kategori'] : ''; 

// Mengupdate qty_active
$updateStmt = $connection->prepare($updateQtyActiveSql);
if ($updateStmt === false) {
    die('Error preparing update statement: ' . $connection->error);
}
if (!$updateStmt->execute()) {
    die('Error executing update statement: ' . $updateStmt->error);
}

// Query untuk mengambil data stok termasuk qty_active dengan pagination
$sql = "SELECT s.id, s.quantity, s.qty_active, s.created_date, s.updated_date, 
        barang.nama_barang
        FROM stock s
        JOIN barang ON s.barang_id = barang.id
        WHERE 1=1"; // Tambahkan WHERE 1=1 agar lebih mudah menambahkan kondisi

// Kondisi untuk kategori
if (!empty($kategori_id)) {
    $sql .= " AND barang.kategori_id = ?";
}

// Kondisi untuk pencarian
$search_query = [];
if (!empty($searchTerm)) {
    $sql .= " AND (s.id LIKE ? OR barang.nama_barang LIKE ?)";
    $search_query[] = "%$searchTerm%"; // Menggunakan wildcard untuk pencarian
    $search_query[] = "%$searchTerm%";
}

// Tambahkan pengurutan dan limit
$sql .= " ORDER BY s.id DESC LIMIT ?, ?";

$stmt = $connection->prepare($sql);
if ($stmt === false) {
    die('Error preparing statement: ' . $connection->error);
}

// Mengikat parameter untuk pencarian
if (!empty($kategori_id) && !empty($search_query)) {
    $stmt->bind_param("issii", $kategori_id, $search_query[0], $search_query[1], $offset, $items_per_page);
} elseif (!empty($kategori_id)) {
    $stmt->bind_param("iii", $kategori_id, $offset, $items_per_page);
} elseif (!empty($search_query)) {
    $stmt->bind_param("ssii", $search_query[0], $search_query[1], $offset, $items_per_page);
} else {
    $stmt->bind_param("ii", $offset, $items_per_page);
}

$stmt->execute();
$result = $stmt->get_result();

// Menghitung total data untuk pagination
$sql_count = "SELECT COUNT(*) AS total FROM stock s 
              JOIN barang ON s.barang_id = barang.id 
              WHERE 1=1";

if (!empty($kategori_id)) {
    $sql_count .= " AND barang.kategori_id = ?";
}
if (!empty($searchTerm)) {
    $sql_count .= " AND (s.id LIKE ? OR barang.nama_barang LIKE ?)";
}

$count_stmt = $connection->prepare($sql_count);

if (!empty($kategori_id) && !empty($search_query)) {
    $count_stmt->bind_param("iss", $kategori_id, $search_query[0], $search_query[1]);
} elseif (!empty($kategori_id)) {
    $count_stmt->bind_param("i", $kategori_id);
} elseif (!empty($search_query)) {
    $count_stmt->bind_param("ss", $search_query[0], $search_query[1]);
}

$count_stmt->execute();
$totalData = $count_stmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalData / $items_per_page);

// Cek mode edit jika ada parameter edit_id
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $sql = "SELECT stock.id, stock.quantity, stock.lokasi_id, stock.barang_id, barang.nama_barang, lokasi.nama_lokasi 
            FROM stock
            JOIN barang ON stock.barang_id = barang.id
            JOIN lokasi ON stock.lokasi_id = lokasi.id
            WHERE stock.id = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $editStock = $stmt->get_result()->fetch_assoc();
    $editMode = true; // Set editMode ke true jika dalam mode edit
    $stmt->close();
}

// Proses pembaruan data barang
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // [Proses update data barang jika dibutuhkan]
}
?>

<!-- Kode HTML untuk menampilkan data dan tombol delete -->

<?php
require "./header.php";
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<!-- Navbar -->
<?php 
require "./navbar.php";
?>
<!-- Navbar -->

<div class="container my-2">
    <h2><?php echo $editMode ? "Edit Stock" : "Daftar Stock"; ?></h2>
    
    <?php if (!$editMode): ?>
        <a style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="btn btn-primary mb-3" href="/inventori/createstock.php" role="button">New Stock</a>
    <?php endif; ?>

    <!-- Form Filter Kategori dan Pencarian Bersama -->
<form method="get" action="">
    <div class="input-group mb-3" style="max-width:fit-content;">
        <!-- Select Kategori -->
        <select class="form-select" name="kategori" id="kategori" onchange="this.form.submit()">
            <option value="">Pilih Kategori</option>
            <?php while ($kategori = $result_kategori->fetch_assoc()): ?>
                <option value="<?php echo $kategori['id']; ?>" <?php echo ($kategori_id == $kategori['id']) ? 'selected' : ''; ?>>
                    <?php echo $kategori['nama_kategori']; ?>
                </option>
            <?php endwhile; ?>
        </select>

        <!-- Input Pencarian -->
        <input type="text" class="form-control" placeholder="Nama Barang" name="search" id="search" value="<?php echo htmlspecialchars($searchTerm); ?>">
        <button style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
    </div>
</form>


    <!-- Tabel Data Stok -->
    <table class="table table-striped table-bordered" style="max-width:fit-content; max-height:fit-content;">
        <thead>
            <tr>
                <th style="background-color:cornflowerblue;">No.</th>
                <th style="background-color:cornflowerblue;">Nama Barang</th>
                <th style="background-color:cornflowerblue;">Stock Total</th>
                <th style="background-color:cornflowerblue;">Qty Active</th>
                <th style="background-color:cornflowerblue;">Create Date</th>
                <th style="background-color:cornflowerblue;">Update Date</th>
                <th style="background-color:cornflowerblue;">Action</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php $i = 1 + $offset; ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $i++ . '.' ?></td>
                    <td><?php echo htmlspecialchars($row['nama_barang']); ?></td>
                    <td style="text-align: center;"><?php echo htmlspecialchars($row['quantity']); ?></td>
                    <td style="text-align: center;"><?php echo htmlspecialchars($row['qty_active']); ?></td>
                    <td><?php echo htmlspecialchars($row['created_date']); ?></td>
                    <td><?php echo htmlspecialchars($row['updated_date']); ?></td>
                    <td>
                            <a style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" href="stock_detail.php?stock_id=<?php echo $row['id']; ?>" class='btn btn-primary btn-sm'><i class="fas fa-info-circle"></i>Detail</a>
                        </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7" class="text-center">Tidak ada data stok ditemukan.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

    <nav aria-label="Page navigation example">
    <ul class="pagination">
        <!-- Tombol Previous -->
        <?php if ($current_page > 1): ?>
            <li class="page-item">
                <a style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="page-link" 
                   href="?page=<?php echo $current_page - 1; ?><?php echo (!empty($kategori_id)) ? "&kategori=".urlencode($kategori_id) : ""; ?><?php echo (!empty($searchTerm)) ? "&search=".urlencode($searchTerm) : ""; ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
            </li>
        <?php endif; ?>

        <!-- Link ke setiap halaman -->
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?php echo $i === $current_page ? 'active' : ''; ?>">
                <a style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="page-link" 
                   href="?page=<?php echo $i; ?><?php echo (!empty($kategori_id)) ? "&kategori=".urlencode($kategori_id) : ""; ?><?php echo (!empty($searchTerm)) ? "&search=".urlencode($searchTerm) : ""; ?>">
                   <?php echo $i; ?>
                </a>
            </li>
        <?php endfor; ?>

        <!-- Tombol Next -->
        <?php if ($current_page < $totalPages): ?>
            <li class="page-item">
                <a style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="page-link" 
                   href="?page=<?php echo $current_page + 1; ?><?php echo (!empty($kategori_id)) ? "&kategori=".urlencode($kategori_id) : ""; ?><?php echo (!empty($searchTerm)) ? "&search=".urlencode($searchTerm) : ""; ?>" aria-label="Previous">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
            </li>
        <?php endif; ?>
    </ul>
</nav>


<?php
require "./foother.php";
?>
