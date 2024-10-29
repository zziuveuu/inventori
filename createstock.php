<?php 
require "./conn.php"; // Menghubungkan dengan file koneksi
date_default_timezone_set('Asia/Jakarta');

// Mengecek koneksi
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

session_start(); // Memulai sesi untuk menggunakan variabel sesi

// Inisialisasi variabel
$barang_id = "";
$quantity = "";
$lokasi_id = "";
$deskripsi = ""; // Tambahkan inisialisasi untuk deskripsi
$created_by = "";
$searchTerm = "";
$errorMessage = "";
$successMessage = "";

// Fungsi untuk generate kode stock baru
function generateNewStockCode($lastCode)
{
    $prefix = substr($lastCode, 0, 1); // Bagian huruf 'S'
    $number = substr($lastCode, 1); // Bagian angka setelah 'S'
    $newNumber = (int)$number + 1;
    return $prefix . str_pad($newNumber, 6, "0", STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $barang_id = trim($_POST["barang_id"]);
    $quantity = trim($_POST["quantity"]);
    $lokasi_id = trim($_POST["nama_lokasi"]);
    $deskripsi = trim($_POST["deskripsi"]); // Tangkap input deskripsi
    $created_by = isset($_SESSION['session_username']) ? $_SESSION['session_username'] : null;

    // Validasi input
    if (empty($barang_id) || empty($quantity) || empty($lokasi_id) || empty($deskripsi)) {
        $errorMessage = "Semua field harus diisi.";
    } elseif (!is_numeric($quantity) || $quantity <= 0) {
        $errorMessage = "Quantity harus berupa angka dan lebih besar dari 0.";
    } elseif (empty($created_by)) {
        $errorMessage = "Pengguna tidak terautentikasi. Harap login terlebih dahulu.";
    } else {
        // Mengecek apakah barang sudah ada di stock (tidak mempertimbangkan lokasi)
        $sql = "SELECT * FROM stock WHERE barang_id = ?";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("s", $barang_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Jika barang sudah ada, tambahkan jumlah quantity
            $row = $result->fetch_assoc();
            $new_quantity = $row['quantity'] + $quantity;

            // Update quantity di database
            $sql_update = "UPDATE stock SET quantity = ? WHERE id = ?";
            $stmt_update = $connection->prepare($sql_update);
            $stmt_update->bind_param("ii", $new_quantity, $row['id']);
            if (!$stmt_update->execute()) {
                $errorMessage = "Error updating quantity: " . $connection->error;
            } else {
                // Set stock_id untuk digunakan pada stock_detail
                $stock_id = $row['id'];
                $successMessage = "Quantity barang berhasil ditambahkan.";
            }
        } else {
            // Jika barang belum ada, tambahkan sebagai entry baru
            $sql_insert = "INSERT INTO stock (barang_id, quantity, created_by) VALUES (?, ?, ?)";
            $stmt_insert = $connection->prepare($sql_insert);
            $stmt_insert->bind_param("sis", $barang_id, $quantity, $created_by);

            if (!$stmt_insert->execute()) {
                $errorMessage = "Error: " . $connection->error;
            } else {
                // Mendapatkan stock_id terakhir yang baru dimasukkan
                $stock_id = $connection->insert_id;
                $successMessage = "Stock berhasil ditambahkan.";
            }
        }

        // Insert ke table stock_detail
        for ($i = 0; $i < $quantity; $i++) {
            // Query untuk mengambil kode stock terakhir
            $sqlLastCode = "SELECT kode_stock FROM stock_detail ORDER BY kode_stock DESC LIMIT 1";
            $resultLastCode = $connection->query($sqlLastCode);

            if ($resultLastCode->num_rows > 0) {
                $rowLastCode = $resultLastCode->fetch_assoc();
                $lastCode = $rowLastCode['kode_stock'];
                // Generate kode stock baru hanya jika ada kode sebelumnya
                $newStockCode = generateNewStockCode($lastCode);
            } else {
                // Jika tidak ada kode sebelumnya, mulai dari 'S000001'
                $newStockCode = 'S000001';
            }

            // Query untuk menyimpan data baru ke dalam tabel stock_detail dengan lokasi_id
            $sqlInsertDetail = "INSERT INTO stock_detail (stock_id, barang_id, kode_stock, lokasi_id, deskripsi, status, create_by, create_date)
                                VALUES (?, ?, ?, ?, ?, 'active', ?, NOW())";
            $stmt_insert_detail = $connection->prepare($sqlInsertDetail);
            $stmt_insert_detail->bind_param("isssss", $stock_id, $barang_id, $newStockCode, $lokasi_id, $deskripsi, $created_by);

            if (!$stmt_insert_detail->execute()) {
                $errorMessage = "Error inserting stock detail: " . $connection->error;
                break; // Menghentikan loop jika terjadi kesalahan
            }
        }

        // Reset input setelah berhasil
        $barang_id = $quantity = $lokasi_id = $deskripsi = ""; // Reset semua variabel

        // Redirect ke halaman stok
        header("Location: /inventori/stock.php");
        exit;
    }
}
?>

<?php require "./header.php"; ?>

<!-- Navbar -->
<?php require "./navbar.php"; ?>

<div class="container my-3">
    <h2>Tambah Stok Baru</h2>

    <?php
    // Menampilkan pesan error jika ada
    if (!empty($errorMessage)) {
        echo "
        <div class='alert alert-warning alert-dismissible fade show' role='alert'>
            <strong>$errorMessage</strong>
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
        </div>
        ";
    }

    // Menampilkan pesan sukses jika ada
    if (!empty($successMessage)) {
        echo "
        <div class='alert alert-success alert-dismissible fade show' role='alert'>
            <strong>$successMessage</strong>
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
        </div>
        ";
    }
    ?>

    <!-- Form untuk input stok baru -->
    <form method="post" action="">
        <div class="row mb-3">
            <label class="col-sm-3 col-form-label">Nama Barang</label>

            <div class="col-sm-3">
                <input type="text" id="nama_barang_input" class="form-control" readonly placeholder="Pilih Barang" required 
                    style="background-color: #f8f9fa; border-color: #ced4da; padding: 8px; font-size: 14px; color: #495057;">
                <input type="hidden" name="barang_id" id="barang_id" required>
            </div>

            <div class="col-sm-2">
                <input type="text" id="kategori_input" class="form-control" readonly placeholder="Kategori" required 
                    style="background-color: #f8f9fa; border-color: #ced4da; padding: 8px; font-size: 14px; color: #495057;">
            </div>

            <div class="col-sm-2">
                <button type="button" style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#barangModal">Search</button>
            </div>
        </div>
        <div class="row mb-3">
            <label class="col-sm-3 col-form-label">QTY</label>
            <div class="col-sm-6">
                <input type="number" class="form-control" name="quantity" value="<?php echo htmlspecialchars($quantity); ?>" min="1" required 
                style="background-color: #f8f9fa; border-color: #ced4da; padding: 8px; font-size: 14px; color: #495057;">
            </div>
        </div>
        <div class="row mb-3">
            <label class="col-sm-3 col-form-label">Lokasi</label>
            <div class="col-sm-6">
                <select class="form-select" name="nama_lokasi" required
                style="background-color: #f8f9fa; border-color: #ced4da; padding: 8px; font-size: 14px; color: #495057;">
                    <option value="">Pilih Lokasi</option>
                    <?php
                    $sql = "SELECT id, nama_lokasi FROM lokasi";
                    $result = $connection->query($sql);

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $selected = ($lokasi_id == $row['id']) ? "selected" : "";
                            echo "<option value='" . htmlspecialchars($row['id']) . "' $selected>" . htmlspecialchars($row['nama_lokasi']) . "</option>";
                        }
                    } else {
                        echo "<option value=''>Tidak ada lokasi tersedia</option>";
                    }
                    ?>
                </select>
            </div>
        </div>
        <div class="row mb-3">
            <label class="col-sm-3 col-form-label">Deskripsi</label>
            <div class="col-sm-6">
                <textarea class="form-control" name="deskripsi" required
                style="background-color: #f8f9fa; border-color: #ced4da; padding: 8px; font-size: 14px; color: #495057;" ><?php echo htmlspecialchars($deskripsi); ?></textarea>
            </div>
        </div>
        <div class="row mb-3">
            <div class="offset-sm-3 col-sm-3 d-grid">
                <button style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" type="submit" class="btn btn-primary">Submit</button>
            </div>
            <div class="col-sm-3 d-grid">
                <a href="/inventori/stock.php" style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="btn btn-outline-primary">Cancel</a>
            </div>
        </div>
    </form>
</div>

<!-- Modal Pencarian Barang -->
<div class="modal fade" id="barangModal" tabindex="-1" aria-labelledby="barangModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="barangModalLabel">Pilih Barang</h5>
                <button type="button" style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" class="form-control" id="searchInput" placeholder="Cari barang...">
                </div>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Nama Barang</th>
                            <th>Kategori</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="barangTableBody">
                        <?php
                        // Ambil data barang dari database
                        $sql_barang = "SELECT b.id, b.nama_barang, k.nama_kategori FROM barang b JOIN barang_kategori k ON b.kategori_id = k.id";
                        $result_barang = $connection->query($sql_barang);

                        if ($result_barang->num_rows > 0) {
                            while ($row_barang = $result_barang->fetch_assoc()) {
                                echo "
                                <tr>
                                    <td>" . htmlspecialchars($row_barang['nama_barang']) . "</td>
                                    <td>" . htmlspecialchars($row_barang['nama_kategori']) . "</td>
                                    <td>
                                        <button style='box-shadow: 2px 2px 2px rgba(0,0,0,5);' class='btn btn-primary select-barang' 
                                                data-id='" . htmlspecialchars($row_barang['id']) . "' 
                                                data-nama='" . htmlspecialchars($row_barang['nama_barang']) . "' 
                                                data-kategori='" . htmlspecialchars($row_barang['nama_kategori']) . "' 
                                                data-bs-dismiss='modal'>Pilih</button>
                                    </td>
                                </tr>
                                ";

                            }
                        } else {
                            echo "
                            <tr>
                                <td colspan='4' class='text-center'>Tidak ada barang ditemukan</td>
                            </tr>
                            ";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Script untuk memilih barang dari modal -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const barangTableBody = document.getElementById('barangTableBody');

    // Event listener untuk input pencarian
    searchInput.addEventListener('input', function() {
        const searchTerm = searchInput.value.toLowerCase();
        
        // Mengambil semua baris dalam tabel
        const rows = barangTableBody.querySelectorAll('tr');
        rows.forEach(row => {
            const barangNama = row.cells[0].textContent.toLowerCase(); // Mengambil nama barang dari kolom 0
            const kategoriNama = row.cells[1].textContent.toLowerCase(); // Mengambil kategori dari kolom 1
            // Menyembunyikan baris yang tidak cocok
            if (barangNama.includes(searchTerm) || kategoriNama.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Event listener untuk tombol pilih
    const selectBarangButtons = document.querySelectorAll('.select-barang');
    selectBarangButtons.forEach(button => {
        button.addEventListener('click', function() {
            const barangId = this.getAttribute('data-id');
            const barangNama = this.getAttribute('data-nama');
            const kategoriNama = this.getAttribute('data-kategori');

            // Mengisi input dengan data yang dipilih
            document.getElementById('barang_id').value = barangId;
            document.getElementById('nama_barang_input').value = barangNama;
            document.getElementById('kategori_input').value = kategoriNama;
        });
    });

    // Ambil elemen form
    const form = document.querySelector('form');

    // Menambahkan event listener pada submit form
    form.addEventListener('submit', function(event) {
        event.preventDefault(); // Mencegah form dari submit secara langsung

        // Validasi input agar tidak kosong
        const barangId = document.getElementById('barang_id').value;
        const quantity = document.querySelector('input[name="quantity"]').value;
        const lokasiId = document.querySelector('select[name="nama_lokasi"]').value;
        const deskripsi = document.querySelector('textarea[name="deskripsi"]').value;

        if (!barangId || !quantity || !lokasiId || !deskripsi) {
            Swal.fire({
                title: 'Error!',
                text: 'Semua field harus diisi.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return;
        }

        Swal.fire({
            title: 'Konfirmasi',
            text: "Apakah Anda yakin untuk menambahkan stok ini?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, tambah!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Terkirim!',
                    text: 'Form berhasil dikirim.',
                    icon: 'success',
                    showConfirmButton: false,
                    timer: 1100
                }).then(() => {
                    form.submit(); // Form akan dikirim setelah SweetAlert sukses
                });
            }
        });
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php require "./foother.php"; ?>
