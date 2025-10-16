<?php
// admin/add-admin.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';

$auth = new AuthHandler();

// Require admin access - ini tetap ada untuk memastikan yang masuk adalah admin
$auth->require_admin();

$message = '';
$message_type = '';

// Handle add admin
if (isset($_POST['add_admin'])) {
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $message = 'Semua field harus diisi';
        $message_type = 'error';
    } elseif ($password !== $confirm_password) {
        $message = 'Password dan konfirmasi password tidak sama';
        $message_type = 'error';
    } elseif (strlen($password) < 6) {
        $message = 'Password minimal 6 karakter';
        $message_type = 'error';
    } else {
        $result = $auth->create_admin($username, $email, $password);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Admin - Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>

<body>
    <header class="header">
        <nav class="navbar">
            <a href="../dashboard.php" class="logo">
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

    <section class="admin-header">
        <div class="container">
            <h1>Tambah Admin</h1>
            <p>Buat akun admin baru untuk mengelola sistem</p>
        </div>
    </section>

    <div class="container" style="padding: 40px 20px; max-width: 600px;">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div style="background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); padding: 40px;">
            <div style="text-align: center; margin-bottom: 30px;">
                <h2>Buat Admin Baru</h2>
                <p style="color: #666;">Masukkan informasi admin yang akan dibuat</p>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label for="username">Username Admin</label>
                    <input type="text" name="username" id="username" required placeholder="Masukkan username admin">
                    <small style="color: #666; font-size: 12px;">Username harus unik dan mudah diingat</small>
                </div>

                <div class="form-group">
                    <label for="email">Email Admin</label>
                    <input type="email" name="email" id="email" required placeholder="admin@example.com">
                    <small style="color: #666; font-size: 12px;">Email akan digunakan untuk reset password</small>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required minlength="6" placeholder="Minimal 6 karakter">
                    <small style="color: #666; font-size: 12px;">Gunakan kombinasi huruf dan angka untuk keamanan</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Konfirmasi Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" required placeholder="Ulangi password">
                </div>

                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <button type="submit" name="add_admin" class="btn btn-success" style="flex: 1;">
                        ➕ Buat Admin
                    </button>
                    <a href="animals.php" class="btn btn-secondary" style="flex: 1; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center;">
                        ↩️ Kembali
                    </a>
                </div>
            </form>

            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                <h4 style="color: #666; font-size: 16px; margin-bottom: 10px;">🔒 Catatan Keamanan:</h4>
                <ul style="color: #666; font-size: 14px; margin-left: 20px;">
                    <li>Admin memiliki akses penuh ke sistem</li>
                    <li>Pastikan memberikan akses hanya kepada orang terpercaya</li>
                    <li>Admin dapat menambah hewan dan melihat semua data</li>
                    <li>Simpan informasi login admin dengan aman</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirm = this.value;

            if (password !== confirm) {
                this.setCustomValidity('Password tidak sama');
            } else {
                this.setCustomValidity('');
            }
        });

        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);

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