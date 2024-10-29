$(document).ready(function() {
    // Fungsi untuk memuat konten berdasarkan menu yang diklik
    function loadContent(page) {
        $.ajax({
            url: page + ".php", // Asumsi bahwa setiap halaman ada dalam format PHP seperti barang.php, stock.php, dll.
            method: "GET",
            success: function(data) {
                $("#content-placeholder").html(data); // Menampilkan konten di div placeholder
            },
            error: function() {
                alert("Failed to load content. Please try again.");
            }
        });
    }

    // Mengatur aksi klik untuk setiap link di navbar
    $("#barang-link").click(function() {
        loadContent('barang');
    });

    $("#stock-link").click(function() {
        loadContent('stock');
    });

    $("#barangkeluar-link").click(function() {
        loadContent('pengambilan_barang');
    });

    $("#lokasi-link").click(function() {
        loadContent('lokasi');
    });

    $("#user-link").click(function() {
        loadContent('user');
    });

    // Muat halaman barang secara default ketika halaman pertama kali diakses
});
