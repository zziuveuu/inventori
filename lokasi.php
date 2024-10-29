<?php
require "./conn.php"; // Menghubungkan dengan file koneksi
session_start(); // Pastikan session dimulai

// Tentukan limit dan halaman saat ini
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fungsi untuk menghapus data lokasi
if (isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    $sql = "DELETE FROM lokasi WHERE id = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("i", $delete_id);

    if ($stmt->execute()) {
        $_SESSION['delete_success'] = true; // Set session untuk notifikasi hapus
    } else {
        $_SESSION['delete_error'] = "Error deleting lokasi: " . $connection->error;
    }

    $stmt->close();
    header("Location: lokasi.php"); // Redirect setelah menghapus
    exit;
}

// Fungsi untuk mencari data lokasi
$searchTerm = "";
if (isset($_GET['search'])) {
    $searchTerm = $_GET['search'];
}
$sql = "SELECT id, nama_lokasi FROM lokasi";

if (!empty($searchTerm)) {
    $sql .= " WHERE nama_lokasi LIKE ? OR id LIKE ?";
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

// Menghitung total jumlah data untuk keperluan paginasi
$count_sql = "SELECT COUNT(*) as total FROM lokasi";
if (!empty($searchTerm)) {
    $count_sql .= " WHERE nama_lokasi LIKE ? OR id LIKE ?";
    $count_stmt = $connection->prepare($count_sql);
    $count_stmt->bind_param("ss", $searchTermParam, $searchTermParam);
} else {
    $count_stmt = $connection->prepare($count_sql);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Fungsi untuk mengedit data lokasi berdasarkan ID
$editMode = false;
$editLokasi = null;
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $sql = "SELECT id, nama_lokasi FROM lokasi WHERE id = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $editLokasi = $stmt->get_result()->fetch_assoc();
    $editMode = true;
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['nama_lokasi'])) {
    $id = $_POST['id'];
    $nama_lokasi = $_POST['nama_lokasi'];

    $sql = "SELECT * FROM lokasi WHERE nama_lokasi = ? AND id != ?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("si", $nama_lokasi, $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['update_error'] = "Nama lokasi sudah ada. Silakan pilih yang lain.";
    } else {
        $update_sql = "UPDATE lokasi SET nama_lokasi = ? WHERE id = ?";
        $update_stmt = $connection->prepare($update_sql);
        $update_stmt->bind_param("si", $nama_lokasi, $id);

        if ($update_stmt->execute()) {
            $_SESSION['update_success'] = true; // Set session untuk notifikasi update
            header("Location: lokasi.php");
            exit;
        } else {
            $_SESSION['update_error'] = "Error updating lokasi: " . $update_stmt->error;
        }

        $update_stmt->close();
    }
}
?>

<?php require "./header.php"; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<?php require "./navbar.php"; ?>

<div class="container my-5">
    <h2><?php echo $editMode ? "Edit Lokasi" : "List of Lokasi"; ?></h2>

    <?php if (!$editMode): ?>
        <a style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="btn btn-primary" href="/inventori/createlokasi.php" role="button">New Lokasi</a>
        <br><br>
        <form method="get" action="">
            <div class="input-group mb-3" style="max-width:fit-content;">
                <input type="text" class="form-control" placeholder="Search by ID or Nama Lokasi" name="search" id="search" value="<?php echo htmlspecialchars($searchTerm); ?>">
                <button style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
            </div>
        </form>

        <table class="table table-striped table-bordered" style="max-width: fit-content;">
            <thead>
                <tr>
                    <th style="background-color:cornflowerblue;">No.</th>
                    <th style="background-color:cornflowerblue;">Nama Lokasi</th>
                    <th style="background-color:cornflowerblue;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    $no = $offset + 1;
                    while ($row = $result->fetch_assoc()) {
                        echo "
                        <tr>
                            <td>{$no}.</td>
                            <td>{$row['nama_lokasi']}</td>
                            <td>
                                <a style='box-shadow: 2px 2px 2px rgba(0,0,0,5);' class='btn btn-primary btn-sm' href='/inventori/lokasi.php?edit_id={$row['id']}'><i class='fas fa-pencil-alt'></i></a>
                                <button type='button' onclick='deleteLokasi({$row['id']})' style='box-shadow: 2px 2px 2px rgba(0,0,0,5);' class='btn btn-danger btn-sm'>
                                    <i class='fas fa-trash'></i>
                                </button>
                            </td>
                        </tr>
                        ";
                        $no += 1;
                    }
                } else {
                    echo "<tr><td colspan='3' class='text-center'>No lokasi found</td></tr>";
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
        <form method="post" id="addItemForm">
            <input type="hidden" name="id" value="<?php echo $editLokasi['id']; ?>">
            <div class="mb-3">
                <label for="nama_lokasi" class="form-label">Nama Lokasi</label>
                <input type="text" class="form-control" id="nama_lokasi" name="nama_lokasi" value="<?php echo htmlspecialchars($editLokasi['nama_lokasi']); ?>" required>
            </div>
            <button type="submit" style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="btn btn-primary">Update</button>
            <a style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="btn btn-secondary" href="/inventori/lokasi.php">Cancel</a>
        </form>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function deleteLokasi(id) {
    Swal.fire({
        title: 'Menghapus lokasi...',
        text: "Anda tidak dapat mengembalikan data yang telah dihapus!",
        icon: 'warning',
        confirmButtonColor: '#d33',
        showCancelButton: false,
        showConfirmButton: false, // Menghilangkan tombol OK
        timer: 1500, // Menutup otomatis setelah 1.5 detik
        willClose: () => {
            // Eksekusi penghapusan setelah timer selesai
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/inventori/lokasi.php'; // URL untuk penghapusan

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'delete_id';
            input.value = id; // Set nilai delete_id

            form.appendChild(input);
            document.body.appendChild(form);
            form.submit(); // Kirim form
        }
    });
}
</script>

<?php
if (isset($_SESSION['delete_success'])) {
    echo "<script>Swal.fire('Berhasil!', 'Data lokasi telah dihapus.', 'success');</script>";
    unset($_SESSION['delete_success']);
}

if (isset($_SESSION['delete_error'])) {
    echo "<script>Swal.fire('Error!', 'Gagal menghapus lokasi: {$_SESSION['delete_error']}', 'error');</script>";
    unset($_SESSION['delete_error']);
}

if (isset($_SESSION['update_success'])) {
    echo "<script>
        Swal.fire({
            title: 'Berhasil!',
            text: 'Data lokasi telah diperbarui.',
            icon: 'success',
            timer: 1500, // Menutup otomatis setelah 1.5 detik
            timerProgressBar: true,
            showConfirmButton: false // Tidak ada tombol OK
        });
    </script>";
    unset($_SESSION['update_success']);
}

if (isset($_SESSION['update_error'])) {
    echo "<script>Swal.fire('Error!', '{$_SESSION['update_error']}', 'error');</script>";
    unset($_SESSION['update_error']);
}
?>

<?php require "./foother.php"; ?>
