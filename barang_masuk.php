<?php
require "./conn.php";

// Memulai sesi
session_start();

// Pastikan pengguna sudah login
if (!isset($_SESSION['session_username'])) {
    header("location:/inventori/index.php");
    exit();
}

// Fungsi untuk menghapus barang masuk
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // Ambil data quantity yang akan dihapus
    $sql = "SELECT quantity, barang_id FROM barang_masuk WHERE id = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $quantity_to_restore = $row['quantity'];
        $barang_id = $row['barang_id'];

        // Hapus data dari tabel barang_masuk
        $sql_delete = "DELETE FROM barang_masuk WHERE id = ?";
        $stmt_delete = $connection->prepare($sql_delete);
        $stmt_delete->bind_param("i", $delete_id);

        if ($stmt_delete->execute()) {
            // Tambahkan kembali quantity ke tabel stock
            $sql_update_stock = "UPDATE stock SET quantity = quantity + ? WHERE barang_id = ?";
            $stmt_update_stock = $connection->prepare($sql_update_stock);
            $stmt_update_stock->bind_param("ii", $quantity_to_restore, $barang_id);
            $stmt_update_stock->execute();

            header("Location: /inventori/barang_masuk.php?message=deleted");
            exit();
        } else {
            echo "Error deleting record: " . $stmt_delete->error;
        }
    } else {
        echo "Data tidak ditemukan.";
    }

    $stmt->close();
}

// Fungsi untuk mencari data
$searchTerm = "";
if (isset($_GET['search'])) {
    $searchTerm = trim($_GET['search']); // Membersihkan input
}

// Fungsi untuk mengedit data barang masuk
$editMode = false;
$editBarangMasuk = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']); // Sanitasi input ID
    $sql = "SELECT id, barang_id, nama_pengembali, lokasi_id, quantity FROM barang_masuk WHERE id = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $editBarangMasuk = $stmt->get_result()->fetch_assoc();
    if ($editBarangMasuk) {
        $editMode = true;
    } else {
        echo "<div class='alert alert-danger'>Data tidak ditemukan.</div>";
    }
    $stmt->close();
}

// Ambil data barang dan lokasi untuk dropdown select option
$sql_barang = "SELECT id, nama_barang FROM barang";
$result_barang = $connection->query($sql_barang);

$sql_lokasi = "SELECT id, nama_lokasi FROM lokasi";
$result_lokasi = $connection->query($sql_lokasi);

// Proses pembaruan data barang masuk
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = intval($_POST["id"]);
    $barang_id = trim($_POST["barang_id"]);
    $nama_pengembali = trim($_POST["nama_pengembali"]);
    $lokasi_id = trim($_POST["lokasi_id"]);
    $quantity = intval($_POST["quantity"]);
    $create_by = $_SESSION['session_username'];  // Informasi user yang meng-update

    if (!empty($id) && !empty($barang_id) && !empty($nama_pengembali) && !empty($lokasi_id) && $quantity > 0) {
        // Update data barang_masuk
        $sql_update = "UPDATE barang_masuk SET barang_id = ?, nama_pengembali = ?, lokasi_id = ?, quantity = ?, create_by = ? WHERE id = ?";
        $stmt_update = $connection->prepare($sql_update);
        if ($stmt_update === false) {
            die('Error preparing statement: ' . $connection->error);
        }

        // Bind parameter untuk update
        $stmt_update->bind_param("sssisi", $barang_id, $nama_pengembali, $lokasi_id, $quantity, $create_by, $id);

        if ($stmt_update->execute()) {
            // Update stok barang sesuai dengan input baru
            $sql_update_stock = "UPDATE stock SET quantity = quantity + ? WHERE barang_id = ?";
            $stmt_update_stock = $connection->prepare($sql_update_stock);
            $stmt_update_stock->bind_param("ii", $quantity, $barang_id);
            $stmt_update_stock->execute();

            // Jika berhasil update, redirect ke halaman barang_masuk
            header("Location: /inventori/barang_masuk.php?message=updated");
            exit();
        } else {
            echo "Error updating data: " . $stmt_update->error;
        }

        $stmt_update->close();
    } else {
        echo "Semua field wajib diisi dan quantity harus lebih besar dari 0.";
    }
}

// Ambil data barang masuk
// $sql = "SELECT bm.id, b.nama_barang, bm.nama_pengembali, l.nama_lokasi, bm.quantity, bm.tanggal_masuk, bm.create_by
//         FROM barang_masuk bm
//         INNER JOIN barang b ON bm.barang_id = b.id
//         INNER JOIN lokasi l ON bm.lokasi_id = l.id
//         ORDER BY bm.id DESC";

// if (!empty($searchTerm)) {
//     $sql .= " WHERE bm.barang_id LIKE ? OR bm.id LIKE ?";
// }

// $stmt = $connection->prepare($sql);
// if (!empty($searchTerm)) {
//     $searchTermParam = "%$searchTerm%";
//     $stmt->bind_param("ss", $searchTermParam, $searchTermParam);
// }

// $stmt->execute();
// $result = $stmt->get_result();
// ?>

<?php
require "./header.php";
?>

<!-- Navbar -->
<?php 
require "./navbar.php";
?>
<!-- Navbar -->

<div class="container my-5">
    <h2><?php echo $editMode ? "Edit Pengembalian Barang" : "List of Pengembalian Barang"; ?></h2>
    <?php (!$editMode)?>
    <a class="btn btn-primary" href="/inventori/pengembalian_barang.php" role="button">Tambah Barang Masuk</a><br><br>

    <!-- Form pencarian -->
    <form method="get" action="">
        <div class="input-group mb-3">
            <input type="text" class="form-control" placeholder="Search by ID or Barang" name="search" id="search" value="<?php echo htmlspecialchars($searchTerm); ?>">
            <button class="btn btn-outline-secondary" type="submit">Search</button>
        </div>
    </form>

    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>No.</th>
                <th>ID</th>
                <th>Nama Barang</th>
                <th>Nama Pengembali</th>
                <th>Lokasi</th>
                <th>Quantity</th>
                <th>Tanggal Masuk</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Ambil data barang masuk
                $sql = "SELECT bm.id, b.nama_barang, bm.nama_pengembali, l.nama_lokasi, bm.quantity, bm.tanggal_masuk, bm.create_by
                FROM barang_masuk bm
                INNER JOIN barang b ON bm.barang_id = b.id
                INNER JOIN lokasi l ON bm.lokasi_id = l.id
                ORDER BY bm.id DESC";
                 $result = $connection->query($sql);
            if ($result->num_rows > 0) {
                $no = 1;
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>{$no}</td>
                            <td>" . htmlspecialchars($row['id']) . "</td>
                            <td>" . htmlspecialchars($row['nama_barang']) . "</td>
                            <td>" . htmlspecialchars($row['nama_pengembali']) . "</td>
                            <td>" . htmlspecialchars($row['nama_lokasi']) . "</td>
                            <td>" . htmlspecialchars($row['quantity']) . "</td>
                            <td>" . htmlspecialchars($row['tanggal_masuk']) . "</td>
                            <td>
                                <a class='btn btn-primary btn-sm' href='/inventori/barang_masuk.php?edit_id={$row['id']}'>Edit</a>
                                <a class='btn btn-danger btn-sm' href='/inventori/barang_masuk.php?delete_id={$row['id']}' onclick=\"return confirm('Apakah Anda yakin ingin menghapus data ini?');\">Delete</a>
                            </td>
                          </tr>";
                    $no += 1;
                }
            } else {
                echo "<tr><td colspan='9'>Tidak ada data barang masuk.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>
<!-- Form untuk mengedit barang masuk -->
    <?php if ($editMode): ?>
        <!-- Form untuk mengedit barang masuk -->
        <form method="post" action="">
            <input type="hidden" name="id" value="<?php echo $editBarangMasuk['id']; ?>">

            <div class="mb-3">
                <label for="barang_id" class="form-label">Nama Barang</label>
                <select class="form-control" id="barang_id" name="barang_id" required>
                    <option value="">-- Pilih Barang --</option>
                    <?php
                    if ($result_barang->num_rows > 0) {
                        while ($row_barang = $result_barang->fetch_assoc()) {
                            $selected = ($row_barang['id'] == $editBarangMasuk['barang_id']) ? "selected" : "";
                            echo "<option value='{$row_barang['id']}' $selected>{$row_barang['nama_barang']}</option>";
                        }
                    } else {
                        echo "<option value=''>Tidak ada barang tersedia</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="nama_pengembali" class="form-label">Nama Pengembali</label>
                <input type="text" class="form-control" id="nama_pengembali" name="nama_pengembali" value="<?php echo $editBarangMasuk['nama_pengembali']; ?>" required>
            </div>

            <div class="mb-3">
                <label for="lokasi_id" class="form-label">Lokasi</label>
                <select class="form-control" id="lokasi_id" name="lokasi_id" required>
                    <option value="">-- Pilih Lokasi --</option>
                    <?php
                    if ($result_lokasi->num_rows > 0) {
                        while ($row_lokasi = $result_lokasi->fetch_assoc()) {
                            $selected = ($row_lokasi['id'] == $editBarangMasuk['lokasi_id']) ? "selected" : "";
                            echo "<option value='{$row_lokasi['id']}' $selected>{$row_lokasi['nama_lokasi']}</option>";
                        }
                    } else {
                        echo "<option value=''>Tidak ada lokasi tersedia</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="quantity" class="form-label">Quantity</label>
                <input type="number" class="form-control" id="quantity" name="quantity" value="<?php echo $editBarangMasuk['quantity']; ?>" required>
            </div>

            <button type="submit" class="btn btn-primary">Update</button>
        </form>
    <?php endif; ?>
</div>

<?php
require "./foother.php";
?>