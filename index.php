<?php
require "./conn.php";
session_start();

// Atur variabel
$err        = "";
$username   = "";
$rememberme = "";

// Cek cookie untuk login otomatis
if (isset($_COOKIE['cookie_username'])) {
    $cookie_username = $_COOKIE['cookie_username'];
    $cookie_password = $_COOKIE['cookie_password'];

    $sql1 = "SELECT * FROM user WHERE username = '$cookie_username'";
    $q1   = mysqli_query($connection, $sql1);
    $r1   = mysqli_fetch_array($q1);
    if ($r1['password'] == $cookie_password) {
        $_SESSION['session_username'] = $cookie_username;
    }
}

// Jika pengguna sudah login
if (isset($_SESSION['session_username'])) {
    header("location:/inventori/barang.php");
    exit();
}

// Proses login
if (isset($_POST['login'])) {
    $username   = $_POST['username'];
    $password   = $_POST['password'];
    $rememberme = isset($_POST['rememberme']) ? $_POST['rememberme'] : "";

    if ($username == '' || $password == '') {
        $err .= "<li>Silakan masukkan username dan juga password.</li>";
    } else {
        $sql1 = "SELECT * FROM user WHERE username = '$username'";
        $q1   = mysqli_query($connection, $sql1);

        if (mysqli_num_rows($q1) > 0) {
            $r1 = mysqli_fetch_array($q1);
            if ($r1['password'] != $password) {
                $err .= "<li>Password yang dimasukkan tidak sesuai.</li>";
            }

            if (empty($err)) {
                $_SESSION['session_username'] = $username; // server

                if ($rememberme == 1) {
                    setcookie('cookie_username', $username, time() + (60 * 60 * 24 * 30), "/");
                    setcookie('cookie_password', $password, time() + (60 * 60 * 24 * 30), "/");
                }
                header("location:/inventori/barang.php");
                exit();
            }
        } else {
            $err .= "<li>Username <b>$username</b> tidak tersedia.</li>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Inventori</title>

    <!-- Custom fonts for this template-->
    <link rel="stylesheet" type="text/css" href="css/index.css">
   
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body style="background-image: url('assets/image/new-bg.png'); background-size: cover; background-position: center; background-repeat: no-repeat; height: 100vh; margin: 0;">
    <!-- Overlay untuk mengurangi kecerahan background -->
    <div class="bg-overlay"></div>

    <div class="container">
        <!-- Outer Row -->
        <div class="bg-login">
            <div class="d-flex justify-content-center align-items-center" style="min-height: 100vh;">
                <div class="col-xl-4 col-lg-6 col-md-9">
                        <div style=" border-radius:20px; background-color:rgba(255, 255, 255, 0.61); box-shadow: rgba(290, 290, 249, 76) 0px 5px 54px;" class="card-body p-6">
                            <!-- Nested Row within Card Body -->
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="p-4 text-center">
                                        <!-- Logo di sini -->
                                        <img src="assets/image/yusen-logistics.png" class="logo mb-4" alt="Yusen Logistics Logo">
                                        <h3 class="h3">Welcome Inventori!</h3>

                                        <!-- Login Form -->
                                        <?php if ($err) { ?>
                                            <div id="login-alert" class="alert alert-danger col-sm-12">
                                                <ul><?php echo $err; ?></ul>
                                            </div>
                                        <?php } ?>
                                        <form id="loginform" action="" method="POST">
                                            <div class="form-group">
                                                <input type="text" style="border-radius: 10px; box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="form-control form-control-user" id="login-username"
                                                       name="username" value="<?php echo htmlspecialchars($username); ?>" placeholder="Username">
                                            </div>          
                                            <div class="form-group">
                                                <input type="password" style="border-radius: 10px; box-shadow: 2px 2px 2px rgba(0,0,0,5);" class="form-control form-control-user" id="login-password"
                                                       name="password" placeholder="Password">
                                            </div>
                                            <div class="input-group">
                                                <div class="rememberme">
                                                    <label>
                                                        <input id="login-rememberme" type="checkbox" name="rememberme" value="1" <?php if ($rememberme == '1') echo "checked"; ?>> Remember Me
                                                    </label>
                                                </div>
                                            </div>
                                            <button type="submit" style="box-shadow: inset 2px 2px 8px rgba(0,0,0,10); border-radius:10px;" name="login" class="btn btn-primary btn-user btn-block">
                                                Login
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

    <!-- Custom JavaScript for this page -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Fokus pada kolom username saat halaman dimuat
            document.getElementById('login-username').focus();

            // Tambahkan event listener untuk tombol login
            const loginForm = document.getElementById('loginform');
            loginForm.addEventListener('submit', function (e) {
                // Ambil nilai input username dan password
                const username = document.getElementById('login-username').value.trim();
                const password = document.getElementById('login-password').value.trim();

                // Validasi sederhana untuk memastikan kolom tidak kosong
                if (username === '' || password === '') {
                    e.preventDefault(); // Mencegah form dikirim
                    alert('Username dan password harus diisi!');
                    return;
                }

                // Jika validasi lulus, form akan dikirim
            });
        });
    </script>

</body>

</html>
