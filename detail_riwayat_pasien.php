<?php
// detail_riwayat_pasien.php
require_once 'config.php';
require_once 'header.php'; // Memuat header dan sidebar

$patient_id = $_GET['patient_id'] ?? 0;
$patient_info = null;
$medical_records = [];
$prescriptions = [];
$errors = [];

if ($patient_id == 0) {
    $errors[] = "ID Pasien tidak valid.";
} else {
    // Ambil informasi detail pasien
    $sql_patient_info = "SELECT * FROM patients WHERE id = ?";
    if ($stmt_pat_info = $conn->prepare($sql_patient_info)) {
        $stmt_pat_info->bind_param("i", $patient_id);
        $stmt_pat_info->execute();
        $result_pat_info = $stmt_pat_info->get_result();
        if ($result_pat_info->num_rows == 1) {
            $patient_info = $result_pat_info->fetch_assoc();
        } else {
            $errors[] = "Data pasien tidak ditemukan.";
        }
        $stmt_pat_info->close();
    } else {
        $errors[] = "Gagal mengambil informasi pasien: " . $conn->error;
    }

    // Ambil semua rekap medis untuk pasien ini
    // PERBAIKAN: Mengubah 'diagnosis' menjadi 'diagnosa' di query SQL
    $sql_medical_records = "SELECT id, tanggal_pemeriksaan, diagnosa, rencana_tindakan -- Diubah 'diagnosis' menjadi 'diagnosa'
                            FROM medical_record
                            WHERE patient_id = ?
                            ORDER BY tanggal_pemeriksaan DESC";
    // Menggunakan variabel $sql_medical_records yang benar di sini
    if ($stmt_mr = $conn->prepare($sql_medical_records)) {
        $stmt_mr->bind_param("i", $patient_id);
        $stmt_mr->execute();
        $result_mr = $stmt_mr->get_result();
        while ($row = $result_mr->fetch_assoc()) {
            $medical_records[] = $row;
        }
        $stmt_mr->close();
    } else {
        $errors[] = "Gagal mengambil rekap medis: " . $conn->error;
    }

    // Ambil semua resep obat untuk pasien ini
    $sql_prescriptions = "SELECT pr.id, pr.tanggal_pemeriksaan, pr.diagnosa, pr.tindakan, pr.saran_rawat_inap,
                          mr.id AS medical_record_id
                          FROM prescriptions pr
                          JOIN medical_record mr ON pr.medical_record_id = mr.id
                          WHERE mr.patient_id = ?
                          ORDER BY pr.tanggal_pemeriksaan DESC";
    if ($stmt_pres = $conn->prepare($sql_prescriptions)) {
        $stmt_pres->bind_param("i", $patient_id);
        $stmt_pres->execute();
        $result_pres = $stmt_pres->get_result();
        while ($row = $result_pres->fetch_assoc()) {
            $prescriptions[] = $row;
        }
        $stmt_pres->close();
    } else {
        $errors[] = "Gagal mengambil resep obat: " . $conn->error;
    }
}
?>

<style>
    .patient-detail-card {
        background-color: #fff;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        padding: 30px;
        margin-bottom: 20px;
    }
    .patient-detail-card h4 {
        color: #333;
        margin-bottom: 20px;
        font-weight: 600;
    }
    .patient-info-block p {
        margin-bottom: 5px;
        font-size: 1.05rem;
    }
    .patient-info-block strong {
        color: #555;
    }
    .record-section h5 {
        color: #0d6efd;
        margin-bottom: 20px;
        font-weight: 600;
    }
    .record-item {
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
    }
    .record-item div {
        flex: 1;
        min-width: 200px;
        margin-bottom: 10px;
    }
    .record-item .actions {
        flex: none;
        margin-left: auto;
        margin-bottom: 0;
    }
    .record-item p {
        margin-bottom: 5px;
    }
    .record-item strong {
        color: #444;
    }
    .record-item .badge {
        font-size: 0.85em;
        padding: 0.4em 0.8em;
        border-radius: 5px;
    }
</style>

<h1 class="page-title"><i class="fas fa-user-circle me-2"></i> Detail Riwayat Pasien</h1>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
        <a href="riwayat_pasien.php" class="btn btn-warning mt-2">Kembali ke Daftar Pasien</a>
    </div>
<?php else: ?>
    <?php if ($patient_info): ?>
        <div class="patient-detail-card">
            <h4>Informasi Pasien</h4>
            <div class="row patient-info-block">
                <div class="col-md-6">
                    <p><strong>Nama Lengkap:</strong> <?php echo htmlspecialchars($patient_info['nama_lengkap']); ?></p>
                    <p><strong>Tanggal Lahir:</strong> <?php echo date('d M Y', strtotime($patient_info['tanggal_lahir'])); ?></p>
                    <p><strong>Jenis Kelamin:</strong> <?php echo htmlspecialchars($patient_info['jenis_kelamin']); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Nomor HP:</strong> <?php echo htmlspecialchars($patient_info['nomor_hp']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($patient_info['email']); ?></p>
                    <p><strong>Alamat:</strong> <?php echo htmlspecialchars($patient_info['alamat']); ?></p>
                </div>
            </div>
            <div class="d-flex justify-content-end mt-3">
                <a href="rekap_medis_add.php?patient_id=<?php echo $patient_info['id']; ?>" class="btn btn-primary me-2"><i class="fas fa-file-medical me-2"></i> Tambah Rekam Medis Baru</a>
            </div>
        </div>

        <div class="patient-detail-card record-section">
            <h5 class="mb-4">Rekap Medis Pasien</h5>
            <?php if (empty($medical_records)): ?>
                <div class="alert alert-info text-center" role="alert">
                    Belum ada rekap medis untuk pasien ini.
                </div>
            <?php else: ?>
                <?php foreach ($medical_records as $mr): ?>
                    <div class="record-item">
                        <div>
                            <p class="mb-1"><strong>Tanggal:</strong> <span class="badge bg-secondary"><?php echo date('d M Y', strtotime($mr['tanggal_pemeriksaan'])); ?></span></p>
                            <p class="mb-1"><strong>Diagnosis:</strong> <?php echo htmlspecialchars(substr($mr['diagnosa'], 0, 100)) . (strlen($mr['diagnosa']) > 100 ? '...' : ''); ?></p>
                            <p class="mb-0"><strong>Tindakan:</strong> <?php echo htmlspecialchars(substr($mr['rencana_tindakan'], 0, 100)) . (strlen($mr['rencana_tindakan']) > 100 ? '...' : ''); ?></p>
                        </div>
                        <div class="actions">
                            <a href="detail_rekap_medis.php?id=<?php echo $mr['id']; ?>" class="btn btn-info btn-sm text-white me-2"><i class="fas fa-eye"></i> Detail MR</a>
                            <a href="rekap_medis_add.php?id=<?php echo $mr['id']; ?>" class="btn btn-secondary btn-sm"><i class="fas fa-edit"></i> Edit MR</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="patient-detail-card record-section">
            <h5 class="mb-4">Resep Obat Pasien</h5>
            <?php if (empty($prescriptions)): ?>
                <div class="alert alert-info text-center" role="alert">
                    Belum ada resep obat untuk pasien ini.
                </div>
            <?php else: ?>
                <?php foreach ($prescriptions as $pres): ?>
                    <div class="record-item">
                        <div>
                            <p class="mb-1"><strong>Tanggal Resep:</strong> <span class="badge bg-secondary"><?php echo date('d M Y', strtotime($pres['tanggal_pemeriksaan'])); ?></span></p>
                            <p class="mb-1"><strong>Diagnosa Terkait:</strong> <?php echo htmlspecialchars(substr($pres['diagnosa'], 0, 100)) . (strlen($pres['diagnosa']) > 100 ? '...' : ''); ?></p>
                            <p class="mb-0"><strong>Saran Rawat Inap:</strong> <span class="badge bg-<?php echo ($pres['saran_rawat_inap'] == 'iya') ? 'warning' : 'success'; ?>"><?php echo htmlspecialchars($pres['saran_rawat_inap'] ?? 'Tidak ada'); ?></span></p>
                        </div>
                        <div class="actions">
                            <a href="detail_resep.php?pres_id=<?php echo $pres['id']; ?>" class="btn btn-info btn-sm text-white me-2"><i class="fas fa-file-prescription"></i> Detail Resep</a>
                            <a href="resep_obat_add.php?mr_id=<?php echo $pres['medical_record_id']; ?>&pres_id=<?php echo $pres['id']; ?>" class="btn btn-secondary btn-sm"><i class="fas fa-edit"></i> Edit Resep</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    <?php endif; ?>
<?php endif; ?>

<?php
require_once 'footer.php'; // Memuat footer
$conn->close();
?>
