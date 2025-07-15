<?php
// resep_obat_add.php
require_once 'config.php';
require_once 'header.php'; // Memuat header dan sidebar

$medical_record_id = $_GET['mr_id'] ?? 0;
$prescription_id = $_GET['pres_id'] ?? 0;

$patient_info = null;
$prescription_details = [];
$errors = [];
$success_message = "";

// Pastikan medical_record_id dan prescription_id valid
if ($medical_record_id == 0 || $prescription_id == 0) {
    $errors[] = "ID Rekap Medis atau ID Resep tidak valid.";
} else {
    // Ambil informasi pasien dan resep utama
    $sql_prescription_info = "SELECT p.nama_lengkap AS patient_name, p.nomor_hp, p.alamat,
                               pr.tanggal_pemeriksaan, pr.diagnosa, pr.tindakan, pr.saran_rawat_inap
                               FROM prescriptions pr
                               JOIN medical_record mr ON pr.medical_record_id = mr.id 
                               JOIN patients p ON mr.patient_id = p.id
                               WHERE pr.id = ? AND pr.medical_record_id = ?";
    if ($stmt_pres_info = $conn->prepare($sql_prescription_info)) {
        $stmt_pres_info->bind_param("ii", $prescription_id, $medical_record_id);
        $stmt_pres_info->execute();
        $result_pres_info = $stmt_pres_info->get_result();
        if ($result_pres_info->num_rows == 1) {
            $patient_info = $result_pres_info->fetch_assoc();
        } else {
            $errors[] = "Data resep tidak ditemukan.";
        }
        $stmt_pres_info->close();
    } else {
        $errors[] = "Gagal menyiapkan query info resep: " . $conn->error;
    }

    // Ambil detail obat yang sudah ada (jika ada)
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

// Handle penambahan detail obat
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_drug') {
    if ($prescription_id > 0) {
        $nama_obat = trim($_POST['nama_obat'] ?? '');
        $dosis = trim($_POST['dosis'] ?? '');
        $aturan_pakai = trim($_POST['aturan_pakai'] ?? '');
        $detail_obat = trim($_POST['detail_obat'] ?? '');

        if (empty($nama_obat) || empty($dosis) || empty($aturan_pakai)) {
            $errors[] = "Nama Obat, Dosis, dan Aturan Pakai wajib diisi.";
        } else {
            $sql_insert_detail = "INSERT INTO prescription_details (prescription_id, nama_obat, dosis, aturan_pakai, detail) VALUES (?, ?, ?, ?, ?)";
            if ($stmt_insert = $conn->prepare($sql_insert_detail)) {
                $stmt_insert->bind_param("issss", $prescription_id, $nama_obat, $dosis, $aturan_pakai, $detail_obat);
                if ($stmt_insert->execute()) {
                    $success_message = "Obat berhasil ditambahkan ke resep!";
                    // Refresh halaman untuk menampilkan obat baru
                    header("location: resep_obat_add.php?mr_id=" . $medical_record_id . "&pres_id=" . $prescription_id);
                    exit;
                } else {
                    $errors[] = "Gagal menambahkan obat: " . $stmt_insert->error;
                }
                $stmt_insert->close();
            } else {
                $errors[] = "Gagal menyiapkan statement tambah obat: " . $conn->error;
            }
        }
    } else {
        $errors[] = "Resep belum dipilih.";
    }
}

// Handle hapus detail obat
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_drug') {
    $detail_id = (int)($_POST['detail_id'] ?? 0);
    if ($detail_id > 0) {
        $sql_delete_detail = "DELETE FROM prescription_details WHERE id = ? AND prescription_id = ?";
        if ($stmt_delete = $conn->prepare($sql_delete_detail)) {
            $stmt_delete->bind_param("ii", $detail_id, $prescription_id);
            if ($stmt_delete->execute()) {
                $success_message = "Obat berhasil dihapus.";
                // Refresh halaman
                header("location: resep_obat_add.php?mr_id=" . $medical_record_id . "&pres_id=" . $prescription_id);
                exit;
            } else {
                $errors[] = "Gagal menghapus obat: " . $stmt_delete->error;
            }
            $stmt_delete->close();
        } else {
            $errors[] = "Gagal menyiapkan statement hapus obat: " . $conn->error;
        }
    }
}

// Handle update saran rawat inap
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_saran') {
    $saran_rawat_inap = trim($_POST['saran_rawat_inap'] ?? '');
    if ($prescription_id > 0) {
        $sql_update_saran = "UPDATE prescriptions SET saran_rawat_inap = ? WHERE id = ?";
        if ($stmt_update_saran = $conn->prepare($sql_update_saran)) {
            $stmt_update_saran->bind_param("si", $saran_rawat_inap, $prescription_id);
            if ($stmt_update_saran->execute()) {
                $success_message = "Saran rawat inap berhasil diperbarui.";
                // Refresh halaman untuk menampilkan perubahan
                header("location: resep_obat_add.php?mr_id=" . $medical_record_id . "&pres_id=" . $prescription_id);
                exit;
            } else {
                $errors[] = "Gagal memperbarui saran rawat inap: " . $stmt_update_saran->error;
            }
            $stmt_update_saran->close();
        } else {
            $errors[] = "Gagal menyiapkan statement update saran rawat inap: " . $conn->error;
        }
    }
}

?>

<style>
    .prescription-card {
        background-color: #fff;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        padding: 30px;
        margin-top: 20px;
    }
    .prescription-card .card-header {
        background-color: #0d6efd;
        color: #fff;
        padding: 15px 25px;
        border-top-left-radius: 15px;
        border-top-right-radius: 15px;
        margin: -30px -30px 30px -30px; /* Adjust to cover full width of card */
        text-align: center;
        font-size: 1.5rem;
        font-weight: 600;
    }
    .form-group-custom {
        background-color: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        border: 1px solid #e9ecef;
    }
    .form-group-custom label {
        font-weight: 500;
        color: #333;
        margin-bottom: 8px;
        display: block;
    }
    .form-group-custom .form-control {
        border-radius: 8px;
        border-color: #ced4da;
    }
    .btn-action {
        border-radius: 8px;
        padding: 10px 25px;
        font-size: 1.05rem;
    }
    .table-prescription-details th, .table-prescription-details td {
        vertical-align: middle;
    }
    .table-prescription-details th {
        background-color: #e9ecef;
        color: #333;
    }
    .table-prescription-details {
        border-radius: 10px;
        overflow: hidden; /* For rounded corners on table */
    }
</style>

<div class="row">
    <div class="col-12">
        <div class="prescription-card">
            <div class="card-header">
                Resep Obat
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

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success mt-3">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if ($patient_info): ?>
                <div class="form-group-custom">
                    <h5>Informasi Pasien dan Rekap Medis Terkait</h5>
                    <p class="mb-1"><strong>Nama Pasien:</strong> <?php echo htmlspecialchars($patient_info['patient_name']); ?></p>
                    <p class="mb-1"><strong>No. HP:</strong> <?php echo htmlspecialchars($patient_info['nomor_hp']); ?></p>
                    <p class="mb-1"><strong>Alamat:</strong> <?php echo htmlspecialchars($patient_info['alamat']); ?></p>
                    <p class="mb-1"><strong>Tanggal Pemeriksaan:</strong> <?php echo date('d M Y', strtotime($patient_info['tanggal_pemeriksaan'])); ?></p>
                    <p class="mb-1"><strong>Diagnosa:</strong> <?php echo htmlspecialchars($patient_info['diagnosa']); ?></p>
                    <p class="mb-0"><strong>Tindakan:</strong> <?php echo htmlspecialchars($patient_info['tindakan']); ?></p>
                </div>
            <?php endif; ?>


            <form action="resep_obat_add.php?mr_id=<?php echo $medical_record_id; ?>&pres_id=<?php echo $prescription_id; ?>" method="POST" class="mb-4">
                <input type="hidden" name="action" value="add_drug">
                <div class="form-group-custom">
                    <h5 class="mb-3">Tambah Detail Obat</h5>
                    <div class="mb-3">
                        <label for="nama_obat" class="form-label">Nama Obat</label>
                        <input type="text" class="form-control" id="nama_obat" name="nama_obat" placeholder="Contoh: Parasetamol" required>
                    </div>
                    <div class="mb-3">
                        <label for="dosis" class="form-label">Dosis</label>
                        <input type="text" class="form-control" id="dosis" name="dosis" placeholder="Contoh: 500mg" required>
                    </div>
                    <div class="mb-3">
                        <label for="aturan_pakai" class="form-label">Aturan Pakai</label>
                        <input type="text" class="form-control" id="aturan_pakai" name="aturan_pakai" placeholder="Contoh: 3x Sehari" required>
                    </div>
                    <div class="mb-3">
                        <label for="detail_obat" class="form-label">Detail Mengenai Obat</label>
                        <textarea class="form-control" id="detail_obat" name="detail_obat" rows="3" placeholder="Tambahkan keterangan mengenai obat"></textarea>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-action"><i class="fas fa-plus-circle me-2"></i> Tambah Obat</button>
                    </div>
                </div>
            </form>

            <?php if (!empty($prescription_details)): ?>
                <h5 class="mb-3">Daftar Obat dalam Resep Ini</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-prescription-details">
                        <thead>
                            <tr>
                                <th>Nama Obat</th>
                                <th>Dosis</th>
                                <th>Aturan Pakai</th>
                                <th>Detail</th>
                                <th style="width: 100px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prescription_details as $detail): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($detail['nama_obat']); ?></td>
                                    <td><?php echo htmlspecialchars($detail['dosis']); ?></td>
                                    <td><?php echo htmlspecialchars($detail['aturan_pakai']); ?></td>
                                    <td><?php echo htmlspecialchars($detail['detail']); ?></td>
                                    <td>
                                        <form action="resep_obat_add.php?mr_id=<?php echo $medical_record_id; ?>&pres_id=<?php echo $prescription_id; ?>" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus obat ini?');">
                                            <input type="hidden" name="action" value="delete_drug">
                                            <input type="hidden" name="detail_id" value="<?php echo $detail['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash-alt"></i> Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    Belum ada obat ditambahkan ke resep ini.
                </div>
            <?php endif; ?>

            <form action="resep_obat_add.php?mr_id=<?php echo $medical_record_id; ?>&pres_id=<?php echo $prescription_id; ?>" method="POST" class="mt-4">
                <input type="hidden" name="action" value="update_saran">
                <div class="form-group-custom">
                    <h5 class="mb-3">Saran Rawat Inap (Opsional)</h5>
                    <input type="text" class="form-control" id="saran_rawat_inap" name="saran_rawat_inap" placeholder="Contoh: iya/tidak" value="<?php echo htmlspecialchars($patient_info['saran_rawat_inap'] ?? ''); ?>">
                    <div class="d-grid mt-3">
                        <button type="submit" class="btn btn-info btn-action text-white"><i class="fas fa-edit me-2"></i> Perbarui Saran Rawat Inap</button>
                    </div>
                </div>
            </form>

            <div class="d-flex justify-content-between mt-4">
                <a href="riwayat_pasien.php" class="btn btn-secondary btn-action"><i class="fas fa-arrow-left me-2"></i> Kembali ke Riwayat</a>
                <a href="detail_resep.php?pres_id=<?php echo $prescription_id; ?>" class="btn btn-success btn-action"><i class="fas fa-print me-2"></i> Lihat Detail Resep</a>
            </div>

        </div>
    </div>
</div>

<?php
require_once 'footer.php'; // Memuat footer
$conn->close();
?>
