<?php
// Koneksi ke database
require "./conn.php";

// Memulai sesi
session_start();

$editMode = false;
$kategori_id = "";
$searchTerm = "";

// Pastikan pengguna sudah login
if (!isset($_SESSION['session_username'])) {
    header("location:/inventori/index.php");
    exit();
}

$items_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Fungsi untuk generate kode barang
function generateKodeBarang($connection) {
    // Kode tetap sama
}

// Hapus barang berdasarkan ID
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $sql = "DELETE FROM barang WHERE id = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("i", $delete_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Barang berhasil dihapus.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting barang: ' . $connection->error]);
    }

    $stmt->close();
    exit();
}

// Fungsi untuk mencari data barang
$searchTerm = isset($_GET['search']) ? $_GET['search'] : "";
$kategori_id = isset($_GET['kategori']) ? $_GET['kategori'] : "";

$sql = "SELECT barang.id, barang.kode_barang, barang.nama_barang, barang.createdate, barang.update_date, bk.nama_kategori AS kategori
        FROM barang
        LEFT JOIN barang_kategori bk ON barang.kategori_id = bk.id";

// Tambahkan kondisi jika ada pencarian
$conditions = [];
$parameters = [];
$types = "";

if (!empty($searchTerm)) {
    $conditions[] = "(barang.kode_barang LIKE ? OR barang.nama_barang LIKE ? OR bk.nama_kategori LIKE ?)";
    $searchTermParam = "%$searchTerm%";
    $parameters[] = $searchTermParam;
    $parameters[] = $searchTermParam;
    $parameters[] = $searchTermParam;
    $types .= "sss";
}

// Gabungkan kondisi ke dalam query
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

// Tambahkan limit dan offset untuk pagination
$sql .= " ORDER BY barang.id DESC LIMIT ? OFFSET ?";
$parameters[] = $items_per_page;
$parameters[] = $offset;
$types .= "ii";

$stmt = $connection->prepare($sql);
$stmt->bind_param($types, ...$parameters);
$stmt->execute();
$result = $stmt->get_result();

// Fungsi untuk mengedit data barang berdasarkan ID
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $sql = "SELECT id, nama_barang FROM barang WHERE id = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $editBarang = $stmt->get_result()->fetch_assoc();
    $editMode = true;
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST["id"];
    $nama_barang = $_POST["nama_barang"];
    $errorMessage = "";

    if (!empty($id) && !empty($nama_barang)) {
        // Update barang
        $sql = "UPDATE barang SET nama_barang=? WHERE id=?";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("si", $nama_barang, $id);

        if ($stmt->execute()) {
            $_SESSION['update_success'] = true; 
            header("Location: /inventori/barang.php");
            exit;
        } else {
            $errorMessage = "Error updating barang: " . $stmt->error;
        }
    } else {
        $errorMessage = "Semua field harus diisi.";
    }

    if (!empty($errorMessage)) {
        echo "<div class='alert alert-danger'>$errorMessage</div>";
    }
}

// SweetAlert untuk notifikasi update barang
if (isset($_SESSION['update_success'])): ?>
<script>
    function showUpdateAlert() {
        Swal.fire({
            title: 'Berhasil!',
            text: 'Barang berhasil diupdate.',
            icon: 'success',
            showConfirmButton: false,
            timer: 1500
        });
    }

    window.onload = showUpdateAlert;
</script>
<?php unset($_SESSION['update_success']); ?>
<?php endif; ?>

<?php
// Menghitung total data untuk pagination
$sql_count = "SELECT COUNT(*) as total FROM barang LEFT JOIN barang_kategori bk ON barang.kategori_id = bk.id";
$conditions = [];
$parameters = [];
$types = "";

// Jika ada kondisi pencarian
if (!empty($searchTerm)) {
    $conditions[] = "(barang.kode_barang LIKE ? OR barang.nama_barang LIKE ? OR bk.nama_kategori LIKE ?)";
    $parameters[] = $searchTermParam;
    $parameters[] = $searchTermParam;
    $parameters[] = $searchTermParam;
    $types .= "sss";
}

if (!empty($kategori_id)) {
    $conditions[] = "barang.kategori_id = ?";
    $parameters[] = $kategori_id;
    $types .= "i";
}

if (!empty($conditions)) {
    $sql_count .= " WHERE " . implode(" AND ", $conditions);
}

$stmt_count = $connection->prepare($sql_count);
if (!empty($types)) {
    $stmt_count->bind_param($types, ...$parameters);
}
$stmt_count->execute();
$totalData = $stmt_count->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalData / $items_per_page);
?>

<?php
require "./header.php";
?>

<!-- Include SweetAlert CSS and JS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>

<!-- Navbar -->
<?php require "./navbar.php"; ?>

<div class="container my-2">
    <h2><?php echo $editMode ? "Edit Barang" : "LIST Barang"; ?></h2>
    <?php if (!$editMode): ?>
        <a style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="btn btn-primary" href="/inventori/create.php" role="button">New Barang</a>
        <br><br>

        <!-- Form Pencarian -->
        <form method="get" action="">
            <div class="input-group mb-2">
                <input  style="max-width: fit-content;" type="text" class="form-control" placeholder="Search Kode Barang or Nama Barang" name="search" id="search" value="<?php echo htmlspecialchars($searchTerm); ?>">
                <button style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" style="max-width: fit-content;" class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
            </div>
        </form>

        <!-- Tabel Barang -->
        <table class="table table-striped table-bordered" style="max-width: fit-content;">
            <thead >
                <tr >
                    <th style="background-color:cornflowerblue;">No.</th>
                    <th style="background-color:cornflowerblue;">Kode Barang</th>
                    <th style="background-color:cornflowerblue;">Nama Barang</th>
                    <th style="background-color:cornflowerblue;">Kategori</th>
                    <th style="background-color:cornflowerblue;">Create Date</th>
                    <th style="background-color:cornflowerblue;">Update Date</th>
                    <th style="background-color:cornflowerblue;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    $no = $offset + 1;
                    while ($row = $result->fetch_assoc()) {
                        echo "
                        <tr id='barang{$row['id']}'>
                            <td>{$no}.</td>
                            <td>{$row['kode_barang']}</td>
                            <td>{$row['nama_barang']}</td>
                            <td style='text-align:center;'>{$row['kategori']}</td>
                            <td>{$row['createdate']}</td>
                            <td>{$row['update_date']}</td>
                            <td>
                                <a style='box-shadow: 2px 2px 2px rgba(0,0,0,5);' class='btn btn-primary btn-sm' href='/inventori/barang.php?edit_id={$row['id']}'><i class='fas fa-pencil-alt'></i></a>
                                <a style='box-shadow: 2px 2px 2px rgba(0,0,0,5);' class='btn btn-danger btn-sm' href='/inventori/barang.php?delete_id={$row['id']}' onclick=\"event.preventDefault(); deleteBarang({$row['id']});\"> <i class='fas fa-trash'></i> </a>
                            </td>
                        </tr>";
                        $no++;
                    }
                } else {
                    echo "<tr><td colspan='7'>No records found</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <nav>
            <ul class="pagination">
                <?php if ($current_page > 1): ?>
                    <li class="page-item">
                        <a style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="page-link" href="?page=<?php echo $current_page - 1; ?>&search=<?php echo htmlspecialchars($searchTerm); ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a></li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                        <a style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo htmlspecialchars($searchTerm); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($current_page < $totalPages): ?>
                    <li class="page-item">
                        <a style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="page-link" href="?page=<?php echo $current_page + 1; ?>&search=<?php echo htmlspecialchars($searchTerm); ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a></li>
                <?php endif; ?>
            </ul>
        </nav>

    <?php else: ?>
        <!-- Form Edit Barang -->
        <form method="post" action="">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($editBarang['id']); ?>">
            <div class="mb-3">
                <label for="nama_barang" class="form-label">Nama Barang</label>
                <input type="text" class="form-control" name="nama_barang" id="nama_barang" value="<?php echo htmlspecialchars($editBarang['nama_barang']); ?>" required>
            </div>
            <button style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" type="submit" class="btn btn-primary">Update Barang</button>
            <a style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" sty href="/inventori/barang.php" class="btn btn-outline-primary">Batal</a>
        </form>
    <?php endif; ?>
</div>

<script>
function deleteBarang(id) {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Anda tidak dapat mengembalikan data ini!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/inventori/barang.php?delete_id=${id}`, {
                method: 'GET'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Dihapus!',
                        text: data.message,
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        // Menghapus baris barang dari tabel setelah berhasil dihapus
                        const row = document.getElementById('barang' + id);
                        if (row) {
                            row.remove();
                        }
                    });
                } else {
                    Swal.fire('Error!', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error!', 'Terjadi kesalahan saat menghapus.', 'error');
            });
        }
    });
}
</script>


<?php
require "./foother.php";
?>
