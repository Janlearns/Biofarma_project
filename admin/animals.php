<?php
// admin/animals.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../handlers/animal.php';

$auth = new AuthHandler();
$animal_handler = new AnimalHandler();

// Require admin access
$auth->require_admin();

$message = '';
$message_type = '';

// Handle add animal

if (isset($_POST['add_animal'])) {
    $nama_hewan = sanitize_input($_POST['nama_hewan']);
    $total_kandang = (int)$_POST['total_kandang'];
    $kapasitas_per_kandang = (int)$_POST['kapasitas_per_kandang'];
    $deskripsi = sanitize_input($_POST['deskripsi']);
    $foto_hewan = $_FILES['foto_hewan']; // Ambil data file

    if (empty($nama_hewan) || $total_kandang < 1 || $kapasitas_per_kandang < 1) {
        $message = 'Semua field harus diisi dengan benar';
        $message_type = 'error';
    } else {
        // Panggil fungsi add_animal dengan parameter foto_hewan
        $result = $animal_handler->add_animal($nama_hewan, $total_kandang, $kapasitas_per_kandang, $deskripsi, $foto_hewan);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'error';
    }
}
// ...

// Get all animals with details
$animals = $animal_handler->get_all_animals();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Hewan - Admin</title>
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
                <li><a href="animals.php" class="active">Kelola Hewan</a></li>
                <li><a href="karantina.php">Daftar Karantina</a></li>
                <li class="nav-item">
                    <a href="inventaris.php" class="nav-link">
                        <i class="fas fa-box"></i> Inventory
                    </a>
                </li>
                <li><a href="add-admin.php" class="active">Tambah Admin</a></li>

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
            <h1>Kelola Hewan</h1>
            <p>Tambah dan kelola hewan yang tersedia</p>
        </div>
    </section>

    <div class="container" style="padding: 40px 20px;">
        <!-- Alert Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Add Animal Button -->
        <button class="add-animal-btn" onclick="showAddModal()">
            ➕ Tambah Hewan Baru
        </button>

        <!-- Animals Table -->
        <div style="background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); overflow: hidden;">
            <div style="padding: 25px; border-bottom: 1px solid #eee;">
                <h2 style="margin: 0;">Daftar Hewan</h2>
            </div>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead style="background: #f8f9fa;">
                        <tr>
                            <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee;">Nama Hewan</th>
                            <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee;">Kode</th>
                            <th style="padding: 15px; text-align: center; border-bottom: 1px solid #eee;">Total Cage</th>
                            <th style="padding: 15px; text-align: center; border-bottom: 1px solid #eee;">Kapasitas/Cage</th>
                            <th style="padding: 15px; text-align: center; border-bottom: 1px solid #eee;">Total Kapasitas</th>
                            <th style="padding: 15px; text-align: center; border-bottom: 1px solid #eee;">Terisi</th>
                            <th style="padding: 15px; text-align: center; border-bottom: 1px solid #eee;">Sisa</th>
                            <th style="padding: 15px; text-align: center; border-bottom: 1px solid #eee;">Terakhir Diedit</th>
                            <th style="padding: 15px; text-align: center; border-bottom: 1px solid #eee;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($animals)): ?>
                            <tr>
                                <td colspan="9" style="padding: 40px; text-align: center; color: #666;">
                                    Belum ada hewan yang ditambahkan
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($animals as $animal): ?>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 15px;">
                                        <div style="font-weight: 600; color: #333; margin-bottom: 4px;">
                                            <?php echo htmlspecialchars($animal['nama_hewan']); ?>
                                        </div>
                                        <div style="font-size: 12px; color: #666;">
                                            Dibuat: <?php echo format_date($animal['created_at']); ?>
                                        </div>
                                    </td>
                                    <td style="padding: 15px;">
                                        <span style="background: #4A90E2; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                            <?php echo htmlspecialchars($animal['kode_awal']); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 15px; text-align: center;">
                                        <?php echo $animal['total_kandang']; ?>
                                    </td>
                                    <td style="padding: 15px; text-align: center;">
                                        <?php echo $animal['kapasitas_per_kandang']; ?>
                                    </td>
                                    <td style="padding: 15px; text-align: center;">
                                        <span style="font-weight: 600; color: #4A90E2;">
                                            <?php echo $animal['total_slot']; ?>
                                        </span>
                                    </td>
                                    <td style="padding: 15px; text-align: center;">
                                        <span style="color: #dc3545; font-weight: 600;">
                                            <?php echo $animal['total_terisi'] ?? 0; ?>
                                        </span>
                                    </td>
                                    <td style="padding: 15px; text-align: center;">
                                        <span style="color: #28a745; font-weight: 600;">
                                            <?php echo ($animal['total_sisa_slot'] ?? $animal['total_slot']); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 15px; text-align: center;"> <?php
                                                                                    // Periksa jika updated_at ada dan tidak sama dengan created_at
                                                                                    if ($animal['updated_at'] && $animal['updated_at'] !== $animal['created_at']) {
                                                                                        echo format_datetime($animal['updated_at']);
                                                                                    } else {
                                                                                        echo '-'; // Tampilkan '-' jika belum pernah diedit
                                                                                    }
                                                                                    ?>
                                    </td>
                                    <td style="padding: 15px; text-align: center;">
                                        <button class="btn" style="padding: 6px 12px; font-size: 14px; margin-right: 5px;" onclick="showEditModal(<?php echo $animal['id']; ?>)">
                                            ✏️ Edit
                                        </button>
                                        <button class="btn btn-secondary" style="padding: 6px 12px; font-size: 14px;" onclick="viewKandangStatus(<?php echo $animal['id']; ?>)">
                                            📊 Status Cage
                                        </button>
                                        <button class="btn btn-danger" style="padding: 6px 12px; font-size: 14px;" onclick="confirmDelete(<?php echo $animal['id']; ?>)">
                                            🗑️ Hapus
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Animal Modal -->
    <div class="modal" id="addAnimalModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Tambah Hewan Baru</h2>
                <button class="close-btn" onclick="closeModal('addAnimalModal')">&times;</button>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="nama_hewan">Nama Hewan</label>
                    <input type="text" name="nama_hewan" id="nama_hewan" required placeholder="Contoh: Kucing">
                    <small style="color: #666; font-size: 12px;">Kode awal akan dibuat otomatis dari huruf pertama nama</small>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="total_kandang">Total Cage</label>
                        <input type="number" name="total_kandang" id="total_kandang" required min="1" placeholder="5">
                    </div>

                    <div class="form-group">
                        <label for="kapasitas_per_kandang">Kapasitas per Cage</label>
                        <input type="number" name="kapasitas_per_kandang" id="kapasitas_per_kandang" required min="1" placeholder="4">
                    </div>
                </div>

                <div class="form-group">
                    <label>Total Kapasitas</label>
                    <div id="total_slot_preview" style="padding: 12px; background: #f8f9fa; border-radius: 8px; color: #666;">
                        Total kapasitas akan dihitung otomatis: Total Cage × Kapasitas per Cage
                    </div>
                </div>

                <div class="form-group">
                    <label for="deskripsi">Deskripsi</label>
                    <textarea name="deskripsi" id="deskripsi" required placeholder="Deskripsi hewan dan perawatannya..."></textarea>
                </div>
                <div class="form-group">
                    <label for="foto_hewan">Foto Hewan</label>
                    <input type="file" name="foto_hewan" id="foto_hewan" accept="image/*" required>
                    <small style="color: #666; font-size: 12px;">Pilih file gambar untuk hewan</small>
                </div>

                <button type="submit" name="add_animal" class="btn btn-success">
                    ➕ Tambah Hewan
                </button>
            </form>
        </div>
    </div>

    <!-- Kandang Status Modal -->
    <div class="modal" id="kandangModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="kandang-title">Status Cage</h2>
                <button class="close-btn" onclick="closeModal('kandangModal')">&times;</button>
            </div>

            <div id="kandang-content">

            </div>

        </div>
    </div>

    <div class="modal" id="chartModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="chart-title">Grafik Kapasitas Terisi</h2>
                <button class="close-btn" onclick="closeModal('chartModal')">&times;</button>
            </div>

            <div id="chart-container" style="width: 100%;">
                <canvas id="slotChart"></canvas>
            </div>
        </div>
    </div>

    <div class="modal" id="editAnimalModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit Hewan</h2>
                <button class="close-btn" onclick="closeModal('editAnimalModal')">&times;</button>
            </div>

            <form id="editForm">
                <input type="hidden" name="id" id="edit_hewan_id">

                <div class="form-group">
                    <label for="edit_nama_hewan">Nama Hewan</label>
                    <input type="text" name="nama_hewan" id="edit_nama_hewan" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="edit_total_kandang">Total Cage</label>
                        <input type="number" name="total_kandang" id="edit_total_kandang" required min="1">
                        <small id="current_total_kandang" style="color: #666; font-size: 12px;"></small>
                    </div>

                    <div class="form-group">
                        <label for="edit_kapasitas_per_kandang">Kapasitas per Cage</label>
                        <input type="number" name="kapasitas_per_kandang" id="edit_kapasitas_per_kandang" required min="1">
                        <small id="current_kapasitas_per_kandang" style="color: #666; font-size: 12px;"></small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_deskripsi">Deskripsi</label>
                    <textarea name="deskripsi" id="edit_deskripsi" required></textarea>
                </div>

                <button type="submit" class="btn" style="width: 100%;">
                    💾 Simpan Perubahan
                </button>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let myChart = null; // Variabel untuk menyimpan instance grafik

        function showAddModal() {
            document.getElementById('addAnimalModal').classList.add('show');
        }

        function closeModal(modalId) {
            // Hancurkan grafik saat modal ditutup
            if (modalId === 'kandangModal' && myChart) {
                myChart.destroy();
                myChart = null;
            }
            document.getElementById(modalId).classList.remove('show');
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('show');
            }
        });

        // Calculate total slots automatically
        function updateTotalSlots() {
            const totalKandang = parseInt(document.getElementById('total_kandang').value) || 0;
            const kapasitasPerKandang = parseInt(document.getElementById('kapasitas_per_kandang').value) || 0;
            const totalSlot = totalKandang * kapasitasPerKandang;

            const preview = document.getElementById('total_slot_preview');
            if (totalSlot > 0) {
                preview.innerHTML = `<strong>Total Kapasitas: ${totalSlot}</strong> (${totalKandang} kandang × ${kapasitasPerKandang} kapasitas)`;
                preview.style.color = '#4A90E2';
            } else {
                preview.innerHTML = 'Total Kapasitas akan dihitung otomatis: Total Kandang × Kapasitas per Kandang';
                preview.style.color = '#666';
            }
        }

        document.getElementById('total_kandang').addEventListener('input', updateTotalSlots);
        document.getElementById('kapasitas_per_kandang').addEventListener('input', updateTotalSlots);

        function viewKandangStatus(animalId) {
            // Gabungkan fetch data grafik di sini
            fetch('../api/get-animal-detail.php?id=' + animalId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const animal = data.animal;

                        let kandangHtml = `
                        <div style="margin-bottom: 20px;">
                            <h4>Informasi Umum</h4>
                            <p><strong>Total Cage> ${animal.total_kandang}</p>
                            <p><strong>Kapasitas per Cage:</strong> ${animal.kapasitas_per_kandang}</p>
                            <p><strong>Total Kapasitas:</strong> ${animal.total_slot}</p>
                            <p><strong>Total Terisi:</strong> ${animal.total_terisi || 0}</p>
                            <p><strong>Sisa:</strong> ${animal.total_sisa_slot || animal.total_slot}</p>
                        </div>
                    `;

                        document.getElementById('kandang-content').innerHTML = kandangHtml;
                        document.getElementById('kandangModal').classList.add('show');

                        // Ambil data grafik dan tampilkan
                        fetch('../api/get-slot-data.php?id=' + animalId)
                            .then(response => response.json())
                            .then(chartData => {
                                if (chartData.success) {
                                    renderChart(chartData.chart_data);
                                } else {
                                    console.error('Gagal memuat data grafik:', chartData.message);
                                }
                            });

                    } else {
                        alert('Gagal memuat status Cage');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat memuat status Cage');
                });
        }

        function renderChart(chartData) {
            // Hancurkan grafik yang ada kalo ada
            if (myChart) {
                myChart.destroy();
            }

            const chartContainer = document.createElement('div');
            chartContainer.id = 'chart-container';
            chartContainer.style.width = '100%';
            chartContainer.innerHTML = '<canvas id="slotChart"></canvas>';

            document.getElementById('kandang-content').appendChild(chartContainer);

            const ctx = document.getElementById('slotChart').getContext('2d');
            myChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: 'Kapasitas Terisi',
                        data: chartData.data,
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Jumlah Kapasitas'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Tanggal'
                            }
                        }
                    }
                }
            });
        }

        function confirmDelete(animalId) {
            if (confirm("Apakah Anda yakin ingin menghapus hewan ini? Ini akan menghapus semua Cage dan data terkait.")) {
                window.location.href = 'delete_animal.php?id=' + animalId;
            }
        }

        function showEditModal(animalId) {
            fetch('../api/get-animal-detail.php?id=' + animalId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const animal = data.animal;
                        document.getElementById('edit_hewan_id').value = animal.id;
                        document.getElementById('edit_nama_hewan').value = animal.nama_hewan;
                        document.getElementById('edit_total_kandang').value = animal.total_kandang;
                        document.getElementById('edit_kapasitas_per_kandang').value = animal.kapasitas_per_kandang;
                        document.getElementById('edit_deskripsi').value = animal.deskripsi;
                        document.getElementById('current_total_kandang').textContent = `Cage saat ini: ${animal.total_kandang}`;
                        document.getElementById('current_kapasitas_per_kandang').textContent = `Kapasitas saat ini: ${animal.kapasitas_per_kandang}`;
                        document.getElementById('editAnimalModal').classList.add('show');
                    } else {
                        alert('Gagal memuat data hewan.');
                    }
                });
        }

        document.getElementById('editForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('../api/update-animal.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        alert(result.message);
                        location.reload();
                    } else {
                        alert(result.message);
                    }
                });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const hamburgerBtn = document.getElementById('hamburger-menu');
            const navMenu = document.getElementById('nav-menu');
            if (hamburgerBtn && navMenu) {
                hamburgerBtn.addEventListener('click', function() {
                    navMenu.classList.toggle('active');
                });
            }
        });
        
    </script>
</body>

</html>