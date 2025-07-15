<?php
// detail_resep.php
require_once 'config.php';
require_once 'header.php'; // Memuat header dan sidebar

$prescription_id = $_GET['pres_id'] ?? 0;

$prescription_info = null;
$prescription_details = [];
$errors = [];

if ($prescription_id == 0) {
    $errors[] = "ID Resep tidak valid.";
} else {
    // Ambil informasi resep utama
    // Menggunakan 'nama_lengkap' sesuai dengan struktur tabel 'users' yang diberikan
    $sql_prescription_info = "SELECT pr.*, p.nama_lengkap AS patient_name, p.tanggal_lahir, p.jenis_kelamin,
                               p.alamat, p.nomor_hp, p.email, u.nama_lengkap AS doctor_name -- PERBAIKAN: Menggunakan nama_lengkap sesuai screenshot
                               FROM prescriptions pr
                               JOIN medical_record mr ON pr.medical_record_id = mr.id
                               JOIN patients p ON mr.patient_id = p.id
                               JOIN users u ON mr.user_id = u.id
                               WHERE pr.id = ?";
    if ($stmt_pres_info = $conn->prepare($sql_prescription_info)) {
        $stmt_pres_info->bind_param("i", $prescription_id);
        $stmt_pres_info->execute();
        $result_pres_info = $stmt_pres_info->get_result();
        if ($result_pres_info->num_rows == 1) {
            $prescription_info = $result_pres_info->fetch_assoc();
        } else {
            $errors[] = "Resep tidak ditemukan.";
        }
        $stmt_pres_info->close();
    } else {
        $errors[] = "Gagal menyiapkan query info resep: " . $conn->error;
    }

    // Ambil detail obat untuk resep ini
    $sql_details = "SELECT * FROM prescription_details WHERE prescription_id = ?";
    if ($stmt_details = $conn->prepare($sql_details)) {
        $stmt_details->bind_param("i", $prescription_id);
        $stmt_details->execute();
        $result_details = $stmt_details->get_result();
        while ($row = $result_details->fetch_assoc()) {
            $prescription_details[] = $row;
        }
        $stmt_details->close();
    } else {
        $errors[] = "Gagal mengambil detail obat: " . $conn->error;
    }
}
?>

<style>
    .print-area {
        background-color: #fff;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        padding: 30px;
        margin-top: 20px;
    }
    .print-header {
        text-align: center;
        margin-bottom: 30px;
        border-bottom: 2px solid #eee;
        padding-bottom: 20px;
    }
    .print-header h3 {
        color: #0d6efd;
        font-weight: 700;
        margin-bottom: 5px;
    }
    .print-header p {
        color: #555;
        font-size: 0.95rem;
    }
    .info-section {
        margin-bottom: 25px;
    }
    .info-section h5 {
        color: #0d6efd;
        margin-bottom: 15px;
        font-weight: 600;
    }
    .info-item {
        display: flex;
        margin-bottom: 8px;
    }
    .info-item strong {
        width: 150px;
        flex-shrink: 0;
        color: #333;
    }
    .info-item span {
        flex-grow: 1;
        color: #555;
    }
    .table-details th, .table-details td {
        vertical-align: middle;
        padding: 10px;
    }
    .table-details th {
        background-color: #e9ecef;
        color: #333;
    }
    .table-details {
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 25px;
    }
    .btn-print {
        background-color: #28a745;
        border-color: #28a745;
        color: #fff;
        padding: 10px 25px;
        border-radius: 8px;
        font-size: 1.05rem;
    }
    .btn-print:hover {
        background-color: #218838;
        border-color: #1e7e34;
    }
    @media print {
        body * {
            visibility: hidden;
        }
        .print-area, .print-area * {
            visibility: visible;
        }
        .print-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            padding: 20px;
            box-shadow: none;
            border-radius: 0;
            margin-top: 0;
        }
        .sidebar, .navbar, .btn-group, .alert {
            display: none !important;
        }
        .main-content {
            margin-left: 0 !important;
        }
    }
</style>

<div class="row">
    <div class="col-12">
        <div class="print-area">
            <div class="print-header">
                <h3>Detail Resep Obat</h3>
                <p>Informasi Lengkap Resep Medis Pasien</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger mt-3">
                    Mohon perbaiki kesalahan berikut:
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($prescription_info): ?>
                <div class="info-section">
                    <h5>Informasi Umum Resep</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item">
                                <strong>ID Resep:</strong> <span><?php echo htmlspecialchars($prescription_info['id']); ?></span>
                            </div>
                            <div class="info-item">
                                <strong>Tanggal Pemeriksaan:</strong> <span><?php echo date('d M Y', strtotime($prescription_info['tanggal_pemeriksaan'])); ?></span>
                            </div>
                            <div class="info-item">
                                <strong>Diagnosa:</strong> <span><?php echo htmlspecialchars($prescription_info['diagnosa']); ?></span>
                            </div>
                            <div class="info-item">
                                <strong>Tindakan:</strong> <span><?php echo htmlspecialchars($prescription_info['tindakan']); ?></span>
                            </div>
                            <div class="info-item">
                                <strong>Saran Rawat Inap:</strong> <span><?php echo htmlspecialchars($prescription_info['saran_rawat_inap'] ?? '-'); ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <strong>Nama Dokter:</strong> <span><?php echo htmlspecialchars($prescription_info['doctor_name']); ?></span>
                            </div>
                            <div class="info-item">
                                <strong>Nama Pasien:</strong> <span><?php echo htmlspecialchars($prescription_info['nama_pasien']); ?></td>
                            </div>
                            <div class="info-item">
                                <strong>ID Pasien:</strong> <span><?php echo htmlspecialchars($prescription_info['id_pasien']); ?></span>
                            </div>
                            <div class="info-item">
                                <strong>Alamat Pasien:</strong> <span><?php echo htmlspecialchars($prescription_info['alamat_pasien']); ?></span>
                            </div>
                            <div class="info-item">
                                <strong>No. HP Pasien:</strong> <span><?php echo htmlspecialchars($prescription_info['no_hp_pasien']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="info-section">
                    <h5>Detail Obat</h5>
                    <?php if (!empty($prescription_details)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-details">
                                <thead>
                                    <tr>
                                        <th>Nama Obat</th>
                                        <th>Dosis</th>
                                        <th>Aturan Pakai</th>
                                        <th>Detail</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($prescription_details as $detail): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($detail['nama_obat']); ?></td>
                                            <td><?php echo htmlspecialchars($detail['dosis']); ?></td>
                                            <td><?php echo htmlspecialchars($detail['aturan_pakai']); ?></td>
                                            <td><?php echo htmlspecialchars($detail['detail']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info text-center">
                            Tidak ada obat yang terdaftar dalam resep ini.
                        </div>
                    <?php endif; ?>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <button class="btn btn-print" onclick="window.print()"><i class="fas fa-print me-2"></i> Cetak Resep</button>
                    <a href="resep_obat_add.php?mr_id=<?php echo htmlspecialchars($prescription_info['medical_record_id']); ?>&pres_id=<?php echo htmlspecialchars($prescription_info['id']); ?>" class="btn btn-secondary ms-2"><i class="fas fa-edit me-2"></i> Edit Resep</a>
                </div>

            <?php else: ?>
                <div class="alert alert-warning text-center">
                    Data resep tidak ditemukan atau tidak valid.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
require_once 'footer.php'; // Memuat footer
$conn->close();
?>