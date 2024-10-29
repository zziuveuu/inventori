<?php
require "./conn.php";
?>

<?php
$nama_kategori = "";

$errorMessage = "";
$successMessage = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_kategori = $_POST["nama_kategori"];

    do {
        if (empty($nama_kategori)) {
            $errorMessage = "ALL fields are required";
            break;
        }
        
        // Cek apakah kategori sudah ada
        $sql = "SELECT * FROM barang_kategori WHERE nama_kategori = ?";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("s", $nama_kategori);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errorMessage = "Kategori already exists. Please choose another one.";
            break;
        }

        // Menambahkan kategori baru ke database
        $sql = "INSERT INTO barang_kategori (nama_kategori) VALUES (?)";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("s", $nama_kategori);
        
        if (!$stmt->execute()) {
            $errorMessage = "Invalid query: " . $connection->error;
            break;
        }

        $nama_kategori = "";

        $successMessage = "Kategori added successfully.";

        header("location: /inventori/kategori.php");
        exit;

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
    <h2>New Kategori</h2>

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
            <label class="col-sm-3 col-form-label">Nama Kategori</label>
            <div class="col-sm-6">
                <input type="text" class="form-control" name="nama_kategori" 
                style="background-color: #f8f9fa; border-color: #ced4da; padding: 8px; font-size: 14px; color: #495057;" value="<?php echo $nama_kategori; ?>">
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
                <a class="btn btn-outline-primary" style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" href="/inventori/kategori.php" role="button">Cancel</a>
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
require "./foother.php";
?>
