<?php
require "./conn.php";

// Memulai sesi
session_start();

// Pastikan pengguna sudah login
if (!isset($_SESSION['session_username'])) {
    header("location:/inventori/index.php");
    exit();
}

// Fungsi untuk mencari data
$searchTerm = "";
if (isset($_GET['search'])) {
    $searchTerm = trim($_GET['search']); // Membersihkan input
}

// Menentukan jumlah entri per halaman dan halaman saat ini
$items_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Fetch data for the table based on search term
$sql_barang = "SELECT pb.id, sd.kode_stock, b.nama_barang AS nama_barang, 
sd.deskripsi, pb.nama_pengambil, l.nama_lokasi, pb.status, pb.tanggal_pengembalian, 
k.nama_kategori
FROM pengambilan_barang pb 
INNER JOIN stock_detail sd ON pb.stock_id_detail = sd.id 
INNER JOIN barang b ON sd.barang_id = b.id
INNER JOIN barang_kategori k ON pb.kategori_id = k.id 
INNER JOIN lokasi l ON pb.lokasi_id = l.id";

$search_query = [];
if (!empty($searchTerm)) {
    $sql_barang .= " WHERE b.nama_barang LIKE ? OR pb.nama_pengambil LIKE ? OR sd.kode_stock LIKE ? 
                     OR k.nama_kategori LIKE ? OR l.nama_lokasi LIKE ?";
    $search_query[] = "%$searchTerm%"; // Nama Barang
    $search_query[] = "%$searchTerm%"; // Nama Pengambil
    $search_query[] = "%$searchTerm%"; // Kode Stock
    $search_query[] = "%$searchTerm%"; // Kategori
    $search_query[] = "%$searchTerm%"; // Lokasi
}

// Tambahkan pengurutan dan limit
$sql_barang .= " ORDER BY pb.status = 'Dipinjam' DESC, pb.tanggal_pengembalian DESC, 
                pb.date DESC LIMIT ?, ?";

$stmt_barang = $connection->prepare($sql_barang);

// Mengikat parameter untuk pencarian
if (!empty($search_query)) {
    // Menggabungkan semua parameter pencarian
    $stmt_barang->bind_param("sssssii", 
        $search_query[0], 
        $search_query[1], 
        $search_query[2], 
        $search_query[3], 
        $search_query[4], 
        $offset, 
        $items_per_page
    );
} else {
    $stmt_barang->bind_param("ii", $offset, $items_per_page);
}

$stmt_barang->execute();
$result_barang = $stmt_barang->get_result();

// Dapatkan total jumlah barang untuk pagination
$sql_count = "SELECT COUNT(*) AS total FROM pengambilan_barang pb 
INNER JOIN stock_detail sd ON pb.stock_id_detail = sd.id 
INNER JOIN barang b ON sd.barang_id = b.id 
INNER JOIN barang_kategori k ON b.kategori_id = k.id 
INNER JOIN lokasi l ON sd.lokasi_id = l.id";

if (!empty($searchTerm)) {
    $sql_count .= " WHERE b.nama_barang LIKE ? OR pb.nama_pengambil LIKE ? OR sd.kode_stock LIKE ? 
                    OR k.nama_kategori LIKE ? OR l.nama_lokasi LIKE ?";
}

$stmt_count = $connection->prepare($sql_count);

if (!empty($searchTerm)) {
    $searchTermParam = "%$searchTerm%"; // Wildcard untuk pencarian
    $stmt_count->bind_param("sssss", $searchTermParam, $searchTermParam, $searchTermParam, $searchTermParam, $searchTermParam);
}

$stmt_count->execute();
$result_count = $stmt_count->get_result();
$total_items = $result_count->fetch_assoc()['total'];
$total_pages = ceil($total_items / $items_per_page);

// Proses pengembalian barang
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_id'])) {
    $ambil_barang_id = intval($_POST['return_id']); // Sanitasi input ID

    // Ambil data pengambilan barang
    $sql = "SELECT pb.stock_id_detail, pb.status FROM pengambilan_barang pb WHERE pb.id = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("i", $ambil_barang_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stock_id_detail = $row['stock_id_detail'];
        $current_status = $row['status'];

        // Proses pengembalian barang
        if ($current_status != 'dikembalikan') {
            // Update status pengembalian
            $sql_update_status = "UPDATE pengambilan_barang SET status = 'dikembalikan' WHERE id = ?";
            $stmt_update_status = $connection->prepare($sql_update_status);
            $stmt_update_status->bind_param("i", $ambil_barang_id);
            $stmt_update_status->execute();

            // Update status stock detail menjadi active
            $sql_update_stock = "UPDATE stock_detail SET status = 'active' WHERE id = ?";
            $stmt_update_stock = $connection->prepare($sql_update_stock);
            $stmt_update_stock->bind_param("i", $stock_id_detail);
            $stmt_update_stock->execute();

            // Kirim respons JSON
            echo json_encode(['success' => true]);
            exit();
        }
    }
    // Jika tidak berhasil, kirim pesan error
    echo json_encode(['success' => false, 'message' => 'Pengembalian gagal.']);
    exit();
}
?>

<?php require "./header.php"; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">


<!-- Navbar -->
<?php require "./navbar.php"; ?>

<div class="container my-2">
    <h2>List of Pengambilan Barang</h2>
    <a style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="btn btn-primary" href="/inventori/createambil.php" role="button"><i class="fas fa-plus"></i>New Barang</a>
    <br><br>

    <!-- Form pencarian -->
    <form method="get" action="">
        <div class="input-group mb-3">
            <input style="max-width: fit-content;" type="text" class="form-control" placeholder="Search by Barang, Pengambil, Kode, Kategori, Lokasi" name="search" id="search" value="<?php echo htmlspecialchars($searchTerm); ?>">
            <button style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
        </div>
    </form>

    <!-- Menampilkan daftar pengambilan barang -->
    <table class="table table-striped table-bordered table-sm">
        <thead>
            <tr ">
                <th style="text-align:center; background-color:cornflowerblue; font-family:roboto;">No.</th>
                <th style="text-align:center; background-color:cornflowerblue; font-family:roboto;">Barang</th>
                <th style="text-align:center; background-color:cornflowerblue; font-family:roboto;">Kode</th>
                <th style="text-align:center; background-color:cornflowerblue; font-family:roboto;">Deskripsi</th>
                <th style="text-align:center; background-color:cornflowerblue; font-family:roboto;">Pengambil</th>
                <th style="text-align:center; background-color:cornflowerblue; font-family:roboto;">Lokasi</th>
                <th style="text-align:center; background-color:cornflowerblue; font-family:roboto;">Kategori</th>
                <th style="text-align:center; background-color:cornflowerblue; font-family:roboto;">Status</th>
                <th style="text-align:center; background-color:cornflowerblue; font-family:roboto;">Dikembalikan</th>
                <th style="text-align:center; background-color:cornflowerblue; font-family:roboto;">Actions</th>
            </tr>
        </thead><tbody>
<?php
if ($result_barang->num_rows > 0) {
    $no = $offset + 1; // Menghitung nomor berdasarkan offset
    while ($row = $result_barang->fetch_assoc()) {
        echo "<tr id='row-{$row['id']}'>
                <td style='text-align:center;'>{$no}.</td>
                <td>" . htmlspecialchars($row['nama_barang']) . "</td>
                <td>" . htmlspecialchars($row['kode_stock']) . "</td>
                <td style='text-align:center;'>" . htmlspecialchars($row['deskripsi']) . "</td>
                <td style='text-align:center;'>" . htmlspecialchars($row['nama_pengambil']) . "</td>
                <td style='text-align:center;'>" . htmlspecialchars($row['nama_lokasi']) . "</td>
                <td style='text-align:center;'>" . htmlspecialchars($row['nama_kategori']) . "</td>
                <td >";

        // Status badge berdasarkan status barang
        
        if ($row['status'] === 'dikembalikan') {
            echo "<span class='badge bg-success d-flex justify-content-center align-items-center' style='display: inline-flex; align-items: center; justify-content: center; width: 85px;'>Dikembalikan</span>";
        } elseif ($row['status'] === 'Dipinjam') {
            echo "<span class='badge bg-danger d-flex justify-content-center align-items-center' style='width: 60px;'>Dipinjam</span>";
        } else {
            echo "<span class='badge bg-secondary d-flex justify-content-center align-items-center' style='width: 100px;'>" . htmlspecialchars($row['status']) . "</span>";
        }
        echo "</td>
        <td style='text-align:center'>" . htmlspecialchars($row['tanggal_pengembalian']) . "</td>
        <td>";

        // Tampilkan tombol Kembalikan hanya jika barang belum dikembalikan
       // Tampilkan tombol Kembalikan hanya jika barang belum dikembalikan
        if ($row['status'] === 'Dipinjam') {
            echo "<div style='text-align: center;'>
            <button style='box-shadow: 2px 2px 2px rgba(0,0,0,0.5); font-size:90%; display: inline-flex; align-items: center; justify-content: center; height: 30px; width: 60px;' 
                    class='btn btn-primary return-button' 
                    role='button' 
                    data-id='{$row['id']}' 
                    data-nama='" . htmlspecialchars($row['nama_barang']) . "'>
                <i class='fas fa-undo' style='margin-right: 5px;'></i></button>
          </div>";
    
            } else {
                echo "<div style='display: flex; justify-content: center; align-items: center;'>
        <button class='center-button btn btn-secondary' style='pointer-events: none; cursor: default; display: flex; align-items: center; justify-content: center; width: 60px; height: 30px;'>
            <i class='fas fa-undo' style='margin-right: 5px;'></i>
        </button>
      </div>";
 // Menampilkan tombol tidak aktif
            }

        echo "</td></tr>";
        $no++;
    }
} else {
    echo "<tr><td colspan='10'>Tidak ada data ditemukan.</td></tr>";
}
?>
</tbody>

    </table>

    <!-- Pagination -->
        <!-- Pagination -->
        <nav aria-label="Page navigation">
        <ul class="pagination">
            <?php if ($current_page > 1): ?>
                <li class="page-item">
                    <a style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="page-link" href="?search=<?php echo urlencode($searchTerm); ?>&page=<?php echo $current_page - 1; ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                </li>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $current_page === $i ? 'active' : ''; ?>">
                    <a style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="page-link" href="?search=<?php echo urlencode($searchTerm); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($current_page < $total_pages): ?>
                <li class="page-item">
                    <a style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="page-link" href="?search=<?php echo urlencode($searchTerm); ?>&page=<?php echo $current_page + 1; ?>" aria-label="Previous">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const returnButtons = document.querySelectorAll('.return-button');
    returnButtons.forEach(button => {
        button.addEventListener('click', function() {
            const returnId = this.getAttribute('data-id');
            const namaBarang = this.getAttribute('data-nama');

            Swal.fire({
                title: 'Konfirmasi',
                text: `Apakah Anda yakin ingin mengembalikan ${namaBarang}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, kembalikan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('pengambilan_barang.php', { // Ganti dengan URL endpoint yang sesuai
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            'return_id': returnId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Tampilkan SweetAlert sukses dan auto-close
                            Swal.fire({
                                title: 'Berhasil!',
                                text: 'Barang telah dikembalikan.',
                                icon: 'success',
                                timer: 1000, // Setel timer untuk 1,5 detik
                                showConfirmButton: false // Sembunyikan tombol OK
                            });
                            document.getElementById('row-' + returnId).remove(); // Menghapus baris yang dikembalikan
                        } else {
                            Swal.fire('Gagal!', data.message || 'Pengembalian gagal.', 'error');
                        }
                    })
                    .catch(error => {
                        Swal.fire('Gagal!', 'Terjadi kesalahan. Silakan coba lagi.', 'error');
                    });
                }
            });
        });
    });
});
</script>
<?php require "./foother.php"; ?>
