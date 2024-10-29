<?php 
session_start();
$_SESSION['session_username'] = "";
$_SESSION['session_password'] = "";
session_destroy();

$cookie_name = "cookie_username";
$cookie_value = "";
$time = time() - (60 * 60);
setcookie("cookie_username", "", time() - 3600, "/");

$cookie_name = "cookie_password";
$cookie_value = "";
$time = time() - (60 * 60);
setcookie("cookie_password", "", time() - 3600, "/");

header("location:/inventori/index.php");