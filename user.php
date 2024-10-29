<?php
session_start();
require "./conn.php";

// Fungsi untuk menghapus data user berdasarkan id
if (isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    $sql = "DELETE FROM user WHERE id = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("i", $delete_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting user: ' . $connection->error]);
    }

    $stmt->close();
    exit;
}

// Fungsi untuk mencari data user
$searchTerm = "";
if (isset($_GET['search'])) {
    $searchTerm = $_GET['search'];
}

// Pagination variables
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$sql = "SELECT id, username FROM user";
if (!empty($searchTerm)) {
    $sql .= " WHERE username LIKE ?";
}

$stmt = $connection->prepare($sql);
if (!empty($searchTerm)) {
    $searchTermParam = "%$searchTerm%";
    $stmt->bind_param("s", $searchTermParam);
}
$stmt->execute();
$result = $stmt->get_result();

// Get total number of users for pagination
$totalUsers = $result->num_rows;

// Fetch the actual limited results
$sql .= " LIMIT ?, ?";
$stmt = $connection->prepare($sql);
if (!empty($searchTerm)) {
    $stmt->bind_param("ssi", $searchTermParam, $offset, $limit);
} else {
    $stmt->bind_param("ii", $offset, $limit);
}
$stmt->execute();
$paginatedResult = $stmt->get_result();

// Fungsi untuk mengedit data user berdasarkan id
$editMode = false;
$editUser = null;
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $sql = "SELECT id, username FROM user WHERE id = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $editUser = $stmt->get_result()->fetch_assoc();
    $editMode = true;
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['delete_id'])) {
    $username = $_POST["username"];
    $old_username = $_POST["old_username"];
    $errorMessage = "";

    // Validasi bahwa username tidak boleh kosong
    if (!empty($username)) {
        // Cek apakah username yang baru sudah ada di database
        $sql = "SELECT * FROM user WHERE username = ?";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        // Jika username sudah ada di database dan berbeda dari username lama
        if ($result->num_rows > 0 && $username != $old_username) {
            $errorMessage = "Username sudah ada. Silakan pilih username lain.";
        } else {
            // Lakukan update username jika validasi berhasil
            $sql = "UPDATE user SET username=? WHERE username=?";
            $stmt = $connection->prepare($sql);
            $stmt->bind_param("ss", $username, $old_username);

            if ($stmt->execute()) {
                // Set session untuk SweetAlert sukses
                $_SESSION['update_success'] = true;
                header("Location: /inventori/user.php"); // Redirect setelah update sukses
                exit;
            } else {
                $errorMessage = "Kesalahan saat mengupdate pengguna: " . $stmt->error;
            }
        }
    } else {
        $errorMessage = "Semua field wajib diisi.";
    }

    // Menampilkan pesan error jika ada
    if (!empty($errorMessage)) {
        $_SESSION['update_error'] = $errorMessage; // Simpan pesan error di session
        header("Location: /inventori/user.php?username=" . urlencode($old_username)); // Redirect kembali ke halaman edit dengan error
        exit;
    }
}
?>
<?php require "./header.php"; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php require "./navbar.php"; ?>

<div class="container my-2">
    <h2><?php echo $editMode ? "Edit User" : "List of Users"; ?></h2>
    <?php if (!$editMode): ?>
        <a style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="btn btn-primary" href="/inventori/createuser.php" role="button">New User</a>
        <br><br>
        <form method="get" action="">
            <div class="input-group mb-3">
                <input type="text"  style="max-width: fit-content;" class="form-control" placeholder="Search by username" name="search" id="search" value="<?php echo htmlspecialchars($searchTerm); ?>">
                <button style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
            </div>
        </form>
        <table style="max-width: fit-content;" class="table table-striped table-bordered">
            <thead>
                <tr style="width: 50%; height: 40%">
                    <th style="background-color:cornflowerblue;">No.</th>
                    <th style="background-color:cornflowerblue;">Username</th>
                    <th style="background-color:cornflowerblue;">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php
            if ($paginatedResult->num_rows > 0) {
                $no = $offset + 1;
                while ($row = $paginatedResult->fetch_assoc()) {
                    echo "
                    <tr id='user{$row['id']}'>
                        <td>{$no}.</td>
                        <td>{$row['username']}</td>
                        <td class='text-center'>
                            <a style='box-shadow: 2px 2px 2px rgba(0,0,0,0.5);' class='btn btn-danger btn-sm' href='/inventori/user.php?delete_id={$row['id']}' onclick=\"event.preventDefault(); deleteUser({$row['id']});\">
                                <i class='fas fa-trash'></i>
                            </a>
                        </td>
                    </tr>
                    ";
                    $no++;
                }
            } else {
                echo "<tr><td colspan='3' class='text-center'>No users found</td></tr>";
            }
            ?>
            </tbody>
        </table>

        <!-- Pagination links -->
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($searchTerm) ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                    </li>
                <?php endif; ?>
                <?php
                $totalPages = ceil($totalUsers / $limit);
                for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                        <a style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($searchTerm) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($searchTerm) ?>" aria-label="Previous">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php else: ?>
        <form method="post" action="">
            <input type="hidden" name="old_username" value="<?php echo $editUser['username']; ?>">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($editUser['username']); ?>" required>
            </div>
            <button style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" type="submit" class="btn btn-primary" name="update">Update</button>
            <a href="/inventori/stock.php" style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="btn btn-outline-primary">Cancel</a>
        </form>
    <?php endif; ?>
</div>

<script>
    <?php if (isset($_SESSION['update_success'])): ?>
        Swal.fire({
            title: 'Berhasil!',
            text: 'User berhasil diupdate.',
            icon: 'success',
            showConfirmButton: false,
            timer: 1500
        });
        <?php unset($_SESSION['update_success']); ?> // Hapus session setelah digunakan
    <?php endif; ?>

    function deleteUser(id) {
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
                fetch('/inventori/user.php', {
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
                            text: data.message,
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            document.getElementById('user' + id).remove();
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
