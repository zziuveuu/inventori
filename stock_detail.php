<?php 
require './conn.php';
session_start();

// Pastikan pengguna sudah login
if (!isset($_SESSION['session_username'])) {
    header("location:/inventori/index.php");
    exit();
}

// Ambil stock_id dari parameter URL
$stock_id = isset($_GET['stock_id']) ? $_GET['stock_id'] : null;

// Jika stock_id tidak ada, redirect kembali
if ($stock_id === null) {
    header("Location: stock.php");
    exit();
}

// Ambil waktu saat ini
date_default_timezone_set('Asia/Jakarta');
$update_date = date('Y-m-d H:i:s');

// Ambil search term jika ada
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// Tentukan jumlah item per halaman
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Query utama
$sql = "SELECT sd.id, sd.stock_id, sd.barang_id, sd.kode_stock, sd.status, 
               sd.create_date, sd.create_by, b.nama_barang, sd.deskripsi, 
               l.nama_lokasi, sd.keterangan  
        FROM stock_detail sd
        JOIN barang b ON sd.barang_id = b.id 
        JOIN lokasi l ON sd.lokasi_id = l.id
        WHERE sd.stock_id = ?";

// Tambahkan kondisi pencarian jika ada istilah pencarian
if (!empty($searchTerm)) {
    $sql .= " AND (sd.kode_stock LIKE ? OR l.nama_lokasi LIKE ? OR sd.deskripsi LIKE ?)";
}

// Tambahkan pagination
$sql .= " LIMIT ? OFFSET ?";

$stmt = $connection->prepare($sql);
if ($stmt === false) {
    die('Error preparing statement: ' . $connection->error);
}

// Bind parameter untuk pencarian
if (!empty($searchTerm)) {
    $searchTermParam = "%$searchTerm%"; 
    $stmt->bind_param("isssii", $stock_id, $searchTermParam, $searchTermParam, $searchTermParam, $limit, $offset);
} else {
    $stmt->bind_param("iii", $stock_id, $limit, $offset);
}

$stmt->execute();
$result = $stmt->get_result();

// Ambil jumlah total data untuk pagination
$sql_count = "SELECT COUNT(*) as total FROM stock_detail WHERE stock_id = ?";
if (!empty($searchTerm)) {
    $sql_count .= " AND (kode_stock LIKE ? OR deskripsi LIKE ?)";
}
$stmt_count = $connection->prepare($sql_count);
if ($stmt_count === false) {
    die('Error preparing statement: ' . $connection->error);
}

if (!empty($searchTerm)) {
    $stmt_count->bind_param("iss", $stock_id, $searchTermParam, $searchTermParam);
} else {
    $stmt_count->bind_param("i", $stock_id);
}

$stmt_count->execute();
$count_result = $stmt_count->get_result();
$total_data = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_data / $limit);

// Ambil data lokasi dari tabel lokasi untuk select option
$sql_lokasi = "SELECT id, nama_lokasi FROM lokasi";
$result_lokasi = $connection->query($sql_lokasi);
?>

<?php require "./header.php"; ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<!-- Navbar -->
<?php require "./navbar.php"; ?>
<!-- Navbar -->

<div class="container my-5">
    <h2>Stock Detail</h2>

    <!-- Form Pencarian -->
    <form method="get" id="addItemForm">
        <input type="hidden" name="stock_id" value="<?php echo htmlspecialchars($stock_id); ?>">
        <div class="input-group mb-3" style="max-width: 838px;">
    <input style=" max-width:fit-content; " type="text" class="form-control" placeholder="Search Kode Barang OR lokasi OR Deskripsi" name="search" id="search" value="<?php echo htmlspecialchars($searchTerm); ?>">
    <button style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="btn btn-outline-secondary" type="submit">
        <i class="fas fa-search"></i>
    </button>
</div>

    </form>

    <!-- Tabel Stock Detail -->
    <table class="table table-striped table-bordered" style="width: 75%;">
        <thead>
            <tr style="background-color: #add8e6; height: 20px">
                <th style="background-color:cornflowerblue;">No</th>
                <th style="background-color:cornflowerblue;">Nama Barang</th>
                <th style="background-color:cornflowerblue;">Deskripsi</th>
                <th style="background-color:cornflowerblue;">Lokasi</th>
                <th style="background-color:cornflowerblue;">Kode Stock</th>
                <th style="background-color:cornflowerblue;">Status</th>
                <th style="background-color:cornflowerblue;">Keterangan</th>
                <th style="background-color:cornflowerblue;">Action</th>
            </tr>
        </thead>
        <tbody>
    <?php if ($result->num_rows > 0): ?>
        <?php $no = $offset + 1; while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $no; ?>.</td>
                <td><?= htmlspecialchars($row['nama_barang']); ?></td>
                <td><?= htmlspecialchars($row['deskripsi']); ?></td>
                <td><?= htmlspecialchars($row['nama_lokasi']); ?></td>
                <td><?= htmlspecialchars($row['kode_stock']); ?></td>
                <td>
                    <?php if ($row['status'] === 'active'): ?>
                        <span class="badge bg-success"><?= htmlspecialchars($row['status']); ?></span>
                    <?php elseif ($row['status'] === 'Dipinjam'): ?>
                        <span class="badge bg-danger"><?= htmlspecialchars($row['status']); ?></span>
                    <?php else: ?>
                        <span class="badge bg-secondary"><?= htmlspecialchars($row['status']); ?></span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($row['keterangan']); ?></td>
                <td>
    <?php if ($row['status'] !== 'Dipinjam'): ?>
        <button type="button" class="btn btn-link" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $row['id']; ?>" title="Edit Status"><i class='fas fa-pencil-alt'></i></a>
        </button>

        <!-- Modal untuk Edit Status -->
        <div class="modal fade" id="editModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel<?php echo $row['id']; ?>"><i class='fas fa-pencil-alt'></i> Edit Status</h5>
                        <button type="button" style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="updateForm<?php echo $row['id']; ?>" action="update_status.php" method="POST">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <input type="hidden" name="stock_id" value="<?php echo $stock_id; ?>">
                            <div class="mb-3">
                                <label for="status" class="form-label">Pilih Status</label>
                                <select style="border: 1px solid black; background-color: #f8f9fa; border-color: #ced4da; padding: 8px; font-size: 14px; color: #495057;" class="form-select" name="status" id="status">
                                    <option value="active" <?php if ($row['status'] == 'active') echo 'selected'; ?>>Active</option>
                                    <option value="none active" <?php if ($row['status'] == 'none active') echo 'selected'; ?>>none active</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="keterangan" class="form-label">Keterangan</label>
                                <textarea style="border: 1px solid black; background-color: #f8f9fa; border-color: #ced4da; padding: 8px; font-size: 14px; color: #495057;" class="form-control" name="keterangan" id="keterangan" rows="3"><?php echo htmlspecialchars($row['keterangan']); ?></textarea>
                            </div>
                            <div class="modal-footer">
                                <button type="button" style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <!-- Change this button to call confirmUpdate -->
                                <button type="submit" style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="btn btn-primary">Update Status</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <span class="text-muted">Tidak bisa diedit</span>
    <?php endif; ?>
</td>

            </tr>
            <?php $no++; endwhile; ?>    
    <?php else: ?>
        <tr>
            <td colspan="8" class="text-center">No data available</td>
        </tr>
    <?php endif; ?>
    </tbody>
    </table>

    <!-- Pagination -->
    <nav aria-label="Page navigation">
        <ul class="pagination">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?stock_id=<?php echo htmlspecialchars($stock_id); ?>&page=<?php echo $page - 1; ?>&search=<?php echo urlencode($searchTerm); ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                    <a class="page-link" href="?stock_id=<?php echo htmlspecialchars($stock_id); ?>&page=<?php echo $i; ?>&search=<?php echo urlencode($searchTerm); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?stock_id=<?php echo htmlspecialchars($stock_id); ?>&page=<?php echo $page + 1; ?>&search=<?php echo urlencode($searchTerm); ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Menampilkan konfirmasi dengan SweetAlert2 sebelum update status
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault(); // Mencegah pengiriman form secara default

            // Menampilkan SweetAlert2 untuk konfirmasi
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Perubahan status akan disimpan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, update!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Menampilkan pesan sukses setelah konfirmasi
                    Swal.fire({
                        title: 'Terkirim!',
                        text: 'Status berhasil diupdate.',
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 1100
                    }).then(() => {
                        form.submit(); // Submit form setelah SweetAlert sukses
                    });
                }
            });
        });
    });

    // Menampilkan pesan sukses jika redirect berhasil
    <?php if (isset($_GET['success'])): ?>
    Swal.fire({
        position: "top-end",
        icon: "success",
        title: "Data berhasil disimpan!",
        showConfirmButton: false,
        timer: 1100
    }).then(() => {
        window.location.href = '/inventori/stock_detail.php'; // Redirect setelah pesan sukses ditutup
    });
    <?php endif; ?>
</script>
<?php require "./foother.php"; ?>
