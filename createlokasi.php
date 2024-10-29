<?php
require "./conn.php";

$nama_lokasi = "";
$errorMessage = "";
$successMessage = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lokasi = $_POST["nama_lokasi"];

    do {
        if (empty($nama_lokasi)) {
            $errorMessage = "ALL fields are required";
            break;
        }

        $sql = "SELECT * FROM lokasi WHERE nama_lokasi = ?";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("s", $nama_lokasi);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errorMessage = "Lokasi already exists. Please choose another one";
            break;
        }

        // Menambah lokasi baru ke database
        $sql = "INSERT INTO lokasi (nama_lokasi) VALUES (?)";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("s", $nama_lokasi);
        
        if (!$stmt->execute()) {
            $errorMessage = "Invalid query: " . $connection->error;
            break;
        }

        $successMessage = "Lokasi berhasil ditambahkan";

        // Redirect dengan parameter success
        header("Location: /inventori/lokasi.php?success=1");
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
    <h2>New Lokasi</h2>

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
    
    <form method="post" action="" id="addItemForm">
        <div class="row mb-3">
            <label class="col-sm-3 col-form-label">Nama Lokasi</label>
            <div class="col-sm-6">
                <input type="text" class="form-control" name="nama_lokasi" style="background-color: #f8f9fa; border-color: #ced4da; padding: 8px; font-size: 14px; color: #495057;" value="<?php echo $nama_lokasi; ?>">
            </div>
        </div>

        <div class="row mb-3">
            <div class="offset-sm-3 col-sm-3 d-grid">
                <button type="submit" style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="btn btn-primary">Submit</button>
            </div>
            <div class="col-sm-3 d-grid">
                <a class="btn btn-outline-primary" style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" href="/inventori/lokasi.php" role="button">Cancel</a>
            </div>
        </div>
    </form>
</div>

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
        window.location.href = '/inventori/lokasi.php'; // Redirect setelah pesan sukses ditutup
    });
    <?php endif; ?>
</script>

<?php
require "./foother.php";
?>
