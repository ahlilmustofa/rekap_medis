<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Rekap Medis - <?php echo htmlspecialchars($_SESSION["nama_lengkap"]); ?></title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f0f2f5;
    }
    #wrapper {
      display: flex;
    }
    #sidebar-wrapper {
      min-width: 250px;
      max-width: 250px;
      background-color: #2c3e50;
      color: #ecf0f1;
      transition: all 0.3s ease;
      height: 100vh;
      position: sticky;
      top: 0;
      overflow-y: auto;
    }
    #sidebar-wrapper .sidebar-heading {
      padding: 1.5rem 1rem;
      text-align: center;
      border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    #sidebar-wrapper .sidebar-heading img {
      width: 200px;
      border-radius: 12px;
    }
    #sidebar-wrapper .list-group {
      width: 100%;
    }
    #sidebar-wrapper .list-group-item {
      background-color: transparent;
      color: #ecf0f1;
      border: none;
      padding: 1rem 1.5rem;
      font-size: 1.05rem;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
    }
    #sidebar-wrapper .list-group-item i {
      margin-right: 15px;
      font-size: 1.2rem;
    }
    #sidebar-wrapper .list-group-item:hover {
      background-color: rgba(255,255,255,0.1);
      color: #fff;
    }
    #sidebar-wrapper .list-group-item.active {
      background-color: #0d6efd;
      color: #fff;
      border-left: 5px solid #fff;
      padding-left: 1rem;
    }
    #page-content-wrapper {
      width: 100%;
      padding: 20px;
      background-color: #f0f2f5;
    }
    .navbar-custom {
      background-color: #fff;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
      margin-bottom: 20px;
      padding: 1rem 1.5rem;
      border-radius: 10px;
    }
    .profile-info {
      text-align: center;
      padding: 20px 0;
      border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .profile-info img {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      margin-bottom: 10px;
      border: 2px solid #0d6efd;
    }
    .profile-info h5 {
      margin-bottom: 0;
    }
    .dokter-img {
    width: 100px;
    height: auto;
    border-radius: 10px;
}
    @media (max-width: 768px) {
      #sidebar-wrapper {
        margin-left: -250px;
        position: fixed;
        z-index: 1000;
        height: 100vh;
        box-shadow: 2px 0 5px rgba(0,0,0,0.2);
      }
      #sidebar-wrapper.toggled {
        margin-left: 0;
      }
      #page-content-wrapper {
        min-width: 100vw;
      }
      .navbar-toggler {
        display: block;
      }
      .navbar-brand-desktop {
        display: none;
      }
      .navbar-brand-mobile {
        display: block;
      }
    }
    @media (min-width: 769px) {
      .navbar-toggler {
        display: none;
      }
      .navbar-brand-desktop {
        display: block;
      }
      .navbar-brand-mobile {
        display: none;
      }
    }
  </style>
</head>
<body>
  <div class="d-flex" id="wrapper">
    <!-- Sidebar -->
    <div id="sidebar-wrapper">
      <div class="sidebar-heading">
        <img src="assets/logo1.png" alt="Rekap Medis">
      </div>
      <div class="profile-info">
        <img src="https://placehold.co/40x40/007bff/ffffff?text=<?php echo substr($_SESSION['nama_lengkap'], 0, 1); ?>" alt="Profile">
        <h5><?php echo htmlspecialchars($_SESSION["nama_lengkap"]); ?></h5>
        <button class="btn btn-outline-light btn-sm mt-2 rounded-pill">Edit Profil dokter</button>
      </div>
      <div class="list-group list-group-flush mt-3">
        <a href="dashboard.php" class="list-group-item <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="rekap_medis_add.php" class="list-group-item <?php echo (basename($_SERVER['PHP_SELF']) == 'rekap_medis_add.php') ? 'active' : ''; ?>"><i class="fas fa-file-medical"></i> Tambah Rekap</a>
        <a href="riwayat_pasien.php" class="list-group-item <?php echo (basename($_SERVER['PHP_SELF']) == 'riwayat_pasien.php') ? 'active' : ''; ?>"><i class="fas fa-history"></i> Riwayat Pasien</a>
        <a href="pengaturan.php" class="list-group-item <?php echo (basename($_SERVER['PHP_SELF']) == 'pengaturan.php') ? 'active' : ''; ?>"><i class="fas fa-cog"></i> Pengaturan</a>
        <a href="logout.php" class="list-group-item text-danger"><i class="fas fa-sign-out-alt"></i> Keluar</a>
      </div>
    </div>

    <!-- Page Content -->
    <div id="page-content-wrapper">
      <nav class="navbar navbar-expand-lg navbar-light navbar-custom">
        <div class="container-fluid">
          <button class="btn btn-primary" id="sidebarToggle"><i class="fas fa-bars"></i></button>
          <span class="navbar-brand ms-3 navbar-brand-desktop">Selamat Datang Dokter, <?php echo htmlspecialchars($_SESSION["nama_lengkap"]); ?>!</span>
          <span class="navbar-brand ms-3 navbar-brand-mobile d-none">Rekap Medis</span>
          <div class="d-flex align-items-center ms-auto">
            <img src="https://placehold.co/40x40/007bff/ffffff?text=<?php echo substr($_SESSION['nama_lengkap'], 0, 1); ?>" class="rounded-circle d-block d-md-none me-2">
            <span class="d-none d-md-block me-3">Siap produktif?</span>
            <img src="assets/icon dokter.avif" alt="Dokter" class="dokter-img">
          </div>
        </div>
      </nav>

      <div class="container-fluid">
        <!-- Konten halaman lain masuk di sini -->
