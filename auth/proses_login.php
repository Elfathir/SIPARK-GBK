<?php

session_start();

require '../config/database.php';

// Pastikan request menggunakan metode POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: login.php");

    exit;
}

// Ambil data dari form
$username = trim($_POST['username']);
$password = $_POST['password'];

// Cek apakah username kosong
if (empty($username) || empty($password)) {
    $_SESSION['error'] = "Username dan Password wajib diisi.";
    header("Location: login.php");
    exit;
}

try {
    $sql = "SELECT *
            FROM users
            WHERE username = :username";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        ':username' => $username
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Username ditemukan
    if ($user) {

        // Cek password
        if (password_verify($password, $user['password'])) {
            // Simpan session
            $_SESSION['id_user']       = $user['id_user'];
            $_SESSION['nama_lengkap']  = $user['nama_lengkap'];
            $_SESSION['username']      = $user['username'];
            $_SESSION['role']          = $user['role'];

            header("Location: ../dashboard/dashboard.php");
            exit;
        } else {
            $_SESSION['error'] = "Password salah.";

            header("Location: login.php");
            exit;

        }
    } else {
        $_SESSION['error'] = "Username tidak ditemukan.";
        header("Location: login.php");

        exit;
    }
} catch(PDOException $e){
    die("Error : ".$e->getMessage());
}
?>