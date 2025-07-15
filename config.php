<?php
// config.php
ob_start(); // Tambahkan baris ini di paling atas!

// Pastikan session dimulai paling awal
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Informasi koneksi database
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // Ganti dengan username database Anda
define('DB_PASSWORD', '');     // Ganti dengan password database Anda
define('DB_NAME', 'rekap_medis'); // Ganti dengan nama database yang sudah Anda buat

// Membuat koneksi ke database MySQL
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Mengecek koneksi
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// Set karakter set untuk koneksi
$conn->set_charset("utf8mb4");

?>