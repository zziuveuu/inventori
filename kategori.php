<?php
require "./conn.php"; // Menghubungkan dengan file koneksi
session_start(); // Pastikan session dimulai

// Fungsi untuk menghapus data kategori
if (isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    $sql = "DELETE FROM barang_kategori WHERE id = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus kategori']);
    }
    $stmt->close();
    exit;
}

// Variabel untuk mengedit kategori
$editKategori = null;

// Memproses pembaruan kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['nama_kategori'])) {
    $id = $_POST['id'];
    $nama_kategori = $_POST['nama_kategori'];

    $update_sql = "UPDATE barang_kategori SET nama_kategori = ? WHERE id = ?";
    $update_stmt = $connection->prepare($update_sql);
    $update_stmt->bind_param("si", $nama_kategori, $id);
    
    if ($update_stmt->execute()) {
        // Set session untuk SweetAlert
        $_SESSION['update_success'] = true; // Tambahkan session untuk notifikasi
        header("Location: kategori.php");
        exit;
    } else {
        echo "<script>alert('Gagal memperbarui kategori.');</script>";
    }

    $update_stmt->close();
}

// Fungsi untuk mencari dan menampilkan data kategori
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$searchTerm = "";
if (isset($_GET['search'])) {
    $searchTerm = $_GET['search'];
}

$sql = "SELECT id, nama_kategori FROM barang_kategori";
if (!empty($searchTerm)) {
    $sql .= " WHERE nama_kategori LIKE ? OR id LIKE ?";
}
$sql .= " ORDER BY id DESC LIMIT ? OFFSET ?";

$stmt = $connection->prepare($sql);
if (!empty($searchTerm)) {
    $searchTermParam = "%$searchTerm%";
    $stmt->bind_param("ssii", $searchTermParam, $searchTermParam, $limit, $offset);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$result = $stmt->get_result();

// Menghitung total data untuk paginasi
$count_sql = "SELECT COUNT(*) as total FROM barang_kategori";
if (!empty($searchTerm)) {
    $count_sql .= " WHERE nama_kategori LIKE ? OR id LIKE ?";
    $count_stmt = $connection->prepare($count_sql);
    $count_stmt->bind_param("ss", $searchTermParam, $searchTermParam);
} else {
    $count_stmt = $connection->prepare($count_sql);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Menutup koneksi
$stmt->close();
$count_stmt->close();

// Menangani mode edit
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $edit_sql = "SELECT id, nama_kategori FROM barang_kategori WHERE id = ?";
    $edit_stmt = $connection->prepare($edit_sql);
    $edit_stmt->bind_param("i", $edit_id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result();

    if ($edit_result->num_rows > 0) {
        $editKategori = $edit_result->fetch_assoc();
    }
    $edit_stmt->close();
}

// SweetAlert untuk notifikasi update kategori
?>
<?php require "./header.php"; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php require "./navbar.php"; ?>

<div class="container my-5">
    <h2><?php echo $editKategori ? "Edit Kategori" : "List of Kategori"; ?></h2>

    <?php if (!$editKategori): ?>
        <a style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="btn btn-primary" href="/inventori/createkategori.php" role="button">New Kategori</a>
        <br><br>

        <form method="get" action="">
            <div class="input-group mb-3" style="max-width:fit-content;">
                <input class="form-control" placeholder="Search by ID or Nama Kategori" name="search" id="search" value="<?php echo htmlspecialchars($searchTerm); ?>">
                <button style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="btn btn-outline-secondary" type="submit">
                    <i class="fas fa-search"></i> <!-- Ikon pencarian -->
                </button>
            </div>
        </form>

        <table class="table table-striped table-bordered" style="max-width:fit-content;">
            <thead>
                <tr>
                    <th style="background-color:cornflowerblue;">No.</th>
                    <th style="background-color:cornflowerblue;">Nama Kategori</th>
                    <th style="background-color:cornflowerblue;">Actions</th>
                </tr>
            </thead>
            <tbody id="kategori-list">
                <?php
                if ($result->num_rows > 0) {
                    $no = $offset + 1;
                    while ($row = $result->fetch_assoc()) {
                        echo "
                        <tr id='kategori-{$row['id']}'>
                            <td>{$no}.</td>
                            <td>{$row['nama_kategori']}</td>
                            <td>
                                <a style='box-shadow: 2px 2px 2px rgba(0,0,0,5);' class='btn btn-primary btn-sm' href='/inventori/kategori.php?edit_id={$row['id']}'><i class='fas fa-pencil-alt'></i></a>
                                <a style='box-shadow: 2px 2px 2px rgba(0,0,0,5);' class='btn btn-danger btn-sm' href='#' onclick=\"event.preventDefault(); deleteBarangKategori({$row['id']});\"> 
                                    <i class='fas fa-trash'></i> 
                                </a>
                            </td>
                        </tr>
                        ";
                        $no += 1;
                    }
                } else {
                    echo "<tr><td colspan='3' class='text-center'>No kategori found</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Paginasi -->
        <nav>
            <ul class="pagination">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo htmlspecialchars($searchTerm); ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo htmlspecialchars($searchTerm); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo htmlspecialchars($searchTerm); ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php else: ?>
        <!-- Form Edit Kategori -->
        <form method="post" action="">
            <input type="hidden" name="id" value="<?php echo $editKategori['id']; ?>">
            <div class="mb-3">
                <label for="nama_kategori" class="form-label">Nama Kategori</label>
                <input type="text" class="form-control" id="nama_kategori" name="nama_kategori" value="<?php echo htmlspecialchars($editKategori['nama_kategori']); ?>" required>
            </div>
            <button style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" type="submit" class="btn btn-primary">Update Kategori</button>
        </form>
    <?php endif; ?>
</div>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Menampilkan SweetAlert saat berhasil memperbarui kategori
<?php if (isset($_SESSION['update_success'])): ?>
    Swal.fire({
        title: 'Berhasil!',
        text: 'Kategori berhasil diupdate.',
        icon: 'success',
        showConfirmButton: false,
        timer: 1500
    });
    <?php unset($_SESSION['update_success']); ?>
<?php endif; ?>   
function deleteBarangKategori(id) {
    Swal.fire({
        title: 'Anda yakin?',
        text: "Anda tidak dapat mengembalikan data yang telah dihapus!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, hapus!'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('/inventori/kategori.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'delete_id=' + id
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Dihapus!',
                        text: 'Kategori berhasil dihapus.',
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        document.getElementById('kategori-' + id).remove();
                    });
                } else {
                    Swal.fire('Error!', data.message, 'error');
                }
            })
            .catch(error => Swal.fire('Error!', 'Terjadi kesalahan saat menghapus.', 'error'));
        }
    });
}
</script>

<?php require "./foother.php"; ?>
