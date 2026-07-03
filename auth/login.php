<?php
session_start();

require_once "../config/database.php";
// Jika sudah login, langsung ke dashboard
if (isset($_SESSION['id_user'])) {
    header("Location: ../dashboard/dashboard.php");
    exit;
}


if(isset($_POST['login'])){
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $sql = "
        SELECT *
        FROM users
        WHERE username = :username
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':username'=>$username
    ]);
    $user = $stmt->fetch();
    if($user){

        if(password_verify($password,$user['password'])){
            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            header("Location: ../dashboard/dashboard.php");
            exit;
        }
    }
    $error = "Username atau Password salah.";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | SIPARK GBK</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/login.css">

    <style>

        body{
            background:#FFEAD3;
            height:100vh;
            display:flex;
            justify-content:center;
            align-items:center;
            font-family:'Segoe UI',sans-serif;
        }

        .login-card{
            width:430px;
            border:none;
            border-radius:20px;
            overflow:hidden;
            box-shadow:0 15px 35px rgba(0,0,0,.15);
        }

        .login-header{
            background:#D25353;
            color:white;
            text-align:center;
            padding:35px;
        }

        .login-header h2{
            font-weight:bold;
            margin-top:15px;
        }

        .login-body{
            background:white;
            padding:35px;
        }

        .form-control{
            height:50px;
            border-radius:12px;
        }

        .btn-login{
            background:#9E3B3B;
            color:white;
            height:50px;
            border-radius:12px;
            font-weight:bold;
            transition:.3s;
        }

        .btn-login:hover{
            background:#D25353;
            color:white;
        }

        .input-group-text{
            background:#EA7B7B;
            color:white;
            border:none;
        }

        .logo-circle{
            width:90px;
            height:90px;
            border-radius:50%;
            background:white;
            color:#D25353;
            display:flex;
            justify-content:center;
            align-items:center;
            margin:auto;
            font-size:40px;
        }

        .footer{
            margin-top:20px;
            text-align:center;
            font-size:13px;
            color:#777;
        }

    </style>

</head>
<body>

<div class="card login-card">
    <div class="login-header">
        <div class="logo-circle">
            <i class="bi bi-p-square-fill"></i>
        </div>
        <h2>SIPARK GBK</h2>
        <small>Sistem Informasi Parkir Kendaraan</small>
    </div>

    <div class="login-body">
        <?php
        if(isset($_SESSION['error'])){
        ?>

        <div class="alert alert-danger">
            <?= $_SESSION['error']; ?>
        </div>

        <?php
        unset($_SESSION['error']);
        }
        ?>

        <form action="proses_login.php" method="POST">
            <div class="mb-3">
                <label class="form-label">
                    Username
                </label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-person-fill"></i>
                    </span>

                    <input
                        type="text"
                        name="username"
                        class="form-control"
                        placeholder="Masukkan Username"
                        required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">
                    Password
                </label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-lock-fill"></i>
                    </span>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Masukkan Password"
                        required>

                    <span class="input-group-text"
                        id="togglePassword"
                        style="cursor:pointer;background:#F5F5F5;color:#777;">
                        <i class="bi bi-eye"></i>
                    </span>
                </div>
            </div>

            <button type="submit" class="btn btn-login w-100">
                <i class="bi bi-box-arrow-in-right"></i>
                Login
            </button>
        </form>
        <div class="footer">
            © 2026 SIPARK-GBK
        </div>
    </div>
</div>

<script>

const password=document.getElementById("password");
const toggle=document.getElementById("togglePassword");

toggle.addEventListener("click",()=>{
    if(password.type==="password"){
        password.type="text";
        toggle.innerHTML='<i class="bi bi-eye-slash"></i>';
    } else{
        password.type="password";
        toggle.innerHTML='<i class="bi bi-eye"></i>';
    }
});

</script>
</body>

</html>