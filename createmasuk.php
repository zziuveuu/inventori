<?php
require "./conn.php";
session_start();

// Pastikan pengguna sudah login
if (!isset($_SESSION['session_username'])) {
    header("location:/inventori/index.php");
    exit();
}

$errorMessage = "";
$successMessage = "";

if (isset($_GET['ambil_barang_id'])) {
    $ambil_barang_id = intval($_GET['ambil_barang_id']); // Sanitasi input ID

    // Ambil data pengambilan barang
    $sql = "SELECT pb.nama_pengambil, sd.id AS stock_id
            FROM pengambilan_barang pb
            JOIN stock_detail sd ON pb.stock_id_detail = sd.id
            WHERE pb.id = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("i", $ambil_barang_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc(); // Ambil data untuk proses pengembalian
        $stock_id = $row['stock_id']; // ID stock_detail untuk update

        // Proses pengembalian barang
        if (isset($_POST['kembalikan_barang'])) {
            // Ambil tanggal pengembalian
            $tanggal_pengembalian = date('Y-m-d H:i:s'); // Waktu sekarang

            // Update stok di tabel stock_detail
            $sql_update_stock = "UPDATE stock_detail SET status = 'active' WHERE id = ?";
            $stmt_update_stock = $connection->prepare($sql_update_stock);
            $stmt_update_stock->bind_param("i", $stock_id); // Menggunakan stock_id yang sudah diambil

            if ($stmt_update_stock->execute()) {
                // Update status dan tanggal pengembalian di tabel pengambilan_barang
                $sql_update_pengambilan = "UPDATE pengambilan_barang SET status = 'dikembalikan', tanggal_pengembalian = ? WHERE id = ?";
                $stmt_update_pengambilan = $connection->prepare($sql_update_pengambilan);
                $stmt_update_pengambilan->bind_param("si", $tanggal_pengembalian, $ambil_barang_id);

                if ($stmt_update_pengambilan->execute()) {
                    // Hapus data dari tabel pengambilan_barang
                    $sql_delete_pengambilan = "DELETE FROM pengambilan_barang WHERE id = ?";
                    $stmt_delete_pengambilan = $connection->prepare($sql_delete_pengambilan);
                    $stmt_delete_pengambilan->bind_param("i", $ambil_barang_id);
                    $stmt_delete_pengambilan->execute();

                    $successMessage = "Barang berhasil dikembalikan.";
                    header("Location: /inventori/pengambilan_barang.php?message=success");
                    exit();
                } else {
                    $errorMessage = "Gagal mengupdate pengambilan barang: " . $stmt_update_pengambilan->error;
                }
            } else {
                $errorMessage = "Gagal mengupdate stok: " . $stmt_update_stock->error;
            }
        }
    } else {
        $errorMessage = "Data tidak ditemukan.";
    }
}
?>

<?php require "./header.php"; ?>

<!-- Navbar -->
<?php require "./navbar.php"; ?>

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
        <div class="row mb-3">
            <div class="col-sm-12">
                <button type="submit" name="kembalikan_barang" class="btn btn-danger">Kembalikan Barang</button>
                <a class="btn btn-outline-primary" href="/inventori/pengambilan_barang.php" role="button">Cancel</a>
            </div>
        </div>
    </form>
</div>

<?php require "./foother.php"; ?>
