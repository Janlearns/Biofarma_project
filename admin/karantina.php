<?php
// admin/karantina.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../handlers/animal.php';

$auth = new AuthHandler();
$animal_handler = new AnimalHandler();

// Require admin access
$auth->require_admin();


$animals = $animal_handler->get_all_animals();
$units = $animal_handler->get_all_units();
$years = $animal_handler->get_all_years(); // Method ini akan kita buat lagi

// Ambil data pemesanan (untuk tabel utama)
$bookings = [];
$hewan_id = isset($_POST['hewan_id']) ? (int)$_POST['hewan_id'] : null;
$tanggal = isset($_POST['tanggal']) ? $_POST['tanggal'] : null;
$unit = isset($_POST['unit']) ? $_POST['unit'] : null;
$tahun = isset($_POST['tahun']) ? (int)$_POST['tahun'] : null;

// Jika ada filter yang disubmit, ambil data sesuai filter
if ($hewan_id || $tanggal || $unit || $tahun) {
    // Tambahkan $tahun ke parameter fungsi
    $bookings = $animal_handler->get_bookings_by_animal($hewan_id, $tanggal, $unit, $tahun);
} else {
    // Jika tidak ada filter, tampilkan semua pemesanan
    $bookings = $animal_handler->get_bookings_by_animal();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Karantina - Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>

<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar">
            <a href="dashboard.php" class="logo">
                <div class="logo-icon">🐾</div>
                <div class="logo-text">
                    BioVet
                    <span class="logo-sub">dra.0.0</span>
                </div>
            </a>

            <button class="hamburger-menu" id="hamburger-menu">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>

            <ul class="nav-menu" id="nav-menu">
                <li><a href="../dashboard.php">Dashboard</a></li>
                <li><a href="animals.php">Kelola Hewan</a></li>
                <li><a href="karantina.php" class="active">Daftar Karantina</a></li>
                <li class="nav-item">
                    <a href="inventaris.php" class="nav-link">
                        <i class="fas fa-box"></i> Inventory
                    </a>
                </li>
                <li><a href="../dashboard.php">Tambah Admin</a></li>

                <li class="user-info-mobile">
                    <span>
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                        <small style="color: #4A90E2;">(Admin)</small>
                    </span>
                    <a href="../logout.php" class="logout-btn">Logout</a>
                </li>
            </ul>

            <div class="user-info">
                <span class="user-name">
                    <?php echo htmlspecialchars($_SESSION['username']); ?>
                    <small style="color: #4A90E2;">(Admin)</small>
                </span>
                <a href="../logout.php" class="logout-btn">Logout</a>
            </div>
        </nav>
    </header>

    <!-- Admin Header -->
    <section class="admin-header">
        <div class="container">
            <h1>Daftar Karantina</h1>
            <p>Lihat dan kelola semua pemesanan karantina</p>
        </div>

    </section>

    <div class="container" style="padding: 40px 20px;">
        <!-- Dropdown untuk memfilter hewan -->
        <form method="POST" style="margin-bottom: 30px;">
            <div style="display: flex; gap: 20px; align-items: flex-end;">
                <div class="form-group" style="flex: 1;">
                    <label for="hewan_filter">Filter Hewan</label>
                    <select name="hewan_id" id="hewan_filter" class="form-control">
                        <option value="">-- Pilih Hewan --</option>
                        <?php foreach ($animals as $animal): ?>
                            <option value="<?php echo $animal['id']; ?>" <?php echo (isset($_POST['hewan_id']) && $_POST['hewan_id'] == $animal['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($animal['nama_hewan']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label for="unit_filter">Filter Unit Uji</label>
                    <select name="unit" id="unit_filter" class="form-control">
                        <option value="">-- Pilih Unit --</option>
                        <?php foreach ($units as $u): ?>
                            <option value="<?php echo htmlspecialchars($u); ?>" <?php echo (isset($_POST['unit']) && $_POST['unit'] == $u) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($u); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label for="tahun_filter">Filter Tahun</label>
                    <select name="tahun" id="tahun_filter" class="form-control" onchange="this.form.submit()">
                        <option value="">-- Pilih Tahun --</option>
                        <?php foreach ($years as $y): ?>
                            <option value="<?php echo htmlspecialchars($y); ?>" <?php echo (isset($_POST['tahun']) && $_POST['tahun'] == $y) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($y); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label for="tanggal_filter">Filter Tanggal</label>
                    <input type="date" name="tanggal" id="tanggal_filter" class="form-control" value="<?php echo isset($_POST['tanggal']) ? htmlspecialchars($_POST['tanggal']) : ''; ?>">
                </div>
            </div>

        </form>
        <div class="container" style="padding: 40px 20px;">
            <form method="POST" style="margin-bottom: 30px;">
            </form>
            <!-- Tabel Daftar Resi Karantina -->
            <div style="background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); overflow: hidden;">
                <div style="padding: 25px; border-bottom: 1px solid #eee;">
                    <h2 style="margin: 0;">Daftar Karantina</h2>
                </div>

                <div style="overflow-x: auto;">
                    <table style="width: 100%; min-width: 2500px; border-collapse: collapse;">
                        <thead style="background: #f8f9fa;">
                            <tr>
                                <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee;">Tanggal Pesan</th>
                                <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee;">No Order</th>
                                <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee;">Nama User</th>
                                <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee;">Bagian</th>
                                <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee;">Unit Uji</th>
                                <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee;">No Karantina</th>
                                <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee;">No Pesanan</th>
                                <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee;">Hewan</th>
                                <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee;">Uji</th>
                                <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee;">Jumlah</th>
                                <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee;">Berat Uji</th>
                                <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee;">Satuan</th>
                                <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee;">Jenis Kelamin</th>
                                <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee;">Tanggal Datang</th>
                                <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee;">Masa Karantina</th>
                                <th style="padding: 15px; text-align: center; border-bottom: 1px solid #eee;">Status</th>
                                <th style="padding: 15px; text-align: center; border-bottom: 1px solid #eee;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bookings)): ?>
                                <tr>
                                    <td colspan="7" style="padding: 40px; text-align: center; color: #666;">

                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($bookings as $booking): ?>
                                    <tr style="border-bottom: 1px solid #eee;">
                                        <td style="padding: 15px;"><?php echo date('d-m-Y H:i', strtotime($booking['created_at'])); ?></td>
                                        <td style="padding: 15px;"><?php echo htmlspecialchars($booking['no_order']); ?></td>
                                        <td style="padding: 15px;"><?php echo htmlspecialchars($booking['nama_user']); ?></td>
                                        <td style="padding: 15px;"><?php echo htmlspecialchars($booking['bagian']); ?></td>
                                        <td style="padding: 15px;"><?php echo htmlspecialchars($booking['unit']); ?></td>
                                        <td style="padding: 15px;"><?php echo htmlspecialchars($booking['no_karantina']); ?></td>
                                        <td style="padding: 15px;"><?php echo htmlspecialchars($booking['no_pesanan']); ?></td>
                                        <td style="padding: 15px;"><?php echo htmlspecialchars($booking['nama_hewan']); ?></td>
                                        <td style="padding: 15px;"><?php echo htmlspecialchars($booking['uji']); ?></td>
                                        <td style="padding: 15px;"><?php echo htmlspecialchars($booking['jumlah_hewan']); ?> ekor</td>
                                        <td style="padding: 15px;"><?php echo htmlspecialchars($booking['berat']); ?></td>
                                        <td style="padding: 15px;"><?php echo htmlspecialchars($booking['satuan']); ?></td>
                                        <td style="padding: 15px;"><?php echo htmlspecialchars($booking['jenis_kelamin']); ?></td>
                                        <td style="padding: 15px;"><?php echo format_date($booking['tanggal_datang']); ?></td>
                                        <td style="padding: 15px;"> <?php echo format_date($booking['tanggal_datang']) . ' - ' . format_date($booking['tanggal_keluar']); ?></td>
                                        <td style="padding: 15px;">
                                            <select class="status-dropdown" data-no-karantina="<?php echo htmlspecialchars($booking['no_karantina']); ?>">
                                                <option value="pending" <?php echo ($booking['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                <option value="approved" <?php echo ($booking['status'] == 'approved') ? 'selected' : ''; ?>>Approved</option>
                                                <option value="cancel" <?php echo ($booking['status'] == 'cancel') ? 'selected' : ''; ?>>Cancel</option>
                                            </select>
                                        </td>
                                        <td style="padding: 15px; text-align: center;">
                                            <div class="action-dropdown">
                                                <button onclick="toggleDropdown(this)" class="dropdown-toggle">...</button>
                                                <div class="dropdown-menu">
                                                    <a href="../api/generate_receipt_pdf.php?no_karantina=<?php echo urlencode($booking['no_karantina']); ?>">Unduh Karantina</a>
                                                    <a href="../api/generate_receipt_user.php?no_karantina=<?php echo urlencode($booking['no_karantina']); ?>">Unduh BPAB</a>
                                                    <div style="border-top: 1px solid #eee;">
                                                        <a href="#" onclick="showEditOptions('<?php echo htmlspecialchars($booking['no_karantina']); ?>')">Edit Data</a>
                                                    </div>
                                                    <a href="#" onclick="confirmDeleteKarantina('<?php echo htmlspecialchars($booking['no_karantina']); ?>')">Hapus</a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div style="margin-bottom:20px; display:flex; justify-content:center;">
            <button class="btn btn-secondary" onclick="showReportModal()">
                ⬇️ Unduh Report
            </button>
        </div>
        <div style="background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); overflow: hidden;">
        </div>
    </div>
    <div class="modal" id="reportModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Unduh Report</h2>
                <button class="close-btn" onclick="closeModal('reportModal')">&times;</button>
            </div>
            <p style="margin-bottom: 20px; text-align: center;">Pilih opsi unduh report dengan:</p>
            <form id="reportForm" action="../api/generate_report.php" method="GET">
                <div class="form-group">
                    <label for="report_tahun">1. Berdasarkan Tahun</label>
                    <select name="tahun" id="report_tahun" class="form-control">
                        <option value="">Semua Tahun</option>
                        <?php foreach ($years as $y): ?>
                            <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="report_bulan">2. Berdasarkan Bulan</label>
                    <select name="bulan" id="report_bulan" class="form-control">
                        <option value="">Semua Bulan</option>
                        <?php
                        $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                        foreach ($months as $key => $month):
                        ?>
                            <option value="<?php echo $key + 1; ?>"><?php echo $month; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="report_unit">3. Berdasarkan Unit</label>
                    <select name="unit" id="report_unit" class="form-control">
                        <option value="">Semua Unit</option>
                        <?php foreach ($units as $u): ?>
                            <option value="<?php echo htmlspecialchars($u); ?>"><?php echo htmlspecialchars($u); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-success" style="width: 100%;">Unduh</button>
            </form>
        </div>
    </div>

    <!-- Modal Notifikasi Pilihan Edit -->
    <div class="modal" id="editOptionModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Pilih Opsi Edit</h2>
                <button class="close-btn" onclick="closeModal('editOptionModal')">&times;</button>
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <button class="btn" style="margin: 5px;" onclick="editKarantina()">Edit Karantina</button>
                <button class="btn" style="margin: 5px;" onclick="editBPAB()">Edit BPAB</button>
            </div>
        </div>
    </div>

    <!-- Edit Karantina Modal -->
    <div class="modal" id="editKarantinaModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit Resi Karantina</h2>
                <button class="close-btn" onclick="closeModal('editKarantinaModal')">&times;</button>
            </div>

            <form id="editKarantinaForm">
                <input type="hidden" name="no_karantina" id="edit_karantina_no_karantina">

                <div class="form-group">
                    <label for="edit_no_pengiriman">No Pengiriman</label>
                    <input type="text" name="no_pengiriman" id="edit_no_pengiriman" placeholder="Masukkan nomor pengiriman">
                </div>

                <div class="form-group">
                    <label for="edit_jumlah_hewan_datang">Jumlah Hewan Datang</label>
                    <input type="number" name="jumlah_hewan_datang" id="edit_jumlah_hewan_datang" min="0" required>
                </div>

                <div class="form-group">
                    <label for="edit_lulus">Jumlah Lulus Karantina (ekor)</label>
                    <input type="number" name="lulus" id="edit_lulus" min="0" required>
                </div>

                <div class="form-group">
                    <label for="edit_tidak_lulus">Jumlah Tidak Lulus Karantina (ekor)</label>
                    <input type="number" name="tidak_lulus" id="edit_tidak_lulus" min="0" required>
                </div>
                <button type="submit" class="btn btn-success" style="width: 100%;">
                    💾 Simpan Perubahan
                </button>
            </form>
        </div>
    </div>

    <div class="modal" id="editBpabModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit Resi BPAB</h2>
                <button class="close-btn" onclick="closeModal('editBpabModal')">&times;</button>
            </div>

            <form id="editBpabForm">
                <input type="hidden" name="no_karantina" id="edit_bpab_no_karantina">

                <div class="form-group">
                    <label for="edit_bpab_divisi">Dari Divisi/Bagian/Seksi</label>
                    <input type="text" name="divisi" id="edit_bpab_divisi" placeholder="Contoh: Divisi Eksperimen" required>
                </div>

                <div class="form-group">
                    <label for="edit_bpab_kepala">Kepada Divisi/Bagian/Seksi</label>
                    <input type="text" name="kepala" id="edit_bpab_kepala" placeholder="Contoh: Budi Sudarman" required>
                </div>

                <div class="form-group">
                    <label for="edit_bpab_no_permintaan">No. Permintaan</label>
                    <input type="text" name="no_permintaan" id="edit_bpab_no_permintaan" placeholder="Contoh: 123/BPAB/II/2025" required>
                </div>
                <div class="form-group">
                    <label for="edit_bpab_keterangan">Keterangan</label>
                    <textarea name="keterangan" id="edit_bpab_keterangan" rows="4" placeholder="Masukkan keterangan tambahan"></textarea>
                </div>
                <button type="submit" class="btn btn-success" style="width: 100%;">
                    💾 Simpan Perubahan
                </button>
            </form>
        </div>
    </div>
    <div class="modal" id="passwordModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Konfirmasi Akses Admin</h2>
                <button class="close-btn" onclick="closeModal('passwordModal')">&times;</button>
            </div>
            <div class="form-group">
                <label for="admin_password">Masukkan Password Admin</label>
                <input type="password" id="admin_password" class="form-control" required>
            </div>
            <div class="alert" id="password_modal_message" style="display:none;"></div>
            <button id="confirmPasswordBtn" class="btn btn-success" style="width: 100%;">
                Konfirmasi
            </button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const hamburgerBtn = document.getElementById('hamburger-menu');
            const navMenu = document.getElementById('nav-menu');
            if (hamburgerBtn && navMenu) {
                hamburgerBtn.addEventListener('click', function() {
                    navMenu.classList.toggle('active');
                });
            }
        });

        function toggleDropdown(button) {
            const dropdown = button.nextElementSibling;
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }

        window.onclick = function(event) {
            if (!event.target.matches('.dropdown-toggle')) {
                const dropdowns = document.querySelectorAll('.dropdown-menu');
                dropdowns.forEach(dropdown => {
                    dropdown.style.display = 'none';
                });
            }
        }

        let currentNoKarantina = '';

        function showEditOptions(noKarantina) {
            currentNoKarantina = noKarantina;
            document.getElementById('editOptionModal').classList.add('show');
        }

        function editKarantina() {
            closeModal('editOptionModal');
            document.getElementById('edit_karantina_no_karantina').value = currentNoKarantina;
            document.getElementById('editKarantinaModal').classList.add('show');
        }

        function editBPAB() {
            closeModal('editOptionModal');
            document.getElementById('edit_bpab_no_karantina').value = currentNoKarantina;
            document.getElementById('editBpabModal').classList.add('show');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }

        document.getElementById('editKarantinaForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('../api/update_karantina.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    alert(result.message);
                    if (result.success) {
                        closeModal('editKarantinaModal');
                        location.reload();
                    }
                });
        });

        document.getElementById('editBpabForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('../api/update-bpab.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    alert(result.message);
                    if (result.success) {
                        closeModal('editBpabModal');
                        location.reload();
                    }
                });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const passwordModal = document.getElementById('passwordModal');
            const confirmPasswordBtn = document.getElementById('confirmPasswordBtn');
            const adminPasswordInput = document.getElementById('admin_password');
            const messageDiv = document.getElementById('password_modal_message');

            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.style.display = 'none';
            });

            let activeDropdown = null;

            // PENTING: Gunakan Event Delegation pada body untuk tombol toggle
            document.body.addEventListener('click', function(e) {
                if (e.target && e.target.matches('.dropdown-toggle')) {
                    e.preventDefault();

                    // Tutup dropdown lain yang mungkin terbuka
                    document.querySelectorAll('.dropdown-menu').forEach(dropdown => {
                        if (dropdown !== e.target.nextElementSibling) {
                            dropdown.style.display = 'none';
                        }
                    });

                    activeDropdown = e.target.nextElementSibling;
                    adminPasswordInput.value = '';
                    messageDiv.style.display = 'none';
                    showModal('passwordModal');
                }

            });

            confirmPasswordBtn.addEventListener('click', function() {
                const password = adminPasswordInput.value;
                if (password === '') {
                    messageDiv.textContent = 'Password tidak boleh kosong.';
                    messageDiv.className = 'alert alert-error';
                    messageDiv.style.display = 'block';
                    return;
                }

                fetch('../api/check-password.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            password: password
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            closeModal('passwordModal');
                            if (activeDropdown) {
                                activeDropdown.style.display = 'block';
                            }
                        } else {
                            messageDiv.textContent = 'Password salah, coba lagi.';
                            messageDiv.className = 'alert alert-error';
                            messageDiv.style.display = 'block';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        messageDiv.textContent = 'Terjadi kesalahan. Silakan coba lagi.';
                        messageDiv.className = 'alert alert-error';
                        messageDiv.style.display = 'block';
                    });
            });

            const tanggalFilter = document.getElementById('tanggal_filter');
            const unitFilter = document.getElementById('unit_filter');
            const hewanFilter = document.getElementById('hewan_filter');

            const filterForm = tanggalFilter.closest('form');
            const filterButton = document.querySelector('form[method="POST"] button[type="submit"]');

            tanggalFilter.addEventListener('change', function() {
                filterForm.submit();
            });

            unitFilter.addEventListener('change', function() {
                filterForm.submit();
            });

            hewanFilter.addEventListener('change', function() {
                filterForm.submit();
            });

            if (filterButton) {
                filterButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    filterForm.submit();
                });
            }
        });

        function showReportModal() {
            document.getElementById('reportModal').classList.add('show');
        }

        function showModal(modalId) {
            document.getElementById(modalId).classList.add('show');
        }
        document.querySelectorAll('.status-dropdown').forEach(dropdown => {
            dropdown.addEventListener('change', function() {
                const noKarantina = this.dataset.noKarantina;
                const newStatus = this.value;

                fetch('../api/update_booking_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `no_karantina=${encodeURIComponent(noKarantina)}&status=${encodeURIComponent(newStatus)}`
                    })
                    .then(response => {
                        // Pastikan respons dari server OK (status HTTP 200)
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Di sini, cek data.success
                        if (data.success) {
                            alert('Status berhasil diperbarui!');
                            location.reload();
                        } else {
                            // Jika data.success = false, tampilkan pesan dari server
                            alert('Gagal memperbarui status: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Pesan Berhasil Di kirim');
                    });
            });
        });

        function confirmDeleteKarantina(noKarantina) {
            if (confirm("Apakah Anda yakin ingin menghapus pemesanan ini?")) {
                fetch('../api/delete_karantina.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `no_karantina=${encodeURIComponent(noKarantina)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            location.reload(); // Muat ulang halaman untuk melihat perubahan
                        } else {
                            alert('Gagal menghapus: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Terjadi kesalahan saat menghapus pemesanan.');
                    });
            }
        }
    </script>
</body>

</html>