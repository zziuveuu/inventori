<?php
require "./conn.php";

$username = "";
$password = "";

$errorMessage = "";
$successMessage = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST["username"];
    $password = $_POST["password"];

    do {
        // Validasi input
        if (empty($username) || empty($password)) {
            $errorMessage = "Semua field wajib diisi";
            break;
        }

        // Cek apakah username sudah ada
        $sql = "SELECT * FROM user WHERE username = ?";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errorMessage = "Username sudah ada. Silakan pilih yang lain";
            break;
        }

        // Simpan password yang diinput langsung ke database
        $sql = "INSERT INTO user (username, password) VALUES (?, ?)";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("ss", $username, $password);

        if (!$stmt->execute()) {
            $errorMessage = "Kesalahan query: " . $connection->error;
            break;
        }

        // Reset input setelah berhasil disimpan
        $username = "";
        $password = "";

        $successMessage = "Pengguna berhasil ditambahkan";

        // Redirect ke halaman daftar user
        header("Location: /inventori/user.php");
        exit;

    } while (false);
}
?>

<!-- Navbar -->
<?php 
require "./navbar.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventori</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="container my-5">
        <h2>User Baru</h2>

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
                <label class="col-sm-3 col-form-label">Username</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" name="username" style="background-color: #f8f9fa; border-color: #ced4da; padding: 8px; font-size: 14px; color: #495057;" value="<?php echo htmlspecialchars($username); ?>">
                </div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Password</label>
                <div class="col-sm-6">
                    <input type="password" class="form-control" name="password" style="background-color: #f8f9fa; border-color: #ced4da; padding: 8px; font-size: 14px; color: #495057;" value="<?php echo htmlspecialchars($password); ?>">
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
                    <a class="btn btn-outline-primary" style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" href="/inventori/user.php" role="button">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</body>
</html>
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
        window.location.href = '/inventori/user.php'; // Redirect setelah pesan sukses ditutup
    });
    <?php endif; ?>
</script>

<?php
// Tutup koneksi
$connection->close();
?>
