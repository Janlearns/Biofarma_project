<?php
// reset-password.php
session_start();
require_once 'config/database.php';
require_once 'auth/auth.php';

$auth = new AuthHandler();
$message = '';
$message_type = '';

// Cek apakah user sudah diverifikasi
if (!isset($_SESSION['can_reset_password']) || $_SESSION['can_reset_password'] !== true) {
    redirect('index.php');
}

// Handle password reset
if (isset($_POST['reset_password'])) {
    $email = $_SESSION['reset_email'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = $_SESSION['reset_user_type'];

    if (empty($new_password) || empty($confirm_password)) {
        $message = 'Semua field harus diisi';
        $message_type = 'error';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Password dan konfirmasi password tidak sama';
        $message_type = 'error';
    } elseif (strlen($new_password) < 6) {
        $message = 'Password minimal 6 karakter';
        $message_type = 'error';
    } else {
        // Ganti baris ini
        // $result = $auth->reset_password($token, $new_password);
        // Dengan baris baru ini
        $result = $auth->reset_password($email, $new_password, $user_type);
        
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'error';
        
        if ($result['success']) {
            unset($_SESSION['can_reset_password']);
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_user_type']);
            echo '<script>setTimeout(() => window.location.href = "index.php", 3000);</script>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - BioVet</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>🔒 Reset Password</h1>
                <p>Masukkan password baru Anda</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                    <?php if ($message_type === 'success'): ?>
                        <br><small>Anda akan diarahkan ke halaman login dalam 3 detik...</small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="new_password">Password Baru</label>
                    <input type="password" id="new_password" name="new_password" required minlength="6" placeholder="Minimal 6 karakter">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Konfirmasi Password Baru</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="Ulangi password baru">
                </div>

                <button type="submit" name="reset_password" class="btn" style="width: 100%;">
                    🔄 Reset Password
                </button>
            </form>

            <div class="register-link">
                <p><a href="index.php">Kembali ke index</a></p>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('new_password').value;
            const confirm = this.value;
            
            if (password !== confirm) {
                this.setCustomValidity('Password tidak sama');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>