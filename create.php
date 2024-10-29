<?php
require "./conn.php"; // Menghubungkan dengan file koneksi

date_default_timezone_set('Asia/Jakarta'); // Set timezone ke Jakarta

// Mengecek koneksi
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

session_start(); // Memulai sesi untuk menggunakan variabel sesi

// Inisialisasi variabel
$id = "";
$kode_barang = "";
$nama_barang = "";
$kategori_id = "";
$createdate = "";
$created_by = "";
$errorMessage = "";
$successMessage = "";

// Fungsi untuk generate kode barang
function generateKodeBarang($connection)
{
    $sql = "SELECT kode_barang FROM barang ORDER BY id DESC LIMIT 1";
    $result = $connection->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastKode = $row['kode_barang'];
        $lastNumber = intval(substr($lastKode, 1));
        $nextNumber = $lastNumber + 1;
        $nextKode = "I" . str_pad($nextNumber, 6, "0", STR_PAD_LEFT);
    } else {
        $nextKode = "I000001";
    }

    return $nextKode;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_barang = trim($_POST["nama_barang"]);
    $kategori_id = $_POST["kategori_id"];
    $created_by = isset($_SESSION['session_username']) ? $_SESSION['session_username'] : null;

    do {
        if (empty($nama_barang) || empty($kategori_id)) {
            $errorMessage = "Nama Barang dan Kategori tidak boleh kosong.";
            break;
        }

        // Mengecek apakah nama barang sudah ada dalam kategori yang sama
        $sql = "SELECT * FROM barang WHERE nama_barang = ? AND kategori_id = ?";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("si", $nama_barang, $kategori_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errorMessage = "Nama Barang sudah ada dalam kategori yang sama. Silakan pilih nama lain.";
            break;
        }

        // Menghasilkan kode barang baru
        $kode_barang = generateKodeBarang($connection);
        $createdate = date('Y-m-d H:i:s');

        // Menambahkan barang baru
        $sql = "INSERT INTO barang (kode_barang, nama_barang, kategori_id, created_by) VALUES (?, ?, ?, ?)";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("ssis", $kode_barang, $nama_barang, $kategori_id, $created_by);

        if (!$stmt->execute()) {
            $errorMessage = "Terjadi kesalahan: " . $connection->error;
            break;
        }

        $id = $nama_barang = $kategori_id = $createdate = "";
        $successMessage = "Barang berhasil ditambahkan.";

        // Redirect dengan query string
        header("Location: /inventori/barang.php?success=1");
        exit; // Menambahkan exit setelah header
    } while (false);
}
?>

<?php
require "./header.php";
?>

<!-- Navbar -->
<?php
require "./navbar.php";
?>

<div class="container my-5">
    <h2>Tambah Barang Baru</h2>

    <?php
    if (!empty($errorMessage)) {
        echo "
        <div class='alert alert-warning alert-dismissible fade show' role='alert'>
            <strong>$errorMessage</strong>
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
        </div>
        ";
    }
    ?>

    <form method="post" id="addItemForm">
        <div class="row mb-3">
            <label class="col-sm-3 col-form-label">Kategori</label>
            <div class="col-sm-6">
                <select class="form-control" name="kategori_id" required
                style="background-color: #f8f9fa; border-color: #ced4da; padding: 8px; font-size: 14px; color: #495057;">
                    <option value="">Pilih Kategori</option>
                    <?php
                    // Mengambil data kategori dari tabel kategori
                    $sql = "SELECT id, nama_kategori FROM barang_kategori";
                    $result = $connection->query($sql);

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . $row['id'] . "'>" . $row['nama_kategori'] . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="row mb-3">
            <label class="col-sm-3 col-form-label">Nama Barang</label>
            <div class="col-sm-6">
                <input type="text" class="form-control" name="nama_barang" value="<?php echo htmlspecialchars($nama_barang); ?>" required
                style="background-color: #f8f9fa; border-color: #ced4da; padding: 8px; font-size: 14px; color: #495057;">
            </div>
        </div>

        <?php
        if (!empty($successMessage)) {
            echo "
            <div class='row mb-3'>
                <div class='offset-sm-3 col-sm-6'>
                    <div class='alert alert-success alert-dismissible fade show' role='alert'>
                        <strong>$successMessage</strong>
                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                    </div>
                </div>
            </div>
            ";
        }
        ?>

        <div class="row mb-3">
            <div class="offset-sm-3 col-sm-3 d-grid">
                <button type="submit" style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="btn btn-primary">Submit</button> 
            </div>
            <div class="col-sm-3 d-grid">
                <a class="btn btn-outline-primary" style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" href="/inventori/barang.php" role="button">Cancel</a>
            </div>
        </div>
    </form>
</div>

<!-- SweetAlert2 Script -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Menampilkan konfirmasi dengan SweetAlert2 sebelum submit
    const form = document.getElementById('addItemForm');
    form.addEventListener('submit', function(event) {
        event.preventDefault(); // Mencegah pengiriman form secara default

        // Menampilkan SweetAlert2 untuk konfirmasi
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: "Pastikan semua data sudah benar!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, kirim!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Mengirim form setelah konfirmasi
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

    // Menampilkan pesan sukses jika redirect berhasil
    <?php if (isset($_GET['success'])): ?>
    Swal.fire({
        position: "top-end",
        icon: "success",
        title: "Data berhasil disimpan!",
        showConfirmButton: false,
        timer: 1500
    }).then(() => {
        window.location.href = '/inventori/barang.php'; // Redirect setelah pesan sukses ditutup
    });
    <?php endif; ?>
</script>

<?php
require "./foother.php"; // Perbaiki dari "foother.php" menjadi "footer.php"
?>
