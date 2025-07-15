<?php
// dashboard.php
require_once 'config.php';
require_once 'header.php'; // Memuat header dan sidebar

// Query untuk mengambil rekap medis terbaru (contoh: 4 rekap medis terakhir)
$recent_medical_records = [];
$sql_recent_records = "SELECT mr.id, mr.tanggal_pemeriksaan, p.nama_lengkap AS pasien_nama, mr.diagnosa, mr.rencana_tindakan,
                        (SELECT pd.nama_obat FROM prescriptions pr JOIN prescription_details pd ON pr.id = pd.prescription_id WHERE pr.medical_record_id = mr.id LIMIT 1) AS nama_obat_resep
                        FROM medical_record mr
                        JOIN patients p ON mr.patient_id = p.id
                        ORDER BY mr.tanggal_pemeriksaan DESC
                        LIMIT 4";
$result_recent_records = $conn->query($sql_recent_records);

if ($result_recent_records) {
    while ($row = $result_recent_records->fetch_assoc()) {
        $recent_medical_records[] = $row;
    }
} else {
    echo "<p class='text-danger'>Error mengambil data rekap medis: " . $conn->error . "</p>";
}

?>

<style>
    /* Custom styles for dashboard */
    .dashboard-header {
        margin-bottom: 30px;
    }
    .card-medical-record {
        border-radius: 15px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
    }
    .card-medical-record:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 15px rgba(0,0,0,0.1);
    }
    .card-medical-record .card-header {
        background-color: #0d6efd; /* Primary color */
        color: #fff;
        border-top-left-radius: 15px;
        border-top-right-radius: 15px;
        font-weight: bold;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.25rem;
    }
    .card-medical-record .card-body {
        padding: 1.5rem;
    }
    .card-medical-record .status-badge {
        font-size: 0.8em;
        padding: 0.3em 0.7em;
        border-radius: 5px;
        font-weight: normal;
    }
    .status-badge.cancelled {
        background-color: #dc3545; /* Red */
        color: #fff;
    }
    .status-badge.completed {
        background-color: #28a745; /* Green */
        color: #fff;
    }
    .status-badge.pending {
        background-color: #ffc107; /* Yellow */
        color: #333;
    }
    .dashboard-search-bar {
        background-color: #fff;
        border-radius: 10px;
        padding: 15px 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        margin-bottom: 30px;
    }
    .dashboard-search-bar .form-control {
        border-radius: 8px;
    }
    .dashboard-search-bar .btn {
        border-radius: 8px;
    }
</style>

<div class="row dashboard-header">
    <div class="col-12">
        <h1 class="display-6">Selamat Datang Dokter, <?php echo htmlspecialchars($_SESSION["nama_lengkap"]); ?>!</h1>
        <p class="lead text-muted">Siap untuk hari yang produktif Dok? Cek jadwal Anda dan tangani pasien dengan efisien!</p>
    </div>
</div>

<div class="row mb-4">
    <div class="col-lg-12"> <!-- Changed to col-lg-12 to take full width -->
        <div class="dashboard-search-bar d-flex align-items-center">
            <input type="text" class="form-control me-3" placeholder="Cari pasien atau rekam medis...">
            <button class="btn btn-primary me-2"><i class="fas fa-search"></i> Cari</button>
            <a href="rekap_medis_add.php" class="btn btn-success"><i class="fas fa-plus"></i> Tambah Rekap Medis</a>
        </div>

        <div class="row">
            <?php if (empty($recent_medical_records)): ?>
                <div class="col-12">
                    <div class="alert alert-info text-center" role="alert">
                        Belum ada rekap medis yang ditambahkan.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($recent_medical_records as $record): ?>
                    <div class="col-md-6 col-lg-4 col-xl-3 mb-4">
                        <div class="card card-medical-record h-100">
                            <div class="card-header">
                                <span>RM-<?php echo str_pad($record['id'], 3, '0', STR_PAD_LEFT); ?></span>
                                <?php
                                    $status_class = '';
                                    $status_text = '';
                                    // Contoh logika status berdasarkan tanggal atau data lain
                                    // Untuk demo, kita bisa membuat status statis atau acak
                                    $random_status = ['completed', 'cancelled', 'pending'][array_rand(['completed', 'cancelled', 'pending'])];
                                    if ($random_status == 'completed') {
                                        $status_class = 'completed';
                                        $status_text = 'COMPLETED';
                                    } elseif ($random_status == 'cancelled') {
                                        $status_class = 'cancelled';
                                        $status_text = 'CANCELLED';
                                    } else {
                                        $status_class = 'pending';
                                        $status_text = 'PENDING';
                                    }
                                ?>
                                <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                            </div>
                            <div class="card-body">
                                <p class="card-text mb-1"><strong>Tanggal:</strong> <?php echo date('d M Y', strtotime($record['tanggal_pemeriksaan'])); ?></p>
                                <p class="card-text mb-1"><strong>Pasien:</strong> <?php echo htmlspecialchars($record['pasien_nama']); ?></p>
                                <p class="card-text mb-1"><strong>Diagnosa:</strong> <?php echo htmlspecialchars(substr($record['diagnosa'], 0, 30)) . (strlen($record['diagnosa']) > 30 ? '...' : ''); ?></p>
                                <p class="card-text mb-3"><strong>Resep:</strong> <?php echo htmlspecialchars($record['nama_obat_resep'] ?? 'Tidak ada resep'); ?></p>
                                <div class="d-flex justify-content-start">
                                    <a href="detail_rekap_medis.php?id=<?php echo $record['id']; ?>" class="btn btn-outline-primary btn-sm rounded-pill me-2"><i class="fas fa-eye"></i> Detail</a>
                                    <a href="rekap_medis_add.php?id=<?php echo $record['id']; ?>" class="btn btn-outline-secondary btn-sm rounded-pill"><i class="fas fa-edit"></i> Edit</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
require_once 'footer.php'; // Memuat footer
$conn->close();
?>
