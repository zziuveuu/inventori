<?php
require "./conn.php";
session_start();

// Pastikan pengguna sudah login
if (!isset($_SESSION['session_username'])) {
    header("location:/inventori/index.php");
    exit();
}

// Inisialisasi variabel
$barang_id = "";
$nama_pengembali = "";
$status = "";
$errorMessage = "";
$successMessage = "";

if (isset($_GET['ambil_barang_id'])) {
    $ambil_barang_id = intval($_GET['ambil_barang_id']); // Sanitasi input ID

    // Ambil data pengambilan barang
    $sql = "SELECT pb.barang_id, pb.nama_pengambil 
            FROM pengambilan_barang pb 
            WHERE pb.id = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("i", $ambil_barang_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $barang_id = $row['barang_id'];
        $nama_pengembali = $row['nama_pengambil'];
    } else {
        $errorMessage = "Data tidak ditemukan.";
    }
}

if (isset($_POST['kembalikan_barang'])) {
    // Proses pengembalian barang
    $create_by = isset($_SESSION['session_username']) ? $_SESSION['session_username'] : null;

    if (empty($barang_id) || empty($nama_pengembali) || empty($create_by)) {
        $errorMessage = "Data tidak valid. Silakan coba lagi.";
    } else {
        // Update status barang di tabel stock_detail menjadi 'active'
        $sql_update_stock = "UPDATE stock_detail SET status = 'active' WHERE id = ?";
        $stmt_update_stock = $connection->prepare($sql_update_stock);
        $stmt_update_stock->bind_param("i", $barang_id); // Pastikan jenis parameter sesuai dengan tipe data di database

        if ($stmt_update_stock->execute()) {
            // Hapus data dari tabel pengambilan_barang
            $sql_delete_pengambilan = "DELETE FROM pengambilan_barang WHERE barang_id = ? AND nama_pengambil = ?";
            $stmt_delete_pengambilan = $connection->prepare($sql_delete_pengambilan);
            $stmt_delete_pengambilan->bind_param("ss", $barang_id, $nama_pengembali);
            if ($stmt_delete_pengambilan->execute()) {
                // Set pesan sukses
                $successMessage = "Barang berhasil dikembalikan.";
                header("Location: /inventori/pengambilan_barang.php?message=success");
                exit();
            } else {
                $errorMessage = "Gagal menghapus data pengambilan barang.";
            }
        } else {
            $errorMessage = "Gagal memperbarui status barang.";
        }
    }
}
?>

<?php require "./header.php"; ?>

<div class="container my-5">
    <h2>Pengembalian Barang</h2>

    <?php
    if (!empty($errorMessage)) {
        echo "
        <div class='alert alert-warning alert-dismissible fade show' role='alert'>
            <strong>$errorMessage</strong>
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
        </div>
        ";
    }

    if (!empty($successMessage)) {
        echo "
        <div class='alert alert-success alert-dismissible fade show' role='alert'>
            <strong>$successMessage</strong>
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
        </div>
        ";
    }
    ?>

    <form method="post" action="">
        <input type="hidden" name="barang_id" value="<?php echo htmlspecialchars($barang_id); ?>">
        <input type="hidden" name="nama_pengembali" value="<?php echo htmlspecialchars($nama_pengembali); ?>">
        
        <div class="row mb-3">
            <div class="offset-sm-3 col-sm-3 d-grid">
                <button type="submit" name="kembalikan_barang" class="btn btn-danger">Kembalikan Barang</button>
            </div>
            <div class="col-sm-3 d-grid">
                <a class="btn btn-outline-primary" href="/inventori/barang_masuk.php" role="button">Cancel</a>
            </div>
        </div>
    </form>
</div>

<?php require "./foother.php"; ?>
