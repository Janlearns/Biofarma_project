<?php
// dashboard.php
session_start();
require_once 'config/database.php';
require_once 'auth/auth.php';
require_once 'handlers/animal.php';

$auth = new AuthHandler();
$animal_handler = new AnimalHandler();


// Require login
$auth->require_login();

// Logika Pencarian Admin
$message = '';
$message_type = '';
$search_result = null; // Ini untuk pencarian user
$search_result_admin = null; // Ini untuk pencarian admin

// Redirect if already logged in
// Logika untuk pencarian admin
if (isset($_POST['search_booking_admin'])) {
    $term = sanitize_input($_POST['term']);
    $type = sanitize_input($_POST['type']);
    if (!empty($term)) {
        // Panggil fungsi pencarian dengan tipe yang berbeda
        $search_result_admin = $animal_handler->search_booking($term, $type);
        if (!$search_result_admin) {
            $message = 'Data tidak ditemukan';
            $message_type = 'error';
        }
    } else {
        $message = 'Harap masukkan kata kunci pencarian';
        $message_type = 'error';
    }
}

// Logika untuk pencarian user
if (isset($_POST['search_booking_user'])) {
    $no_pesanan = sanitize_input($_POST['no_pesanan']);
    if (!empty($no_pesanan)) {
        $search_result = $animal_handler->search_booking($no_pesanan, 'no_pesanan');
        if (!$search_result) {
            $message = 'Nomor Pesanan tidak ditemukan';
            $message_type = 'error';
        }
    }
}

// Tambahkan function format_date jika belum ada
if (!function_exists('format_date')) {
    function format_date($date)
    {
        if (empty($date)) return '-';
        return date('d/m/Y', strtotime($date));
    }
}

// Require login
$auth->require_login();

// Get all animals
$animals = $animal_handler->get_all_animals();

$message = '';
$message_type = '';


if (isset($_POST['book_animal'])) {
    $hewan_id = (int)$_POST['hewan_id'];
    $tanggal_datang = $_POST['tanggal_datang'];
    $tanggal_keluar = $_POST['tanggal_keluar'];
    $uji = sanitize_input($_POST['uji']);
    $jumlah_hewan = (int)$_POST['jumlah_hewan'];
    $berat = sanitize_input($_POST['berat']);
    $satuan = sanitize_input($_POST['satuan']);
    $nama_user = sanitize_input($_POST['nama_user']);
    $bagian = sanitize_input($_POST['bagian']);
    $unit = sanitize_input($_POST['unit']);

    $jenis_kelamin = sanitize_input($_POST['jenis_kelamin']);

    $no_pesanan = isset($_POST['no_pesanan']) ? sanitize_input($_POST['no_pesanan']) : '';

    if (empty($tanggal_datang) || empty($tanggal_keluar) || empty($uji) || empty($jumlah_hewan) || empty($berat) || empty($satuan) || empty($no_pesanan) || empty($jenis_kelamin) ||  empty($bagian) || empty($nama_user) || empty($unit)) {
        $message = 'Semua field harus diisi';
        $message_type = 'error';
    } else {
        $booking_result = $animal_handler->book_animal(
            $_SESSION['user_id'],
            $hewan_id,
            $tanggal_datang,
            $tanggal_keluar,
            $uji,
            $jumlah_hewan,
            $berat,
            $satuan,
            $no_pesanan,
            $jenis_kelamin,
            $nama_user,
            $bagian,
            $unit
        );
        $message = $booking_result['message'];
        $message_type = $booking_result['success'] ? 'success' : 'error';

        if ($booking_result['success']) {
            $booking_data = $booking_result['data'];
            $animals = $animal_handler->get_all_animals();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Marketplace Hewan</title>
    <link rel="stylesheet" href="assets/style.css">
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
                <li><a href="dashboard.php">Dashboard</a></li>
                <?php if ($auth->is_admin()): ?>
                    <li><a href="admin/animals.php">Kelola Hewan</a></li>
                    <li><a href="admin/karantina.php">Daftar Karantina</a></li>
                    <li><a href="admin/inventaris.php">inventory</a></li>
                    <li><a href="admin/add-admin.php" id="addAdminLink">Tambah Admin</a></li>
                <?php endif; ?>

                <!-- Tambahkan tautan logout ke dalam menu hamburger -->
                <li class="user-info-mobile">
                    <span>
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                        <?php if ($auth->is_admin()): ?>
                            <small style="color: #4A90E2;">(Admin)</small>
                        <?php endif; ?>
                    </span>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </li>
            </ul>

            <!-- Versi desktop, disembunyikan di mobile -->
            <div class="user-info">
                <span class="user-name">
                    <?php echo htmlspecialchars($_SESSION['username']); ?>
                    <?php if ($auth->is_admin()): ?>
                        <small style="color: #4A90E2;">(Admin)</small>
                    <?php endif; ?>
                </span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Find the Perfect Stay for Your Animals Laboratory</h1>
                <p>Book from thousands of Animals Laboratory cages and homes.</p>
            </div>
        </div>
    </section>

    <!-- Search Section -->
    <!-- dashboard.php -->

    <!-- Search Section -->
    <?php if (!$auth->is_admin()): ?>
        <section class="search-section">
            <div class="search-container">
                <form method="POST" style="display: flex; align-items: flex-end; gap: 15px; width: 100%;">
                    <div class="search-field" style="flex: 1;">
                        <label for="search_no_pesanan">🔍 Cari Pemesanan Anda</label>
                        <input type="text" name="no_pesanan" id="search_no_pesanan" placeholder="Masukkan nomor pesanan">
                    </div>
                    <button type="submit" name="search_booking_user" class="search-btn">
                        Cari
                    </button>
                </form>
            </div>
        </section>
    <?php endif; ?>
    <?php if ($auth->is_admin()): ?>
        <section class="search-section">
            <div class="search-container" style="max-width: none;">
                <form method="POST" style="display: flex; gap: 15px; align-items: flex-end; width: 100%;">
                    <div class="form-group" style="flex: 1;">
                        <label for="search_admin_term">🔍 Cari Pemesanan</label>
                        <input type="text" name="term" id="search_admin_term" placeholder="Masukkan No. Karantina atau No. Pesanan">
                    </div>
                    <div class="form-group">
                        <label for="search_admin_type">Tipe Pencarian</label>
                        <select name="type" id="search_admin_type" class="form-control" style="height: 48px;">
                            <option value="no_karantina">No. Karantina</option>
                            <option value="no_pesanan">No. Pesanan</option>
                        </select>
                    </div>
                    <button type="submit" name="search_booking_admin" class="btn search-btn" style="height: 48px; margin-bottom: 20px">
                        Cari
                    </button>
                </form>
            </div>
        </section>
    <?php endif; ?>

    <!-- Alert Messages -->
    <?php if ($message): ?>
        <div class="container" style="margin-top: 40px;">
            <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Search Result -->
    <?php if ($search_result): ?>
        <div class="modal show" id="searchResultModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title">Hasil Pencarian No Pemesanan</h2>
                    <button class="close-btn" onclick="closeModal('searchResultModal')">&times;</button>
                </div>
                <div class="alert alert-info">
                    <p><strong>No Order:</strong> <?php echo htmlspecialchars($search_result['no_order']); ?></p>
                    <p><strong>No Pesanan:</strong> <?php echo htmlspecialchars($search_result['no_pesanan']); ?></p>
                    <p><strong>Nama User:</strong> <?php echo htmlspecialchars($search_result['nama_user']); ?></p>
                    <p><strong>bagian:</strong> <?php echo htmlspecialchars($search_result['bagian']); ?></p>
                    <p><strong>No Karantina:</strong> <?php echo htmlspecialchars($search_result['no_karantina']); ?></p>
                    <p><strong>Hewan:</strong> <?php echo htmlspecialchars($search_result['nama_hewan']); ?></p>
                    <p><strong>Jumlah Hewan:</strong> <?php echo htmlspecialchars($search_result['jumlah_hewan']); ?> ekor</p>
                    <p><strong>Kandang:</strong> <?php echo htmlspecialchars($search_result['nomor_kandang']); ?></p>
                    <p><strong>Tanggal Datang:</strong> <?php echo format_date($search_result['tanggal_datang']); ?></p>
                    <p><strong>Masa Uji:</strong> <?php echo format_date($search_result['tanggal_datang']); ?> - <?php echo format_date($search_result['tanggal_keluar']); ?></p>
                    <p><strong>Jenis Uji/Experimen:</strong> <?php echo htmlspecialchars($search_result['uji']); ?></p>
                    <p><strong>Status:</strong> <?php echo ucfirst($search_result['status']); ?></p>
                    <p><strong>Unit Uji:</strong> <?php echo ucfirst($search_result['unit']); ?></p>
                    <p><strong>Sex:</strong> <?php echo ucfirst($search_result['jenis_kelamin']); ?></p>
                    <p><strong>Berat Hewan:</strong> <?php echo htmlspecialchars($search_result['berat']); ?></p>
                </div>

                <div style="margin-top: 20px; text-align: center;">
                    <button class="btn btn-secondary" onclick="closeModal('searchResultModal')">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <?php if ($search_result_admin): ?>
        <div class="modal show" id="searchResultAdminModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title">Hasil Pencarian Pemesanan</h2>
                    <button class="close-btn" onclick="closeModal('searchResultAdminModal')">&times;</button>
                </div>
                <div class="alert alert-info">
                    <p><strong>No Order:</strong> <?php echo htmlspecialchars($search_result_admin['no_order'] ?? ''); ?></p>
                    <p><strong>No Pesanan:</strong> <?php echo htmlspecialchars($search_result_admin['no_pesanan'] ?? ''); ?></p>
                    <p><strong>Nama User:</strong> <?php echo htmlspecialchars($search_result_admin['nama_user'] ?? ''); ?></p>
                    <p><strong>Bagian:</strong> <?php echo htmlspecialchars($search_result_admin['bagian'] ?? ''); ?></p>
                    <p><strong>No Karantina:</strong> <?php echo htmlspecialchars($search_result_admin['no_karantina'] ?? ''); ?></p>
                    <p><strong>Hewan:</strong> <?php echo htmlspecialchars($search_result_admin['nama_hewan'] ?? ''); ?></p>
                    <p><strong>Jumlah Hewan:</strong> <?php echo htmlspecialchars($search_result_admin['jumlah_hewan'] ?? ''); ?> ekor</p>
                    <p><strong>Kandang:</strong> <?php echo htmlspecialchars($search_result_admin['nomor_kandang'] ?? ''); ?></p>
                    <p><strong>Tanggal Datang:</strong> <?php echo format_date($search_result_admin['tanggal_datang'] ?? ''); ?></p>
                    <p><strong>Masa Uji:</strong> <?php echo format_date($search_result_admin['tanggal_datang'] ?? '') . ' - ' . format_date($search_result_admin['tanggal_keluar'] ?? ''); ?></p>
                    <p><strong>Jenis Uji/Experimen:</strong> <?php echo htmlspecialchars($search_result_admin['uji'] ?? ''); ?></p>
                    <p><strong>Status:</strong> <?php echo ucfirst($search_result_admin['status'] ?? ''); ?></p>
                    <p><strong>Unit Uji:</strong> <?php echo ucfirst($search_result_admin['unit'] ?? ''); ?></p>
                    <p><strong>Sex:</strong> <?php echo ucfirst($search_result_admin['jenis_kelamin'] ?? ''); ?></p>
                    <p><strong>Berat Hewan:</strong> <?php echo htmlspecialchars($search_result_admin['berat'] ?? ''); ?></p>
                </div>
                <div style="margin-top: 20px; text-align: center;">
                    <button class="btn btn-secondary" onclick="closeModal('searchResultAdminModal')">Tutup</button>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <!-- Booking Receipt -->
    <?php if (isset($booking_data)): ?>
        <div class="container" style="margin-top: 40px;">
            <div class="receipt fade-in">
                <div class="receipt-header">
                    <div class="receipt-subtitle">Thanks for order</div>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Silahkan Tunggu Pesanan Di Setujui Oleh Admin</span>
                </div>
                <div style="text-align: center; margin-top: 25px;">
                    <div style="text-align: center; margin-top: 25px;">
                    </div>
                </div>
            </div>
        </div>
        </div>
    <?php endif; ?>

    <!-- Animals Section -->
    <section class="animals-section">
        <div class="container">
            <div class="section-title">
                <h2>Available Animals</h2>
                <p>Choose from our selection of well-cared animals</p>
                <?php if ($auth->is_admin()): ?>
                    <button class="add-animal-btn" onclick="location.href='admin/animals.php'">
                        ➕ Tambah Hewan Baru
                    </button>
                <?php endif; ?>
            </div>

            <div class="animals-grid" id="animals-grid">
                <?php foreach ($animals as $animal): ?>
                    <div class="animal-card" data-animal-id="<?php echo $animal['id']; ?>">
                        <div class="animal-image" style="height: 250px; overflow: hidden; position: relative;">
                            <?php if (!empty($animal['foto_hewan'])): ?>
                                <img src="uploads/<?php echo htmlspecialchars($animal['foto_hewan']); ?>" alt="<?php echo htmlspecialchars($animal['nama_hewan']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <!-- Placeholder yang lebih sederhana dan memenuhi area -->
                                <div style="width: 100%; height: 100%; background: #f0f2f0; display: flex; align-items: center; justify-content: center; font-size: 18px; color: #999;">
                                    Foto tidak tersedia
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="animal-info">
                            <div class="animal-header">
                                <h3 class="animal-name"><?php echo htmlspecialchars($animal['nama_hewan']); ?></h3>
                            </div>

                            <div class="animal-stats">
                                <div class="stat">
                                    <span class="stat-icon">🏠</span>
                                    <span>Kapasitas: <?php echo $animal['total_slot']; ?></span>
                                </div>
                                <div class="stat">
                                    <span class="stat-icon">📦</span>
                                    <span>Kandang: <?php echo $animal['total_kandang']; ?></span>
                                </div>
                                <div class="stat">
                                    <span class="stat-icon">🐾</span>
                                    <span>Realisasi: <?php echo $animal['total_terisi'] ?? 0; ?></span>
                                </div>
                            </div>

                            <p style="color: #666; font-size: 14px; margin-bottom: 15px;">
                                <?php echo htmlspecialchars(substr($animal['deskripsi'], 0, 100)); ?>...
                            </p>
                            <div class="animal-footer">
                                <button class="view-btn" onclick="viewAnimalDetail(<?php echo $animal['id']; ?>)">
                                    View Details
                                </button>
                                <button class="order-btn" onclick="showOrderModal(<?php echo $animal['id']; ?>)">
                                    Order
                                </button>
                                <?php if ($auth->is_admin()): ?>
                                    <div style="text-align: right;">
                                        <div style="color: #28a745; font-weight: bold;">
                                            Sisa: <?php echo $animal['total_sisa_slot'] ?? $animal['total_slot']; ?> RKAP
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Order Modal -->
    <div class="modal" id="orderModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Pesan Hewan</h2>
                <button class="close-btn" onclick="closeModal('orderModal')">&times;</button>
            </div>
            <form method="POST" id="orderForm">
                <div class="form-group">
                    <label for="order_pesanan">No Pesanan</label>
                    <input type="text" name="no_pesanan" id="order_pesanan" placeholder="Masukkan nomor pesanan" required>
                </div>
                <div class="form-group">
                    <label for="order_divisi">Bagian</label>
                    <input type="text" name="bagian" id="order_divisi" placeholder="Masukkan nama bagian" required>
                </div>
                <div class="form-group">
                    <label for="order_user_name">Nama User</label>
                    <input type="text" name="nama_user" id="order_user_name" placeholder="Masukkan nama Anda" required>
                </div>
                <div class="form-group">
                    <label for="order_animal">Pilih Hewan</label>
                    <select name="hewan_id" id="order_animal" required>
                        <option value="">-- Pilih Hewan --</option>
                        <?php foreach ($animals as $animal): ?>
                            <?php if (($animal['total_sisa_slot'] ?? $animal['total_slot']) > 0): ?>
                                <option value="<?php echo $animal['id']; ?>">
                                    <?php echo htmlspecialchars($animal['nama_hewan']); ?>
                                    <?php if ($auth->is_admin()): ?>
                                        (Sisa: <?php echo $animal['total_sisa_slot'] ?? $animal['total_slot']; ?> slot)
                                    <?php endif; ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="order_quantity">Jumlah Hewan</label>
                    <input type="number" name="jumlah_hewan" id="order_quantity" required min="1" value="1">
                </div>

                <div class="form-group">
                    <label for="order_test">Uji</label>
                    <input type="text" name="uji" id="order_test" placeholder="Contoh: Tetanus, Rabies, dll" required>
                </div>
                <div class="form-group">
                    <label for="order_date">Tanggal Datang</label>
                    <input type="date" name="tanggal_datang" id="order_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label for="order_end_date">Tanggal Uji</label>
                    <input type="date" name="tanggal_keluar" id="order_end_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label for="order_weight">Berat (gram)</label>
                    <input type="text" name="berat" id="order_weight" placeholder="Contoh: 1-2gr" required>
                </div>
                <div class="form-group">
                    <label for="order_sex">Jenis Kelamin</label>
                    <select name="jenis_kelamin" id="order_sex" required>
                        <option value="">-- Pilih Jenis Kelamin --</option>
                        <option value="Jantan">Jantan</option>
                        <option value="Betina">Betina</option>
                        <option value="Sejenisnya">Sejenisnya</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="order_unit_name">Unit Uji</label>
                    <select name="unit" id="order_unit_name" required>
                        <option value="">-- Pilih Unit --</option>
                        <option value="Unit 1">Unit 1</option>
                        <option value="Unit 2">Unit 2</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="order_unit">Satuan</label>
                    <input type="text" name="satuan" id="order_unit" placeholder="Contoh: gr, kg, dsb" required>
                </div>
                <button type="submit" name="book_animal" class="btn btn-success">
                    Pesan Sekarang
                </button>
            </form>
        </div>
    </div>

    <!-- Search Karantina Modal -->
    <div class="modal" id="searchModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Cari No Karantina</h2>
                <button class="close-btn" onclick="closeModal('searchModal')">&times;</button>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label for="search_karantina">No Karantina</label>
                    <input type="text" name="no_karantina" id="search_karantina" placeholder="Contoh: 2Q/K/8/VII/2025" required>
                </div>
                <div>
                    <button type="submit" name="search_karantina" class="btn">
                        🔍 Cari
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Animal Detail Modal -->
    <div class="modal" id="detailModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="detail-title">Detail Hewan</h2>
                <button class="close-btn" onclick="closeModal('detailModal')">&times;</button>
            </div>

            <div id="detail-content">
                <div class="form-group">
                    <label><strong>Deskripsi:</strong></label>
                    <p>${data.animal.deskripsi}</p>
                </div>

                <div class="form-group">
                    <label><strong>Total Kandang:</strong></label>
                    <p>${data.animal.total_kandang} kandang</p>
                </div>

                <div class="form-group">
                    <label><strong>Kapasitas per Kandang:</strong></label>
                    <p>${data.animal.kapasitas_per_kandang} ekor</p>
                </div>

                <div class="form-group">
                    <label><strong>Total Slot:</strong></label>
                    <p>${data.animal.total_slot} slot</p>
                </div>
                <div class="form-group">
                    <label><strong>RKAP
                            :</strong></label>
                    <p>${data.animal.total_terisi || 0} slot</p>
                </div>

                <div class="form-group">
                    <label><strong>Slot Tersisa:</strong></label>
                    <p>${data.animal.total_sisa_slot || data.animal.total_slot} slot</p>
                </div>

                <div style="margin-top: 20px;">
                    <button class="btn" onclick="closeModal('detailModal'); showSearchModal();">
                        🔍 Cari No Karantina
                    </button>
                </div>
            </div>
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
        function showOrderModal() {
            document.getElementById('orderModal').classList.add('show');
        }

        function showSearchModal() {
            document.getElementById('searchModal').classList.add('show');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('show');
            }
        });

        function searchAnimals() {
            const location = document.getElementById('search-location').value;
            const dates = document.getElementById('search-dates').value;
            const animal = document.getElementById('search-animal').value;

            // Simple filter - in real app would be more sophisticated
            const cards = document.querySelectorAll('.animal-card');
            let visibleCount = 0;

            cards.forEach(card => {
                let shouldShow = true;

                if (animal && card.dataset.animalId !== animal) {
                    shouldShow = false;
                }

                if (location) {
                    const animalName = card.querySelector('.animal-name').textContent.toLowerCase();
                    if (!animalName.includes(location.toLowerCase())) {
                        shouldShow = false;
                    }
                }

                card.style.display = shouldShow ? 'block' : 'none';
                if (shouldShow) visibleCount++;
            });

            if (visibleCount === 0) {
                alert('Tidak ada hewan yang sesuai dengan pencarian Anda');
            }
        }

        function viewAnimalDetail(animalId) {
            // Fetch animal detail via AJAX
            fetch('api/get-animal-detail.php?id=' + animalId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('detail-title').textContent = data.animal.nama_hewan;
                        document.getElementById('detail-content').innerHTML = `
                            <div class="form-group">
                                <label><strong>Deskripsi:</strong></label>
                                <p>${data.animal.deskripsi}</p>
                            </div>
                            
                            <div class="form-group">
                                <label><strong>Total Kandang:</strong></label>
                                <p>${data.animal.total_kandang} kandang</p>
                            </div>
                            
                            <div class="form-group">
                                <label><strong>Kapasitas per Kandang:</strong></label>
                                <p>${data.animal.kapasitas_per_kandang} ekor</p>
                            </div>
                            
                            <div class="form-group">
                                <label><strong>Total Slot:</strong></label>
                                <p>${data.animal.total_slot} slot</p>
                            </div>
                            
                            <div class="form-group">
                                <label><strong>Slot Terisi:</strong></label>
                                <p>${data.animal.total_terisi || 0} slot</p>
                            </div>
                            
                            <div class="form-group">
                                <label><strong>Slot Tersisa:</strong></label>
                                <p>${data.animal.total_sisa_slot || data.animal.total_slot} slot</p>
                            </div>
                            
                        `;
                        document.getElementById('detailModal').classList.add('show');
                    } else {
                        alert('Gagal memuat detail hewan');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat memuat detail');
                });
        }

        // Auto-clear messages after 5 seconds
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }

        function showSearchModal() {
            document.getElementById('searchModal').classList.add('show');
        }
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('hamburger-menu').addEventListener('click', function() {
                const navMenu = document.getElementById('nav-menu');
                navMenu.classList.toggle('active');
            });
        });

        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            if (!element) {
                console.error('Element with ID ' + elementId + ' not found.');
                return;
            }

            const text = element.textContent.trim();

            navigator.clipboard.writeText(text).then(() => {
                showNotification('Berhasil disalin ke clipboard', 'success', 2000);
            }).catch(() => {
                // Fallback untuk browser lama
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showNotification('Berhasil disalin ke clipboard', 'success', 2000);
            });
        }

        // Fungsi notifikasi (jika belum ada)
        function showNotification(message, type = 'info', duration = 5000) {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-icon">
                ${type === 'success' ? '✓' : type === 'error' ? '✗' : 'ℹ'}
            </span>
            <span class="notification-message">${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
        </div>
    `;

            // Add to page
            let container = document.getElementById('notification-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'notification-container';
                container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            max-width: 400px;
        `;
                document.body.appendChild(container);
            }

            container.appendChild(notification);

            // Auto remove
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.style.opacity = '0';
                    notification.style.transform = 'translateX(100%)';
                    setTimeout(() => notification.remove(), 300);
                }
            }, duration);
        }
        // ... di dalam tag <script> di dashboard.php ...

        document.addEventListener('DOMContentLoaded', function() {
            const passwordModal = document.getElementById('passwordModal');
            const confirmPasswordBtn = document.getElementById('confirmPasswordBtn');
            const adminPasswordInput = document.getElementById('admin_password');
            const messageDiv = document.getElementById('password_modal_message');

            let targetUrl = ''; // Menyimpan URL tujuan sementara

            // Fungsi untuk menampilkan modal
            function showModal(modalId) {
                document.getElementById(modalId).classList.add('show');
            }

            // Fungsi untuk menutup modal
            function closeModal(modalId) {
                document.getElementById(modalId).classList.remove('show');
            }

            // Tangani klik pada tautan "Tambah Admin"
            const addAdminLink = document.getElementById('addAdminLink');
            if (addAdminLink) {
                addAdminLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    targetUrl = this.href;
                    showModal('passwordModal');
                });
            }

            // Tangani klik pada tombol Konfirmasi di modal
            confirmPasswordBtn.addEventListener('click', function() {
                const password = adminPasswordInput.value;
                if (password === '') {
                    messageDiv.textContent = 'Password tidak boleh kosong.';
                    messageDiv.className = 'alert alert-error';
                    messageDiv.style.display = 'block';
                    return;
                }

                fetch('api/check-password.php', {
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
                            // Password benar, tutup modal dan lanjutkan ke URL target
                            closeModal('passwordModal');
                            window.location.href = targetUrl;
                        } else {
                            // Password salah, tampilkan pesan error
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

            // Event listener untuk tombol dan overlay penutup modal
            document.querySelectorAll('.close-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    closeModal('passwordModal');
                    resetPasswordModal();
                });
            });

            window.addEventListener('click', function(e) {
                if (e.target.classList.contains('modal')) {
                    closeModal(e.target.id);
                    resetPasswordModal();
                }
            });

            // Fungsi untuk mereset modal
            function resetPasswordModal() {
                adminPasswordInput.value = '';
                messageDiv.style.display = 'none';
                targetUrl = '';
            }
        });
    </script>
</body>

</html>