<?php
// riwayat_pasien.php
require_once 'config.php';
require_once 'header.php'; // Memuat header dan sidebar

$patients = [];
$search_query = $_GET['search'] ?? '';

// Query untuk mengambil daftar pasien
$sql_patients = "SELECT id, nama_lengkap, tanggal_lahir, jenis_kelamin, nomor_hp, email
                 FROM patients";

$params = [];
$types = "";

if (!empty($search_query)) {
    $sql_patients .= " WHERE nama_lengkap LIKE ? OR nomor_hp LIKE ? OR email LIKE ?";
    $search_param = "%" . $search_query . "%";
    $params = [$search_param, $search_param, $search_param];
    $types = "sss";
}

$sql_patients .= " ORDER BY nama_lengkap ASC";

// Baris 36: Memastikan penggunaan variabel $sql_patients yang benar
if ($stmt_patients = $conn->prepare($sql_patients)) {
    if (!empty($params)) {
        $stmt_patients->bind_param($types, ...$params);
    }
    $stmt_patients->execute();
    $result_patients = $stmt_patients->get_result();
    while ($row = $result_patients->fetch_assoc()) {
        $patients[] = $row;
    }
    $stmt_patients->close();
} else {
    echo "<div class='alert alert-danger'>Gagal mengambil data pasien: " . $conn->error . "</div>";
}

?>

<style>
    .page-title {
        color: #333;
        margin-bottom: 25px;
    }
    .patient-list-card {
        background-color: #fff;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        padding: 30px;
    }
    .patient-table th, .patient-table td {
        vertical-align: middle;
    }
    .patient-table th {
        background-color: #f8f9fa;
        color: #555;
    }
    .patient-table a {
        color: #0d6efd;
        text-decoration: none;
    }
    .patient-table a:hover {
        text-decoration: underline;
    }
    .search-bar-container {
        background-color: #fff;
        border-radius: 10px;
        padding: 15px 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        margin-bottom: 20px;
    }
</style>

<h1 class="page-title"><i class="fas fa-history me-2"></i> Riwayat Pasien</h1>

<div class="search-bar-container d-flex justify-content-between align-items-center mb-4">
    <form action="riwayat_pasien.php" method="GET" class="d-flex w-100 me-3">
        <input type="text" name="search" class="form-control me-2" placeholder="Cari nama, nomor HP, atau email pasien..." value="<?php echo htmlspecialchars($search_query); ?>">
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Cari</button>
    </form>
    <a href="rekap_medis_add.php" class="btn btn-success"><i class="fas fa-user-plus"></i> Tambah Pasien Baru</a>
</div>

<div class="patient-list-card">
    <?php if (empty($patients)): ?>
        <div class="alert alert-info text-center" role="alert">
            Tidak ada pasien ditemukan.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover patient-table">
                <thead>
                    <tr>
                        <th>ID Pasien</th>
                        <th>Nama Lengkap</th>
                        <th>Tanggal Lahir</th>
                        <th>Jenis Kelamin</th>
                        <th>Nomor HP</th>
                        <th>Email</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($patients as $patient): ?>
                        <tr>
                            <td>P-<?php echo htmlspecialchars($patient['id']); ?></td>
                            <td><?php echo htmlspecialchars($patient['nama_lengkap']); ?></td>
                            <td><?php echo date('d M Y', strtotime($patient['tanggal_lahir'])); ?></td>
                            <td><?php echo htmlspecialchars($patient['jenis_kelamin']); ?></td>
                            <td><?php echo htmlspecialchars($patient['nomor_hp']); ?></td>
                            <td><?php echo htmlspecialchars($patient['email']); ?></td>
                            <td>
                                <a href="detail_riwayat_pasien.php?patient_id=<?php echo $patient['id']; ?>" class="btn btn-info btn-sm text-white"><i class="fas fa-folder-open"></i> Lihat Riwayat</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
require_once 'footer.php'; // Memuat footer
$conn->close();
?>
