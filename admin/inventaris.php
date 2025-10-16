<?php
// admin/inventaris.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../handlers/inventaris.php';

$auth = new AuthHandler();
$inventaris_handler = new InventarisHandler();

// Require admin access
$auth->require_admin();

$message = '';
$message_type = '';

if (isset($_POST['add_inventaris'])) {
    $tanggal_datang = sanitize_input($_POST['tanggal_datang']);
    $kode_barang = sanitize_input($_POST['kode_barang']);
    $jenis_barang = sanitize_input($_POST['jenis_barang']);
    $no_batch = sanitize_input($_POST['no_batch']);
    $total_barang = (float)$_POST['total_barang'];
    $satuan = sanitize_input($_POST['satuan']);
    $uji_file = $_FILES['uji_organoleptik_file'];
    $expired_date = sanitize_input($_POST['expired_date']);

    $result = $inventaris_handler->add_inventaris($tanggal_datang, $kode_barang, $jenis_barang, $no_batch, $total_barang, $satuan, $expired_date, $uji_file);

    if ($result['success']) {
        redirect('inventaris.php?message=' . urlencode($result['message']) . '&type=success');
    } else {
        $message = $result['message'];
        $message_type = 'error';
    }
}

// Get all inventory items
$inventaris_items = $inventaris_handler->get_all_inventaris();

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>

<body>
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
                <li><a href="karantina.php">Daftar Karantina</a></li>
                <li><a href="inventaris.php" class="active">Inventory</a></li>
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

    <section class="admin-header">
        <div class="container">
            <h1>inventory</h1>
            <p>Kelola barang inventory</p>
        </div>
    </section>

    <div class="container" style="padding: 40px 20px;">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="button-group" style="text-align: right; margin-bottom: 20px;">
            <button class="btn btn-primary" onclick="showAddModal()">
                + Tambah
            </button>
        </div>

        <div style="background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); overflow: hidden;">
            <div style="padding: 25px; border-bottom: 1px solid #eee;">
                <h2 style="margin: 0;">Daftar Barang Inventory</h2>
            </div>
            <div style="overflow-x: auto;">
                <table style="width: 150%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee;">No</th>
                            <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee;">Tanggal Datang</th>
                            <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee;">Kode Barang</th>
                            <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee;">Jenis Barang</th>
                            <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee;">No Batch</th>
                            <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee;">ED</th>
                            <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee;">Jumlah</th>
                            <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee;">Terpakai</th>
                            <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee;">Saldo Akhir</th>
                            <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee;">Dok. Pendukung</th>
                            <th style="padding: 15px; text-align: center; border-bottom: 1px solid #eee;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($inventaris_items)): ?>
                            <?php $no = 1; ?>
                            <?php foreach ($inventaris_items as $item): ?>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 15px;"><?php echo $no++; ?></td>
                                    <td style="padding: 15px;"><?php echo htmlspecialchars($item['tanggal_datang']); ?></td>
                                    <td style="padding: 15px;"><?php echo htmlspecialchars($item['kode_barang']); ?></td>
                                    <td style="padding: 15px;"><?php echo htmlspecialchars($item['jenis_barang']); ?></td>
                                    <td style="padding: 15px;"><?php echo htmlspecialchars($item['no_batch']); ?></td>
                                    <td style="padding: 15px;"><?php echo htmlspecialchars($item['expired_date']); ?></td>
                                    <td class="total-barang-cell" style="padding: 15px;"><?php echo htmlspecialchars($item['total_barang']) . ' ' . htmlspecialchars($item['satuan']); ?></td>
                                    <td class="terpakai-form-cell" style="padding: 15px;">
                                        <form class="update-terpakai-form" data-id="<?php echo $item['id']; ?>" style="display: flex; gap: 5px;">
                                            <input type="number" step="0.01" name="terpakai" value="<?php echo htmlspecialchars($item['terpakai']); ?>" required style="width: 80px; padding: 5px; border: 1px solid #ccc; border-radius: 4px;">
                                            <button type="submit" class="btn btn-sm btn-info" style="padding: 5px 10px; font-size: 12px;" title="Simpan"><i class="fas fa-save"></i></button>
                                        </form>
                                    </td>
                                    <td class="saldo-pakai-cell" style="padding: 15px;"><?php echo htmlspecialchars($item['saldo_pakai']) . ' ' . htmlspecialchars($item['satuan']); ?></td>
                                    <td style="padding: 15px;">
                                        <?php if (!empty($item['uji_organoleptik_file'])): ?>
                                            <a href="../uploads/<?php echo htmlspecialchars($item['uji_organoleptik_file']); ?>" download class="btn btn-sm btn-secondary" style="flex: 1; text-align: center; padding: 4px 8px; font-size: 12px;">
                                                <i class="fas fa-download"></i> Unduh
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" style="flex: 1; padding: 4px 8px; font-size: 12px;" onclick="confirmDeleteFile(<?php echo $item['id']; ?>)">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        <?php else: ?>
                                            <form class="upload-form" data-id="<?php echo $item['id']; ?>" enctype="multipart/form-data" style="display: flex; gap: 5px; align-items: center;">
                                                <input type="file" name="uji_organoleptik_file" required style="width: 150px;">
                                                <button type="submit" class="btn btn-sm btn-primary" title="Unggah"><i class="fas fa-upload"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 15px; text-align: center;">
                                        <button class="btn btn-sm btn-danger" style="padding: 5px 10px; font-size: 12px;" onclick="deleteInventaris(<?php echo $item['id']; ?>)">
                                            <i class="fas fa-trash"></i> Hapus
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" style="padding: 40px; text-align: center; color: #666;">
                                    Tidak ada data Inventory
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal" id="addInventarisModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Tambah Barang Inventory</h2>
                <button class="close-btn" onclick="closeModal('addInventarisModal')">&times;</button>
            </div>
            <form action="inventaris.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="tanggal_datang">Tanggal Datang</label>
                    <input type="date" name="tanggal_datang" id="tanggal_datang" required>
                </div>
                <div class="form-group">
                    <label for="kode_barang">Kode Barang</label>
                    <input type="text" name="kode_barang" id="kode_barang" required placeholder="Ex: KOD001">
                </div>
                <div class="form-group">
                    <label for="jenis_barang">Jenis Barang</label>
                    <input type="text" name="jenis_barang" id="jenis_barang" required placeholder="Ex: Vitamin C">
                </div>
                <div class="form-group">
                    <label for="no_batch">No Batch</label>
                    <input type="text" name="no_batch" id="no_batch" required placeholder="Ex: BATCH-001">
                </div>
                <div class="form-group">
                    <label for="expired_date">Tanggal Kedaluwarsa (ED)</label>
                    <input type="text" name="expired_date" id="expired_date" required placeholder="Contoh: 2025-12-31">
                </div>
                <div class="form-group">
                    <label for="total_barang">Total Barang</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="number" step="0.01" name="total_barang" id="total_barang" required style="flex: 1;">
                        <select name="satuan" required style="width: 100px;">
                            <option value="kg">kg</option>
                            <option value="g">g</option>
                            <option value="liter">liter</option>
                            <option value="ml">ml</option>
                            <option value="pcs">pcs</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="uji_organoleptik_file">Dok. Pendukung</label>
                    <input type="file" name="uji_organoleptik_file" id="uji_organoleptik_file">
                    <small style="color: #666; font-size: 12px;">Opsional: Upload file bukti uji organoleptik</small>
                </div>
                <button type="submit" name="add_inventaris" class="btn btn-success" style="width: 100%;">
                    Simpan
                </button>
            </form>
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

            // Event listener untuk tombol close modal
            document.querySelectorAll('.close-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const modalId = this.closest('.modal').id;
                    closeModal(modalId);
                });
            });

            // Close modal when clicking outside
            window.addEventListener('click', function(e) {
                const addInventarisModal = document.getElementById('addInventarisModal');
                if (e.target == addInventarisModal) {
                    closeModal('addInventarisModal');
                }
            });

            document.querySelectorAll('.update-terpakai-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const id = this.dataset.id;
                    const terpakai = this.querySelector('[name="terpakai"]').value;

                    fetch('../api/update_inventaris.php', {
                            method: 'POST',
                            body: new URLSearchParams({
                                'id': id,
                                'terpakai': terpakai
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Data terpakai berhasil diperbarui.');
                                const saldoCell = this.closest('tr').querySelector('.saldo-pakai-cell');
                                const totalBarangText = this.closest('tr').querySelector('.total-barang-cell').textContent;

                                const totalBarang = parseFloat(totalBarangText.split(' ')[0]);
                                const satuan = totalBarangText.split(' ')[1];

                                const saldoPakai = totalBarang - parseFloat(terpakai);
                                saldoCell.textContent = saldoPakai.toFixed(2) + ' ' + satuan;
                            } else {
                                alert('Gagal memperbarui data: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Terjadi kesalahan saat memperbarui data.');
                        });
                });
            });

            document.querySelectorAll('.upload-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const formId = this.dataset.id;
                    const formData = new FormData(this);

                    fetch(`../api/upload_inventory_file.php?id=${formId}`, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('File berhasil diunggah.');
                                location.reload();
                            } else {
                                alert('Gagal mengunggah file: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Terjadi kesalahan saat mengunggah file.');
                        });
                });
            });

        });

        function showAddModal() {
            document.getElementById('addInventarisModal').classList.add('show');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }

        function deleteInventaris(id) {
            if (confirm('Yakin ingin menghapus item ini?')) {
                fetch(`../api/delete_inventaris.php?id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Item berhasil dihapus.');
                            location.reload();
                        } else {
                            alert('Gagal menghapus item: ' + data.message);
                        }
                    });
            }
        }

        function confirmDeleteFile(id) {
            if (confirm('Apakah Anda yakin ingin menghapus file ini?')) {
                fetch(`../api/delete_inventory_file.php`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `id=${id}` // Mengirim ID dalam body POST
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('File berhasil dihapus.');
                            location.reload();
                        } else {
                            alert('Gagal menghapus file: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Terjadi kesalahan saat menghapus file.');
                    });
            }
        }
    </script>
</body>

</html>