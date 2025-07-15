<?php
session_start();                // Wajib: inisialisasi sesi
require_once 'config.php';      // Koneksi ke database

$username = $password = "";
$username_err = $password_err = $login_err = "";

/*-----------------------------------------------------------
 |  PROSES SAAT FORM DISUBMIT
 *----------------------------------------------------------*/
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    /* Validasi username */
    if (empty(trim($_POST["username"]))) {
        $username_err = "Mohon masukkan Username.";
    } else {
        $username = trim($_POST["username"]);
    }

    /* Validasi password */
    if (empty(trim($_POST["password"]))) {
        $password_err = "Mohon masukkan password Anda.";
    } else {
        $password = trim($_POST["password"]);
    }

    /* Cek kredensial ke database */
    if (empty($username_err) && empty($password_err)) {

        $sql = "SELECT id, username, password, nama_lengkap, role
                FROM users
                WHERE username = ?
                LIMIT 1";

        if ($stmt = $conn->prepare($sql)) {

            $stmt->bind_param("s", $param_username);
            $param_username = $username;

            if ($stmt->execute()) {
                $stmt->store_result();

                /* Jika username ditemukan */
                if ($stmt->num_rows == 1) {

                    $stmt->bind_result($id, $username_db, $password_db,
                                       $nama_lengkap, $role);
                    if ($stmt->fetch()) {

                        /* ======== PERBANDINGAN TEKS LANGSUNG ======== */
                        if ($password === $password_db) {

                            /* Login berhasil → simpan sesi */
                            $_SESSION["loggedin"]     = true;
                            $_SESSION["id"]           = $id;
                            $_SESSION["username"]     = $username_db;
                            $_SESSION["nama_lengkap"] = $nama_lengkap;
                            $_SESSION["role"]         = $role;

                            header("location: dashboard.php");
                            exit;

                        } else {
                            $login_err = "Username atau Password salah.";
                        }
                    }

                } else {  // Username tidak ada
                    $login_err = "Username atau Password salah.";
                }

            } else {
                echo "Terjadi kesalahan. Silakan coba lagi.";
            }

            $stmt->close();

        } else {
            echo "Gagal menyiapkan query: " . $conn->error;
        }
    }
}

/* Tutup koneksi */
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Rekap Medis</title>

    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <!-- Gaya bawaan (tetap) -->
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #e0f2f7;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .login-container {
            display: flex;
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,.1);
            overflow: hidden;
            width: 90%;
            max-width: 1200px;
        }
        .login-left {
            background-color: #1a237e;
            color: #fff;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            width: 50%;
            position: relative;
            background-image: url('assets/dokter.png.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        .login-left::before {
            content:'';
            position:absolute;
            top:0;left:0;right:0;bottom:0;
            background-color:rgba(26,35,126,.8);
            border-radius:15px 0 0 15px;
        }
        .login-left-content { z-index:1; }
        .login-left h2 { font-size:2.5rem;margin-bottom:20px; }
        .logo-rekap-medis{ width:300px;height:auto;margin-bottom:10px; }
        .login-left ul{ list-style:none;padding:0;text-align:left;margin-top:0; }
        .login-left ul li{ margin-bottom:15px;font-size:1.1rem;color:#fff; }
        .login-left ul li i{ margin-right:10px;color:#64b5f6; }
        .login-right{
            padding:40px;width:50%;display:flex;
            flex-direction:column;justify-content:center;
        }
        .login-right h2{ font-size:2rem;margin-bottom:10px;color:#333;text-align:center; }
        .login-right p{ color:#666;margin-bottom:30px;text-align:center; }
        .form-floating label{ color:#6c757d; }
        .btn-primary{
            background-color:#0d6efd;border-color:#0d6efd;
            border-radius:10px;padding:12px;font-size:1.1rem;width:100%;
        }
        .btn-primary:hover{ background-color:#0b5ed7;border-color:#0a58ca; }
        .form-control{ border-radius:10px;padding:12px 15px;height:auto; }
        .password-toggle{
            cursor:pointer;padding:.375rem .75rem;
            border-top-right-radius:10px;border-bottom-right-radius:10px;
            background-color:#e9ecef;border:1px solid #ced4da;border-left:none;
            display:flex;align-items:center;color:#6c757d;
        }
        .password-input-container{ display:flex;width:100%; }
        @media (max-width:768px){
            .login-container{ flex-direction:column;width:95%; }
            .login-left,.login-right{ width:100%;padding:30px; }
            .login-left{ border-radius:15px 15px 0 0; }
            .login-left::before{ border-radius:15px 15px 0 0; }
            .login-right{ border-radius:0 0 15px 15px; }
        }
    </style>
</head>
<body>
    <div class="login-container">

        <!-- Kolom kiri (gambar & info) -->
        <div class="login-left">
            <div class="login-left-content">
                <img src="assets/logo1.png" alt="Logo Rekap Medis" class="logo-rekap-medis">
                <ul>
                    <li><strong>nama peserta : ahlil mustofa bidin</strong></li>
                </ul>
            </div>
        </div>

        <!-- Kolom kanan (form login) -->
        <div class="login-right">
            <h2>Masuk ke Sistem</h2>
            <p>Gunakan akun resmi rumah sakit Anda</p>

            <?php
            /* Tampilkan error jika ada */
            if (!empty($login_err)) {
                echo '<div class="alert alert-danger mb-4">'.$login_err.'</div>';
            }
            ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">

                <!-- Username -->
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input  type="text" name="username" id="username"
                                class="form-control <?php echo (!empty($username_err))?'is-invalid':'';?>"
                                value="<?php echo htmlspecialchars($username); ?>"
                                placeholder="Masukkan username Anda">
                        <?php if(!empty($username_err)): ?>
                            <div class="invalid-feedback"><?php echo $username_err; ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Password -->
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group password-input-container">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input  type="password" name="password" id="password"
                                class="form-control <?php echo (!empty($password_err))?'is-invalid':'';?>"
                                placeholder="Masukkan password">
                        <span class="password-toggle" onclick="togglePasswordVisibility()">
                            <i class="fas fa-eye" id="password-toggle-icon"></i>
                        </span>
                        <?php if(!empty($password_err)): ?>
                            <div class="invalid-feedback"><?php echo $password_err; ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Opsional: Ingat perangkat -->
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="rememberMe">
                    <label class="form-check-label" for="rememberMe">Ingat perangkat ini</label>
                </div>

                <!-- Tombol -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt me-2"></i> Masuk
                    </button>
                </div>

                <div class="text-center mt-3">
                    <a href="#" class="text-decoration-none">Lupa password?</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Script Bootstrap & password‑toggle -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
    <script>
        /* Toggle visibilitas password */
        function togglePasswordVisibility() {
            const pwdField = document.getElementById('password');
            const icon      = document.getElementById('password-toggle-icon');
            if (pwdField.type === 'password') {
                pwdField.type = 'text';
                icon.classList.replace('fa-eye','fa-eye-slash');
            } else {
                pwdField.type = 'password';
                icon.classList.replace('fa-eye-slash','fa-eye');
            }
        }
    </script>
</body>
</html>
