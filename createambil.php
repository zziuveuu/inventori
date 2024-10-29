<?php 
require './conn.php'; // Koneksi ke database

// Mulai sesi jika belum dimulai
session_start();

$nama_pengambil = "";
$status = "Dipinjam"; // Status default untuk item yang dipinjam
$create_by = "";
$errorMessage = ""; // Inisialisasi pesan error
$successMessage = ""; // Inisialisasi pesan sukses
$lokasi_id = "";
$kategori_id = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_pengambil = trim($_POST["nama_pengambil"]);
    // Ambil username dari sesi
    $create_by = isset($_SESSION['session_username']) ? $_SESSION['session_username'] : null;
    $lokasi_id = $_POST['lokasi']; // Capture the selected location
    $kategori_id = $_POST['kategori']; // Capture the selected category
    $date = date('Y-m-d H:i:s');

    // Validasi input
    if (empty($create_by)) {
        $errorMessage = "Pengguna tidak terautentikasi. Harap login terlebih dahulu.";
    }

    if (empty($nama_pengambil)) {
        $errorMessage = "Nama pengambil harus diisi.";
    }

    if (empty($lokasi_id)) {
        $errorMessage = "Lokasi harus dipilih.";
    }

    if (empty($kategori_id)) {
        $errorMessage = "Kategori harus dipilih.";
    }

    // Cek apakah ada item yang dipilih
    if (empty($_POST['selected_stock']) || !is_array($_POST['selected_stock'])) {
        $errorMessage = "Pilih setidaknya satu barang untuk disimpan.";
    }

    if (!empty($errorMessage)) {
        echo json_encode(["error" => $errorMessage]);
        exit;
    }

    $selected_stock = $_POST['selected_stock']; // Ambil barang yang dipilih

    // Loop melalui setiap item yang dipilih
    foreach ($selected_stock as $stock_id_detail) {
        // Ambil stock_id dari input yang sama dengan stock_id_detail
        $sql = "SELECT stock_id FROM stock_detail WHERE id = ?";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("s", $stock_id_detail);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if (!$row) {
            echo json_encode(["error" => "Stock ID tidak ditemukan untuk Stock Detail ID: " . htmlspecialchars($stock_id_detail)]);
            exit;
        }

        $current_stock_id = $row['stock_id']; // Ambil current_stock_id

        // Insert data ke pengambilan_barang
        $sql = "INSERT INTO pengambilan_barang (stock_id, stock_id_detail, nama_pengambil, date, status, create_by, lokasi_id, kategori_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $connection->prepare($sql);

        if ($stmt === false) {
            echo json_encode(["error" => "Kesalahan dalam menyiapkan statement SQL: " . $connection->error]);
            exit;
        }

        // Bind parameter termasuk create_by
        $stmt->bind_param("ssssssss", $current_stock_id, $stock_id_detail, $nama_pengambil, $date, $status, $create_by, $lokasi_id, $kategori_id);

        if (!$stmt->execute()) {
            echo json_encode(["error" => "Kesalahan saat menyimpan data: " . $stmt->error]);
            exit;
        }

        // Update status stock_detail menjadi 'Dipinjam'
        $update_sql = "UPDATE stock_detail SET status = 'Dipinjam' WHERE id = ?";
        $update_stmt = $connection->prepare($update_sql);

        if ($update_stmt === false) {
            echo json_encode(["error" => "Kesalahan dalam menyiapkan statement SQL: " . $connection->error]);
            exit;
        }

        $update_stmt->bind_param("s", $stock_id_detail);

        if (!$update_stmt->execute()) {
            echo json_encode(["error" => "Kesalahan saat memperbarui status barang: " . $update_stmt->error]);
            exit;
        }
    }

    // Jika semuanya baik, reset input dan berikan pesan sukses
    $successMessage = "Barang berhasil ditambahkan dan status diperbarui.";
    header("Location: /inventori/pengambilan_barang.php");
    exit;
}
?>

<?php
require "./header.php"; // Include header
require "./navbar.php"; // Include navbar
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

<div class="container my-2">
    <h2>Tambah Pengambilan Baru</h2>

    <form method="post" action="">
        <div class="row mb-3">
            <label class="col-sm-3 col-form-label">Nama Pengambil</label>
            <div class="col-sm-6">
                <input type="text" class="form-control" name="nama_pengambil" required
                style="background-color: #f8f9fa; border-color: #ced4da; padding: 8px; font-size: 14px; color: #495057;"
                    value="<?php echo htmlspecialchars($nama_pengambil); ?>">
            </div>
        </div>

        <!-- Dropdown untuk Lokasi -->
        <div class="row mb-3">
            <label class="col-sm-3 col-form-label">Lokasi</label>
            <div class="col-sm-6">
                <select class="form-control" name="lokasi" required
                style="background-color: #f8f9fa; border-color: #ced4da; padding: 8px; font-size: 14px; color: #495057;">
                    <option value="">Pilih Lokasi</option>
                    <?php
                    // Fetch lokasi dari database
                    $sql = "SELECT id, nama_lokasi FROM lokasi";
                    $lokasi_result = $connection->query($sql);
                    if ($lokasi_result->num_rows > 0) {
                        while ($row = $lokasi_result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($row['id']) . "'>" . htmlspecialchars($row['nama_lokasi']) . "</option>";
                        }
                    } else {
                        echo "<option value=''>Tidak ada lokasi tersedia</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <!-- Dropdown untuk Kategori -->
        <div class="row mb-3">
            <label class="col-sm-3 col-form-label">Kategori</label>
            <div class="col-sm-6">
                <select class="form-control" name="kategori" required
                style="background-color: #f8f9fa; border-color: #ced4da; padding: 8px; font-size: 14px; color: #495057;">
                    <option value="">Pilih Kategori</option>
                    <?php
                    // Fetch kategori dari database
                    $sql = "SELECT id, nama_kategori FROM barang_kategori";
                    $kategori_result = $connection->query($sql);
                    if ($kategori_result->num_rows > 0) {
                        while ($row = $kategori_result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($row['id']) . "'>" . htmlspecialchars($row['nama_kategori']) . "</option>";
                        }
                    } else {
                        echo "<option value=''>Tidak ada kategori tersedia</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <!-- Sisanya tetap sama -->
        <div class="row mb-3">
            <label class="col-sm-3 col-form-label">Nama Barang</label>
            <div class="col-sm-6">
                <select class="js-example-basic-single form-control" name="barang_id" id="barang_id" onchange="getStockDetail()" required>
                    <option value=""></option>
                    <!-- Fetch options from database -->
                    <?php
                    // Fetch barang_id dan nama_barang dari database
                    $sql = "SELECT barang_id, nama_barang, k.nama_kategori
                    FROM stock s 
                    INNER JOIN barang b ON s.barang_id = b.id
                    INNER JOIN barang_kategori k ON b.kategori_id = k.id
                    WHERE s.qty_active > 0";

                    $result = $connection->query($sql);
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($row['barang_id']) . "'>" . htmlspecialchars($row['nama_barang']) . " (" . htmlspecialchars($row['nama_kategori']) . ")</option>";
                        }
                    } else {
                        echo "<option value=''>Tidak ada barang tersedia</option>";
                    }
                    ?>
                </select>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-sm-6">
                <h4>Stock Detail</h4>
                <table class="table table-striped table-bordered" id="stock-detail-table" style="border: 1px solid black; display: flexbox;">
                    <thead>
                        <tr>
                            <th style="background-color:cornflowerblue;">Pilih</th>
                            <th style="background-color:cornflowerblue;">No</th>
                            <th style="background-color:cornflowerblue;">Nama Barang</th>
                            <th style="background-color:cornflowerblue;">Deskripsi</th>
                            <th style="background-color:cornflowerblue;">Lokasi</th>
                            <th style="background-color:cornflowerblue;">Kode</th>
                            <th style="background-color:cornflowerblue;">Kategori</th>
                            <th style="background-color:cornflowerblue;">Status</th>
                        </tr>
                    </thead>
                    <tbody id="stock-detail-body">
                        <!-- Stock details akan ditampilkan di sini -->
                    </tbody>
                </table>

                <div class="form-check mt-2" id="select-all-container" style=" display: none;">
                    <input style="border: 3px solid black;" class="form-check-input" type="checkbox" id="select-all" onclick="toggleSelectAll()">
                    <label class="form-check-label" for="select-all">Pilih Semua</label>
                </div>
            </div>

            <div class="col-sm-6">
                <h4>Barang yang Dipilih</h4>
                <table class="table table-striped table-bordered" id="selected-items-table" style="border: 1px solid black; background-color: lightblue;">
                    <thead>
                        <tr>
                            <th style="background-color: #7AB2D3;">No</th>
                            <th style="background-color: #7AB2D3;">Nama Barang</th>
                            <th style="background-color: #7AB2D3;">Deskripsi</th>
                            <th style="background-color: #7AB2D3;">Lokasi</th>
                            <th style="background-color: #7AB2D3;">Kode</th>
                            <th style="background-color: #7AB2D3;">Kategori</th>
                            <th style="background-color: #7AB2D3;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="selected-items-body">
                        <!-- Barang yang dipilih akan ditampilkan di sini -->
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row mb-3">
            <div class="offset-sm-3 col-sm-3 d-grid">
                <button style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" type="submit" class="btn btn-primary">Submit</button>
            </div>
            <div class="col-sm-3 d-grid">
                <a style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="btn btn-outline-primary" href="/inventori/pengambilan_barang.php" role="button">Cancel</a>
            </div>
        </div>
    </form>
</div>
<script>
$(document).ready(function() {
    // Inisialisasi select2
    $('.js-example-basic-single').select2({
        placeholder: 'Pilih Barang',
        allowClear: true
    }).on('change', function() {
        const barang_id = $(this).val();
        // Cek apakah barang sudah dipilih
        const alreadySelected = selectedItems.some(item => item.stock_id === barang_id);
        if (alreadySelected) {
            Swal.fire({
                title: 'Peringatan!',
                text: 'Barang ini sudah dipilih sebelumnya.',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            $(this).val(null).trigger('change'); // Reset select2
            return; // Jika barang sudah dipilih, hentikan eksekusi
        }
        // Jika barang belum dipilih, lanjutkan dengan mendapatkan detail stok
        getStockDetail(barang_id);
    });

    // Memanggil fungsi untuk memuat barang saat halaman dimuat
    loadBarang();
});

let selectedItems = []; // Array untuk menyimpan item yang dipilih

// Fungsi untuk memuat barang ke dalam Select2
function loadBarang() {
    $.ajax({
        url: 'get_barang.php', // Ganti dengan URL yang sesuai untuk mendapatkan daftar barang
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            const options = response
                .filter(item => item.status === 'active') // Filter hanya barang yang aktif
                .map(item => `<option value="${item.id}">${item.nama_barang}</option>`)
                .join('');

            $('.js-example-basic-single').html(options); // Isi dropdown
            $('.js-example-basic-single').select2({
                placeholder: 'Pilih Barang',
                allowClear: true
            });
        },
        error: function() {
            alert('Kesalahan dalam memuat daftar barang.');
        }
    });
}

// Fungsi untuk mengambil detail stok
function getStockDetail(barang_id) {
    if (barang_id) {
        // Lanjutkan dengan AJAX request
        $.ajax({
            url: 'get_stock_detail.php',
            type: 'POST',
            data: { barang_id: barang_id },
            dataType: 'json',
            success: function(response) {
                if (response.error) {
                    // Ganti alert bawaan dengan SweetAlert untuk error
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.error,
                        confirmButtonText: 'OK'
                    });
                } else if (response.lokasiDetails.length === 0) {
                    // Tampilkan SweetAlert jika tidak ada barang sama sekali
                    Swal.fire({
                        title: 'Informasi',
                        text: 'Tidak ada barang yang ditemukan.',
                        icon: 'info',
                        confirmButtonText: 'OK'
                    });
                } else {
                    const activeItems = response.lokasiDetails.filter(stock => stock.status === 'active');
                    if (activeItems.length === 0) {
                        // Tampilkan SweetAlert jika tidak ada barang aktif
                        Swal.fire({
                            title: 'Peringatan!',
                            text: 'Tidak ada barang yang aktif untuk dipilih.',
                            icon: 'warning',
                            confirmButtonText: 'OK'
                        });
                        $('#stock-detail-body').html(''); // Bersihkan tabel
                        $('#select-all-container').hide(); // Sembunyikan kontainer
                    } else {
                        const stockDetailBody = $('#stock-detail-body');
                        stockDetailBody.empty(); // Menghapus isi sebelumnya

                        // Reset checkbox "Pilih Semua"
                        $('#select-all').prop('checked', false);

                        // Isi tabel dengan data yang diterima
                        response.lokasiDetails.forEach(function(stock, index) {
                            const isChecked = selectedItems.some(item => item.stock_id_detail === stock.stock_id_detail) ? 'checked' : '';
                            const isDisabled = selectedItems.some(item => item.stock_id === stock.stock_id) ? 'disabled' : '';
                            const row = `<tr>
                                            <td><input type="checkbox" name="selected_stock[]" data-stock_id="${stock.stock_id}" value="${stock.stock_id_detail}" ${isChecked} ${isDisabled} onchange="updateSelectedItems('${stock.stock_id}', '${stock.stock_id_detail}', '${stock.nama_barang}', '${stock.deskripsi}', '${stock.nama_lokasi}', '${stock.kode_stock}', '${stock.nama_kategori}', this, '${stock.status}')"></td>
                                            <td>${index + 1}</td>
                                            <td>${stock.nama_barang}</td>
                                            <td>${stock.deskripsi}</td>
                                            <td style='text-align:center'>${stock.nama_lokasi}</td>
                                            <td>${stock.kode_stock}</td>
                                            <td style='text-align:center'>${stock.nama_kategori}</td>
                                            <td>${stock.status}</td>
                                        </tr>`;
                            stockDetailBody.append(row);
                        });

                        $('#stock-detail-table').show();
                        $('#select-all-container').show();
                    }
                }
                displaySelectedItems(); // Tampilkan barang yang sudah dipilih
            },
            error: function() {
                // Ganti alert bawaan dengan SweetAlert untuk error dalam request
                Swal.fire({
                    icon: 'error',
                    title: 'Kesalahan',
                    text: 'Kesalahan dalam memproses permintaan.',
                    confirmButtonText: 'OK'
                });
            }
        });
    } else {
        $('#select-all-container').hide();
    }
}

// Fungsi untuk menampilkan item yang dipilih
function displaySelectedItems() {
    const selectedItemsBody = $('#selected-items-body');
    selectedItemsBody.empty(); // Bersihkan tabel sebelumnya

    // Tampilkan semua barang yang dipilih
    selectedItems.forEach((item, index) => {
        const row = `<tr>
                        <td>${index + 1}</td>
                        <td>${item.nama_barang}</td>
                        <td>${item.deskripsi}</td>
                        <td style='text-align:center'>${item.lokasi}</td>
                        <td>${item.kode}</td>
                        <td style='text-align:center'>${item.nama_kategori}</td>
                        <td><button type="button" style="box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="btn btn-danger btn-sm" onclick="cancelSelectedItem('${item.stock_id_detail}')">Batalkan</button></td>
                    </tr>`;
        selectedItemsBody.append(row);
    });
}

// Fungsi untuk memperbarui item yang dipilih
function updateSelectedItems(stock_id, stock_id_detail, nama_barang, deskripsi, lokasi, kode, nama_kategori, checkbox, status) {
    if (status !== 'active') {
        // Jika status tidak aktif, tampilkan alert
        Swal.fire({
            title: 'Peringatan!',
            text: 'Barang ini sudah tidak aktif dan tidak dapat dipilih.',
            icon: 'warning',
            confirmButtonText: 'OK'
        });
        // Uncheck checkbox
        checkbox.checked = false;
        return;
    }

    if (checkbox.checked) {
        // Tambahkan ke array jika belum ada
        const alreadySelected = selectedItems.find(item => item.stock_id_detail === stock_id_detail);
        if (!alreadySelected) {
            selectedItems.push({ stock_id, stock_id_detail, nama_barang, deskripsi, lokasi, kode, nama_kategori });
        } else {
            // Tampilkan alert jika barang sudah ada
            Swal.fire({
                title: 'Peringatan!',
                text: 'Barang ini sudah ada di daftar barang yang dipilih.',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            checkbox.checked = false; // Uncheck jika sudah ada
        }
    } else {
        // Hapus dari array jika di-uncheck
        selectedItems = selectedItems.filter(item => item.stock_id_detail !== stock_id_detail);
    }
    displaySelectedItems();
}

// Fungsi untuk membatalkan item yang dipilih
function cancelSelectedItem(stock_id_detail) {
    selectedItems = selectedItems.filter(item => item.stock_id_detail !== stock_id_detail);
    displaySelectedItems();

    // Uncheck checkbox di table stock detail
    $(`input[value="${stock_id_detail}"]`).prop('checked', false);
}

// Fungsi Select All
function toggleSelectAll() {
    const checkboxes = document.querySelectorAll('input[name="selected_stock[]"]');
    const selectAllCheckbox = document.getElementById('select-all');

    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;

        if (checkbox.checked) {
            updateSelectedItems(
                checkbox.getAttribute('data-stock_id'),
                checkbox.value,
                checkbox.closest('tr').cells[2].innerText,
                checkbox.closest('tr').cells[3].innerText,
                checkbox.closest('tr').cells[4].innerText,
                checkbox.closest('tr').cells[5].innerText,
                checkbox.closest('tr').cells[6].innerText,
                checkbox,
                checkbox.closest('tr').cells[7].innerText // Kirim status barang
            );
        } else {
            updateSelectedItems(
                checkbox.getAttribute('data-stock_id'),
                checkbox.value,
                checkbox.closest('tr').cells[2].innerText,
                checkbox.closest('tr').cells[3].innerText,
                checkbox.closest('tr').cells[4].innerText,
                checkbox.closest('tr').cells[5].innerText,
                checkbox.closest('tr').cells[6].innerText,
                checkbox,
                checkbox.closest('tr').cells[7].innerText // Kirim status barang
            );
        }
    });

    displaySelectedItems();
}

// Ambil elemen form
const form = document.querySelector('form');

// Menambahkan event listener pada submit form
form.addEventListener('submit', function(event) {
    event.preventDefault();

    const namaPengambil = document.querySelector('input[name="nama_pengambil"]').value;
    const barangId = document.querySelector('select[name="barang_id"]').value;

    if (!namaPengambil || !barangId || selectedItems.length === 0) {
        Swal.fire({
            title: 'Error!',
            text: 'Anda harus memilih setidaknya satu barang sebelum mengirim.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
        return;
    }

    const existingHiddenInputs = form.querySelectorAll('input[name="selected_stock[]"]');
    existingHiddenInputs.forEach(input => input.remove());

    selectedItems.forEach(item => {
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'selected_stock[]';
        hiddenInput.value = item.stock_id_detail;
        form.appendChild(hiddenInput);
    });

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
                timer: 1000
            }).then(() => {
                form.submit();
            });
        }
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
require "./foother.php"; // Include footer
?>
