<?php
// rekap_medis_add.php
// Pastikan tidak ada spasi atau karakter lain sebelum tag pembuka <?php di file ini atau di file yang di-require.
require_once 'config.php';
require_once 'header.php'; // Memuat header dan sidebar

// Inisialisasi $conn jika belum ada dari config.php (ini seharusnya sudah ada)
global $conn;

// Inisialisasi variabel untuk menyimpan data form
$patient_data = [];
$medical_data = [];
$diagnosis_data = [];

$errors = [];
$success_message = "";

// Ambil data jika ada ID rekap medis untuk edit
$edit_mode = false;
$medical_record_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($medical_record_id > 0) {
    $edit_mode = true;
    $sql_fetch_record = "SELECT mr.*, p.nama_lengkap AS patient_nama_lengkap, p.tanggal_lahir AS patient_tanggal_lahir,
                            p.jenis_kelamin AS patient_jenis_kelamin, p.alamat AS patient_alamat,
                            p.nomor_hp AS patient_nomor_hp, p.email AS patient_email, p.id AS patient_id_db
                            FROM medical_record mr
                            JOIN patients p ON mr.patient_id = p.id
                            WHERE mr.id = ?";
    if ($stmt_fetch = $conn->prepare($sql_fetch_record)) {
        $stmt_fetch->bind_param("i", $medical_record_id);
        $stmt_fetch->execute();
        $result_fetch = $stmt_fetch->get_result();
        if ($result_fetch->num_rows == 1) {
            $record_to_edit = $result_fetch->fetch_assoc();
            // Isi data ke variabel untuk ditampilkan di form
            $patient_data = [
                'id' => $record_to_edit['patient_id_db'], // Gunakan ID pasien dari DB
                'nama_lengkap' => $record_to_edit['patient_nama_lengkap'],
                'tanggal_lahir' => $record_to_edit['patient_tanggal_lahir'],
                'jenis_kelamin' => $record_to_edit['patient_jenis_kelamin'],
                'alamat' => $record_to_edit['patient_alamat'],
                'nomor_hp' => $record_to_edit['patient_nomor_hp'],
                'email' => $record_to_edit['patient_email']
            ];
            $medical_data = [
                'tanggal_pemeriksaan' => $record_to_edit['tanggal_pemeriksaan'],
                'golongan_darah' => $record_to_edit['golongan_darah'],
                'keluhan_utama' => $record_to_edit['keluhan_utama'],
                'riwayat_medis' => $record_to_edit['riwayat_medis'],
                'alergi' => $record_to_edit['alergi'],
                'obat_dikonsumsi' => $record_to_edit['obat_dikonsumsi']
            ];
            $diagnosis_data = [
                'diagnosa' => $record_to_edit['diagnosa'],
                'rencana_tindakan' => $record_to_edit['rencana_tindakan']
            ];

        } else {
            $errors[] = "Rekap medis tidak ditemukan.";
            $medical_record_id = 0; // Reset ID jika tidak ditemukan
            $edit_mode = false; // Pastikan mode edit false jika tidak ditemukan
        }
        $stmt_fetch->close();
    }
}


// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil semua data dari POST
    $patient_data = [
        'nama_lengkap' => trim($_POST['nama_lengkap'] ?? ''),
        'tanggal_lahir' => trim($_POST['tanggal_lahir'] ?? ''),
        'jenis_kelamin' => trim($_POST['jenis_kelamin'] ?? ''),
        'alamat' => trim($_POST['alamat'] ?? ''),
        'nomor_hp' => trim($_POST['nomor_hp'] ?? ''),
        'email' => trim($_POST['email'] ?? '')
    ];
    $medical_data = [
        'tanggal_pemeriksaan' => trim($_POST['tanggal_pemeriksaan'] ?? ''),
        'golongan_darah' => trim($_POST['golongan_darah'] ?? ''),
        'keluhan_utama' => trim($_POST['keluhan_utama'] ?? ''),
        'riwayat_medis' => trim($_POST['riwayat_medis'] ?? ''),
        'alergi' => trim($_POST['alergi'] ?? ''),
        'obat_dikonsumsi' => trim($_POST['obat_dikonsumsi'] ?? '')
    ];
    $diagnosis_data = [
        'diagnosa' => trim($_POST['diagnosa'] ?? ''),
        'rencana_tindakan' => trim($_POST['rencana_tindakan'] ?? ''),
        'gunakan_resep_obat' => isset($_POST['gunakan_resep_obat']) ? true : false
    ];

    $patient_id_to_use = 0; // Inisialisasi variabel untuk menampung ID pasien akhir

    if ($edit_mode) {
        // Jika dalam mode edit, ID pasien sudah diketahui dari data yang diambil
        $patient_id_to_use = $patient_data['id']; // ID pasien yang sedang diedit
        // Validasi semua field pasien untuk update
        if (empty($patient_data['nama_lengkap'])) $errors['nama_lengkap'] = "Nama lengkap wajib diisi.";
        if (empty($patient_data['tanggal_lahir'])) $errors['tanggal_lahir'] = "Tanggal lahir wajib diisi.";
        if (empty($patient_data['jenis_kelamin'])) $errors['jenis_kelamin'] = "Jenis kelamin wajib diisi.";
        if (empty($patient_data['alamat'])) $errors['alamat'] = "Alamat wajib diisi.";
        if (empty($patient_data['nomor_hp'])) $errors['nomor_hp'] = "Nomor HP wajib diisi.";
    } else {
        // Ini adalah rekam medis baru, selalu membuat pasien baru
        // Validasi semua field patient_data sebagai wajib diisi
        if (empty($patient_data['nama_lengkap'])) $errors['nama_lengkap'] = "Nama lengkap wajib diisi.";
        if (empty($patient_data['tanggal_lahir'])) $errors['tanggal_lahir'] = "Tanggal lahir wajib diisi.";
        if (empty($patient_data['jenis_kelamin'])) $errors['jenis_kelamin'] = "Jenis kelamin wajib diisi.";
        if (empty($patient_data['alamat'])) $errors['alamat'] = "Alamat wajib diisi.";
        if (empty($patient_data['nomor_hp'])) $errors['nomor_hp'] = "Nomor HP wajib diisi.";
    }

    // Validasi untuk data medis
    if (empty($medical_data['tanggal_pemeriksaan'])) $errors['tanggal_pemeriksaan'] = "Tanggal pemeriksaan wajib diisi.";
    if (empty($medical_data['keluhan_utama'])) $errors['keluhan_utama'] = "Keluhan utama wajib diisi.";

    // Validasi untuk data diagnosis
    if (empty($diagnosis_data['diagnosa'])) $errors['diagnosa'] = "Diagnosis wajib diisi.";
    if (empty($diagnosis_data['rencana_tindakan'])) $errors['rencana_tindakan'] = "Rencana tindakan wajib diisi.";


    if (empty($errors)) {
        $user_id = $_SESSION['id']; // ID dokter yang login

        $conn->begin_transaction(); // Mulai transaksi

        try {
            if ($edit_mode) {
                // UPDATE PASIEN
                $sql_update_patient = "UPDATE patients SET nama_lengkap=?, tanggal_lahir=?, jenis_kelamin=?, alamat=?, nomor_hp=?, email=? WHERE id=?";
                if ($stmt_pat_upd = $conn->prepare($sql_update_patient)) {
                    $stmt_pat_upd->bind_param("ssssssi",
                        $patient_data['nama_lengkap'],
                        $patient_data['tanggal_lahir'],
                        $patient_data['jenis_kelamin'],
                        $patient_data['alamat'],
                        $patient_data['nomor_hp'],
                        $patient_data['email'],
                        $patient_id_to_use // Gunakan ID pasien yang ditentukan sebelumnya untuk mode edit
                    );
                    $stmt_pat_upd->execute();
                    $stmt_pat_upd->close();
                } else {
                    throw new Exception("Gagal menyiapkan statement update pasien: " . $conn->error);
                }

                // UPDATE REKAM MEDIS
                $sql_update_medical = "UPDATE medical_record SET tanggal_pemeriksaan=?, golongan_darah=?, keluhan_utama=?, riwayat_medis=?, alergi=?, obat_dikonsumsi=?, diagnosa=?, rencana_tindakan=?, user_id=? WHERE id=?";
                if ($stmt_med_upd = $conn->prepare($sql_update_medical)) {
                    $stmt_med_upd->bind_param("ssssssssii",
                        $medical_data['tanggal_pemeriksaan'],
                        $medical_data['golongan_darah'],
                        $medical_data['keluhan_utama'],
                        $medical_data['riwayat_medis'],
                        $medical_data['alergi'],
                        $medical_data['obat_dikonsumsi'],
                        $diagnosis_data['diagnosa'],
                        $diagnosis_data['rencana_tindakan'],
                        $user_id,
                        $medical_record_id
                    );
                    $stmt_med_upd->execute();
                    $stmt_med_upd->close();
                } else {
                    throw new Exception("Gagal menyiapkan statement update rekap medis: " . $conn->error);
                }

                // Hapus resep lama jika ada, lalu tambahkan yang baru jika checkbox dicentang
                $sql_delete_prescription = "DELETE FROM prescriptions WHERE medical_record_id = ?";
                if ($stmt_del_pres = $conn->prepare($sql_delete_prescription)) {
                    $stmt_del_pres->bind_param("i", $medical_record_id);
                    $stmt_del_pres->execute();
                    $stmt_del_pres->close();
                } else {
                     throw new Exception("Gagal menghapus resep lama: " . $conn->error);
                }

            } else {
                // TAMBAH REKAM MEDIS BARU (membuat pasien baru)
                $sql_insert_patient = "INSERT INTO patients (nama_lengkap, tanggal_lahir, jenis_kelamin, alamat, nomor_hp, email) VALUES (?, ?, ?, ?, ?, ?)";
                if ($stmt_pat = $conn->prepare($sql_insert_patient)) {
                    $stmt_pat->bind_param("ssssss",
                        $patient_data['nama_lengkap'],
                        $patient_data['tanggal_lahir'],
                        $patient_data['jenis_kelamin'],
                        $patient_data['alamat'],
                        $patient_data['nomor_hp'],
                        $patient_data['email']
                    );
                    $stmt_pat->execute();
                    $patient_id_to_use = $conn->insert_id; // Dapatkan ID pasien baru yang dibuat secara otomatis
                    $stmt_pat->close();
                } else {
                    throw new Exception("Gagal menyiapkan statement pasien: " . $conn->error);
                }

                // Masukkan rekam medis
                $sql_insert_medical = "INSERT INTO medical_record (patient_id, tanggal_pemeriksaan, golongan_darah, keluhan_utama, riwayat_medis, alergi, obat_dikonsumsi, diagnosa, rencana_tindakan, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                if ($stmt_med = $conn->prepare($sql_insert_medical)) {
                    $stmt_med->bind_param("issssssssi",
                        $patient_id_to_use, // Gunakan ID pasien yang baru dibuat
                        $medical_data['tanggal_pemeriksaan'],
                        $medical_data['golongan_darah'],
                        $medical_data['keluhan_utama'],
                        $medical_data['riwayat_medis'],
                        $medical_data['alergi'],
                        $medical_data['obat_dikonsumsi'],
                        $diagnosis_data['diagnosa'],
                        $diagnosis_data['rencana_tindakan'],
                        $user_id
                    );
                    $stmt_med->execute();
                    $medical_record_id = $conn->insert_id; // Dapatkan ID rekam medis baru
                    $stmt_med->close();
                } else {
                    throw new Exception("Gagal menyiapkan statement rekam medis: " . $conn->error);
                }
            }

            // Tambahkan resep obat jika checkbox 'Gunakan Resep Obat' dicentang
            if ($diagnosis_data['gunakan_resep_obat']) {
                $sql_insert_prescription = "INSERT INTO prescriptions (medical_record_id, nama_pasien, id_pasien, alamat_pasien, no_hp_pasien, tanggal_pemeriksaan, diagnosa, tindakan, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                if ($stmt_pres = $conn->prepare($sql_insert_prescription)) {
                    // FIX: Simpan hasil penggabungan string ke variabel sementara
                    $formatted_patient_id_for_pres = "P-" . $patient_id_to_use;

                    $stmt_pres->bind_param("isssssssi",
                        $medical_record_id,
                        $patient_data['nama_lengkap'], // Nama dari POST (jika baru) atau DB (jika edit)
                        $formatted_patient_id_for_pres, // Gunakan variabel sementara
                        $patient_data['alamat'],
                        $patient_data['nomor_hp'],
                        $medical_data['tanggal_pemeriksaan'],
                        $diagnosis_data['diagnosa'],
                        $diagnosis_data['rencana_tindakan'],
                        $user_id
                    );
                    $stmt_pres->execute();
                    $prescription_id = $conn->insert_id;
                    $stmt_pres->close();
                    // Jika berhasil, redirect ke form detail resep obat
                    $conn->commit();
                    header("location: resep_obat_add.php?mr_id=" . $medical_record_id . "&pres_id=" . $prescription_id);
                    exit;
                } else {
                    throw new Exception("Gagal menyiapkan statement resep: " . $conn->error);
                }
            }

            $conn->commit(); // Commit transaksi jika semua berhasil
            $success_message = $edit_mode ? "Rekap medis berhasil diperbarui!" : "Rekap medis berhasil ditambahkan!";
            // Redirect ke halaman riwayat pasien atau detail rekap medis
            header("location: riwayat_pasien.php?patient_id=" . $patient_id_to_use . "&medical_record_id=" . $medical_record_id);
            exit;

        } catch (Exception $e) {
            $conn->rollback(); // Rollback transaksi jika ada error
            $errors[] = "Error saat menyimpan data: " . $e->getMessage();
        }
    }
}

// Data pasien untuk mengisi form
// Jika ada error validasi, data dari POST akan mengisi ulang form.
// Jika mode edit, data dari DB akan mengisi form.
// Jika baru dan tidak ada POST, akan kosong.
$current_patient_id_display = '';
if ($edit_mode && isset($patient_data['id'])) {
    $current_patient_id_display = $patient_data['id'];
}

?>

<style>
    .form-wizard-card {
        background-color: #fff;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        padding: 30px;
        margin-top: 20px;
    }
    /* Menghilangkan progress tracker karena ini satu halaman */
    .progress-tracker {
        display: none;
    }

    /* Form styling */
    .form-label {
        font-weight: 500;
        color: #333;
    }
    .form-control, .form-select {
        border-radius: 8px;
        padding: 10px 15px;
        border: 1px solid #ced4da;
    }
    .form-control:focus, .form-select:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13,110,253,.25);
    }
    .input-group-text {
        border-top-left-radius: 8px;
        border-bottom-left-radius: 8px;
        background-color: #e9ecef;
        border-right: none;
    }
    .input-group .form-control {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }
    .btn-action {
        border-radius: 8px;
        padding: 10px 25px;
        font-size: 1.05rem;
    }
    .btn-primary {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }
    .btn-primary:hover {
        background-color: #0b5ed7;
        border-color: #0a58ca;
    }
    .btn-secondary {
        background-color: #6c757d;
        border-color: #6c757d;
    }
    .btn-secondary:hover {
        background-color: #5c636a;
        border-color: #565e64;
    }
    .btn-success {
        background-color: #28a745;
        border-color: #28a745;
    }
    .btn-success:hover {
        background-color: #218838;
        border-color: #1e7e34;
    }
    .form-section {
        display: block; /* Selalu tampilkan semua bagian */
    }
    .form-header {
        background-color: #0d6efd;
        color: #fff;
        padding: 15px 25px;
        border-top-left-radius: 15px;
        border-top-right-radius: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .form-header h4 {
        margin-bottom: 0;
        font-weight: 600;
    }
    .form-check-input {
        border-radius: 5px; /* Adjust for checkbox */
    }
</style>

<div class="row">
    <div class="col-12">
        <div class="form-wizard-card">
            <div class="form-header">
                <h4>Sistem Rekam Medis</h4>
                <p class="mb-0">
                    <?php if ($edit_mode): ?>
                        Edit Rekam Medis Pasien (ID: <?php echo htmlspecialchars($medical_record_id); ?>)
                    <?php else: ?>
                        Tambahkan Rekam Medis Pasien Baru
                    <?php endif; ?>
                </p>
                <button class="btn btn-sm btn-outline-light"><i class="fas fa-plus"></i></button>
            </div>

            <!-- Progress tracker dihapus karena ini satu halaman -->
            <div class="d-none progress-tracker mt-4"></div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger mt-3">
                    Mohon perbaiki kesalahan berikut:
                    <ul>
                        <?php foreach ($errors as $error_msg): ?>
                            <li><?php echo $error_msg; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success mt-3">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <form id="medicalRecordForm" action="rekap_medis_add.php<?php echo $medical_record_id > 0 ? '?id=' . $medical_record_id : ''; ?>" method="POST">
                <input type="hidden" name="medical_record_id" value="<?php echo $medical_record_id; ?>">

                <!-- Step 1: Informasi Pasien -->
                <div class="form-section active" id="step1">
                    <h5 class="mb-4 mt-4"><i class="fas fa-user-alt me-2"></i> Informasi Pasien</h5>
                    <?php if ($edit_mode): // Tampilkan ID pasien yang sedang diedit jika dalam mode edit ?>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="patient_id_display" class="form-label">ID Pasien</label>
                            <input type="text" class="form-control" id="patient_id_display" value="<?php echo htmlspecialchars($patient_data['id'] ?? ''); ?>" readonly disabled>
                            <input type="hidden" name="patient_id" value="<?php echo htmlspecialchars($patient_data['id'] ?? ''); ?>">
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nama_lengkap" class="form-label">Nama Lengkap *</label>
                            <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" placeholder="Nama lengkap pasien" required value="<?php echo htmlspecialchars($patient_data['nama_lengkap'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="tanggal_lahir" class="form-label">Tanggal Lahir *</label>
                            <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" required value="<?php echo htmlspecialchars($patient_data['tanggal_lahir'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="jenis_kelamin" class="form-label">Jenis Kelamin *</label>
                            <select class="form-select" id="jenis_kelamin" name="jenis_kelamin" required>
                                <option value="">Pilih Jenis Kelamin</option>
                                <option value="Laki-laki" <?php echo (isset($patient_data['jenis_kelamin']) && $patient_data['jenis_kelamin'] == 'Laki-laki') ? 'selected' : ''; ?>>Laki-laki</option>
                                <option value="Perempuan" <?php echo (isset($patient_data['jenis_kelamin']) && $patient_data['jenis_kelamin'] == 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="nomor_hp" class="form-label">Nomor HP *</label>
                            <input type="text" class="form-control" id="nomor_hp" name="nomor_hp" placeholder="08123456789" required value="<?php echo htmlspecialchars($patient_data['nomor_hp'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="row mb-3">
                         <div class="col-md-6">
                            <label for="alamat" class="form-label">Alamat *</label>
                            <textarea class="form-control" id="alamat" name="alamat" rows="3" placeholder="Alamat lengkap pasien" required><?php echo htmlspecialchars($patient_data['alamat'] ?? ''); ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="pasien@example.com" value="<?php echo htmlspecialchars($patient_data['email'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <!-- Step 2: Informasi Medis -->
                <div class="form-section active" id="step2">
                    <h5 class="mb-4 mt-4"><i class="fas fa-heartbeat me-2"></i> Informasi Medis</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="tanggal_pemeriksaan" class="form-label">Tanggal Pemeriksaan *</label>
                            <input type="date" class="form-control" id="tanggal_pemeriksaan" name="tanggal_pemeriksaan" value="<?php echo htmlspecialchars($medical_data['tanggal_pemeriksaan'] ?? date('Y-m-d')); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="golongan_darah" class="form-label">Golongan Darah</label>
                            <select class="form-select" id="golongan_darah" name="golongan_darah">
                                <option value="">Pilih Golongan Darah</option>
                                <option value="A" <?php echo (isset($medical_data['golongan_darah']) && $medical_data['golongan_darah'] == 'A') ? 'selected' : ''; ?>>A</option>
                                <option value="B" <?php echo (isset($medical_data['golongan_darah']) && $medical_data['golongan_darah'] == 'B') ? 'selected' : ''; ?>>B</option>
                                <option value="AB" <?php echo (isset($medical_data['golongan_darah']) && $medical_data['golongan_darah'] == 'AB') ? 'selected' : ''; ?>>AB</option>
                                <option value="O" <?php echo (isset($medical_data['golongan_darah']) && $medical_data['golongan_darah'] == 'O') ? 'selected' : ''; ?>>O</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="keluhan_utama" class="form-label">Keluhan Utama *</label>
                        <textarea class="form-control" id="keluhan_utama" name="keluhan_utama" rows="3" placeholder="Keluhan utama yang dirasakan pasien" required><?php echo htmlspecialchars($medical_data['keluhan_utama'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="riwayat_medis" class="form-label">Riwayat Medis</label>
                        <textarea class="form-control" id="riwayat_medis" name="riwayat_medis" rows="3" placeholder="Riwayat penyakit atau kondisi medis sebelumnya"><?php echo htmlspecialchars($medical_data['riwayat_medis'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="alergi" class="form-label">Alergi</label>
                        <textarea class="form-control" id="alergi" name="alergi" rows="2" placeholder="Alergi obat, makanan, atau lainnya"><?php echo htmlspecialchars($medical_data['alergi'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="obat_dikonsumsi" class="form-label">Obat yang Sedang Dikonsumsi</label>
                        <textarea class="form-control" id="obat_dikonsumsi" name="obat_dikonsumsi" rows="2" placeholder="Daftar obat yang sedang dikonsumsi pasien"><?php echo htmlspecialchars($medical_data['obat_dikonsumsi'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Step 3: Diagnosis & Tindakan -->
                <div class="form-section active" id="step3">
                    <h5 class="mb-4 mt-4"><i class="fas fa-stethoscope me-2"></i> Diagnosis & Tindakan</h5>
                    <div class="mb-3">
                        <label for="diagnosa" class="form-label">Diagnosis *</label>
                        <textarea class="form-control" id="diagnosa" name="diagnosa" rows="3" placeholder="Diagnosis medis berdasarkan pemeriksaan" required><?php echo htmlspecialchars($diagnosis_data['diagnosa'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="rencana_tindakan" class="form-label">Rencana Tindakan *</label>
                        <textarea class="form-control" id="rencana_tindakan" name="rencana_tindakan" rows="3" placeholder="Tindakan medis yang akan dilakukan atau direkomendasikan" required><?php echo htmlspecialchars($diagnosis_data['rencana_tindakan'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="gunakan_resep_obat" name="gunakan_resep_obat" value="1" <?php echo (isset($diagnosis_data['gunakan_resep_obat']) && $diagnosis_data['gunakan_resep_obat']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="gunakan_resep_obat">
                            Menggunakan Resep Obat
                        </label>
                    </div>
                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" name="action" value="save" class="btn btn-success btn-action"><i class="fas fa-save me-2"></i> Simpan Rekam Medis</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

