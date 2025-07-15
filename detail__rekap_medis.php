<?php
// detail_rekap_medis.php
require_once 'config.php';
require_once 'header.php'; // Memuat header dan sidebar

$medical_record_id = $_GET['id'] ?? 0;
$medical_record = null;
$patient_info = null;
$prescription_exists = false;
$prescription_id = null;
$errors = [];

if ($medical_record_id == 0) {
    $errors[] = "ID Rekap Medis tidak valid.";
} else {
    // Ambil detail rekap medis
    $sql_mr = "SELECT mr.*, p.nama_lengkap AS patient_name, p.tanggal_lahir, p.jenis_kelamin, p.alamat, p.nomor_hp, p.email,
               u.nama_lengkap AS doctor_name
               FROM medical_records mr
               JOIN patients p ON mr.patient_id = p.id
               JOIN users u ON mr.user_id = u.id
               WHERE mr.id = ?";
    if ($stmt_mr = $conn->prepare($sql_mr)) {
        $stmt_mr->bind_param("i", $medical_record_id);
        $stmt_mr->execute();
        $result_mr = $stmt_mr->get_result();
        if ($result_mr->num_rows == 1) {
            $medical_record = $result_mr->fetch_assoc();
            $patient_info = [
                'nama_lengkap' => $medical_record['patient_name'],
                'tanggal_lahir' => $medical_record['tanggal_lahir'],
                'jenis_kelamin' => $medical_record['jenis_kelamin'],
                'alamat' => $medical_record['alamat'],
                'nomor_hp' => $medical_record['nomor_hp'],
                'email' => $medical_record['email']
            ];
        } else {
            $errors[] = "Rekap medis tidak ditemukan.";
        }
        $stmt_mr->close();
    } else {
        $errors[] = "Gagal mengambil rekap medis: " . $conn->error;
    }

    // Cek apakah ada resep terkait
    if ($medical_record) {
        $sql_check_prescription = "SELECT id FROM prescriptions WHERE medical_record_id = ?";
        if ($stmt_check_pres = $conn->prepare($sql_check_prescription)) {
            $stmt_check_pres->bind_param("i", $medical_record_id);
            $stmt_check_pres->execute();
            $result_check_pres = $stmt_check_pres->get_result();
            if ($result_check_pres->num_rows > 0) {
                $prescription_exists = true;
                $row = $result_check_pres->fetch_assoc();
                $prescription_id = $row['id'];
            }
            $stmt_check_pres->close();
        } else {
            $errors[] = "Gagal memeriksa resep terkait: " . $conn->error;
        }
    }
}
?>

<style>
    .detail-card {
        background-color: #fff;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        padding: 30px;
        margin-bottom: 20px;
    }
    .detail-card h4 {
        color: #0d6efd;
        margin-bottom: 25px;
        font-weight: 600;
        text-align: center;
        border-bottom: 2px solid #e9ecef;
        padding-bottom: 15px;
    }
    .detail-section {
        margin-bottom: 30px;
    }
    .detail-section h5 {
        color: #333;
        font-weight: 600;
        margin-bottom: 15px;
        border-left: 5px solid #0d6efd;
        padding-left: 10px;
    }
    .detail-section p {
        margin-bottom: 10px;
        line-height: 1.6;
        font-size: 1.05rem;
    }
    .detail-section p strong {
        color: #555;
        display: inline-block;
        min-width: 150px;
    }
    .btn-action-group {
        display: flex;
        justify-content: flex-end;
        gap: 15px;
        padding-top: 20px;
        border-top: 1px solid #eee;
        margin-top: 30px;
    }
    .btn-action-group .btn {
        padding: 10px 20px;
        border-radius: 8px;
    }
</style>

<h1 class="page-title"><i class="fas fa-file-alt me-2"></i> Detail Rekap Medis</h1>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
        <a href="riwayat_pasien.php" class="btn btn-warning mt-2">Kembali ke Riwayat Pasien</a>
    </div>
<?php else: ?>
    <?php if ($medical_record && $patient_info): ?>
        <div class="detail-card">
            <h4>Detail Rekap Medis Pasien</h4>

            <div class="detail-section">
                <h5><i class="fas fa-user-circle me-2"></i> Informasi Pasien</h5>
                <p><strong>Nama Pasien:</strong> <?php echo htmlspecialchars($patient_info['nama_lengkap']); ?></p>
                <p><strong>Tanggal Lahir:</strong> <?php echo date('d M Y', strtotime($patient_info['tanggal_lahir'])); ?></p>
                <p><strong>Jenis Kelamin:</strong> <?php echo htmlspecialchars($patient_info['jenis_kelamin']); ?></p>
                <p><strong>Alamat:</strong> <?php echo htmlspecialchars($patient_info['alamat']); ?></p>
                <p><strong>Nomor HP:</strong> <?php echo htmlspecialchars($patient_info['nomor_hp']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($patient_info['email']); ?></p>
            </div>

            <div class="detail-section">
                <h5><i class="fas fa-clipboard-list me-2"></i> Detail Pemeriksaan</h5>
                <p><strong>Tanggal Pemeriksaan:</strong> <?php echo date('d M Y', strtotime($medical_record['tanggal_pemeriksaan'])); ?></p>
                <p><strong>Dokter Penanggung Jawab:</strong> <?php echo htmlspecialchars($medical_record['doctor_name']); ?></p>
                <p><strong>Golongan Darah:</strong> <?php echo htmlspecialchars($medical_record['golongan_darah'] ?? '-'); ?></p>
                <p><strong>Keluhan Utama:</strong><br><?php echo nl2br(htmlspecialchars($medical_record['keluhan_utama'])); ?></p>
                <p><strong>Riwayat Medis:</strong><br><?php echo nl2br(htmlspecialchars($medical_record['riwayat_medis'] ?? '-')); ?></p>
                <p><strong>Alergi:</strong><br><?php echo nl2br(htmlspecialchars($medical_record['alergi'] ?? '-')); ?></p>
                <p><strong>Obat yang Sedang Dikonsumsi:</strong><br><?php echo nl2br(htmlspecialchars($medical_record['obat_dikonsumsi'] ?? '-')); ?></p>
            </div>

            <div class="detail-section">
                <h5><i class="fas fa-notes-medical me-2"></i> Diagnosis & Tindakan</h5>
                <p><strong>Diagnosis:</strong><br><?php echo nl2br(htmlspecialchars($medical_record['diagnosis'])); ?></p>
                <p><strong>Rencana Tindakan:</strong><br><?php echo nl2br(htmlspecialchars($medical_record['rencana_tindakan'])); ?></p>
            </div>

            <div class="btn-action-group">
                <a href="riwayat_pasien.php?patient_id=<?php echo $medical_record['patient_id']; ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i> Kembali ke Riwayat Pasien</a>
                <a href="rekap_medis_add.php?id=<?php echo $medical_record_id; ?>" class="btn btn-warning text-white"><i class="fas fa-edit me-2"></i> Edit Rekap Medis</a>
                <?php if ($prescription_exists): ?>
                    <a href="detail_resep.php?pres_id=<?php echo $prescription_id; ?>" class="btn btn-info text-white"><i class="fas fa-file-prescription me-2"></i> Lihat Resep</a>
                <?php else: ?>
                    <a href="resep_obat_add.php?mr_id=<?php echo $medical_record_id; ?>" class="btn btn-success"><i class="fas fa-plus-circle me-2"></i> Buat Resep Baru</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php
require_once 'footer.php'; // Memuat footer
$conn->close();
?>
